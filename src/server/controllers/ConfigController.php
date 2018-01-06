<?php
/* ************************************************************************

   Bibliograph: Collaborative Online Reference Management

   http://www.bibliograph.org

   Copyright:
     2007-2017 Christian Boulanger

   License:
     LGPL: http://www.gnu.org/licenses/lgpl.html
     EPL: http://www.eclipse.org/org/documents/epl-v10.php
     See the LICENSE file in the project's top-level directory for details.

   Authors:
     * Chritian Boulanger (cboulanger)

************************************************************************ */

namespace app\controllers;

use app\controllers\AppController;


/**
 * Service class providing methods to get or set configuration
 * values
 */
class ConfigController extends AppController
{

  /**
   * types that config values may have
   * @var array
   */
  protected $types = array(
    QCL_CONFIG_TYPE_STRING,
    QCL_CONFIG_TYPE_NUMBER,
    QCL_CONFIG_TYPE_BOOLEAN,
    QCL_CONFIG_TYPE_LIST
  );
  
  //-------------------------------------------------------------
  // JSONRPC service methods
  //-------------------------------------------------------------

 /**
  * Service method to load config data
  * @param string|null $filter Filter
  * @return array
  */
  public function actionLoad( $filter=null )
  {
    return $this->getAccessibleKeys( $filter );
  }

  /**
   * Service method to set a config value
   * @param string $key Key
   * @param mixed $value Value
   * @throws InvalidArgumentException 
   * @return bool
   */
  function actionSet( $key, $value )
  {
    /*
     * check key
     */
    if ( ! $this->keyExists( $key ) )
    {
      throw new InvalidArgumentException("Configuration key '$key' does not exist");
    }
    if ( ! $this->valueIsEditable( $key ) )
    {
      throw new InvalidArgumentException("The value of configuration key '$key' is not editable");
    }

    /*
     * if value is customizable, set the user variant of the key
     */
    if ( $this->valueIsCustomizable( $key ) )
    {
      $this->requirePermission("config.value.edit");
      $this->setKey( $key, $value );
    }

    /*
     * else, you need special permission to edit the default
     */
    else
    {
      $this->requirePermission("config.default.edit");
      $this->setKeyDefault( $key, $value );
    }

    /*
     * result
     */
    return "OK";
  }

  //-------------------------------------------------------------
  // API methods
  //-------------------------------------------------------------

   /**
   * Creates a preference enty with the given properties
	 * @param $key
	 *     The name ("key") of the config value
   * @param mixed $default
   *     The default value
   * @param boolean $customize
   *     If true, allow users to create their
   *     own variant of the configuration setting
	 * @param bool $final
	 *     If true, the value cannot be modified after creation
	 */
  public function addPreference( $key, $default, $customize=false,  $final=false )
  {
    switch( gettype( $default) )
    {
      case "boolean": $type = QCL_CONFIG_TYPE_BOOLEAN; break;
      case "integer": 
      case "double":  
        $type = QCL_CONFIG_TYPE_NUMBER; break;
      case "string": $type = QCL_CONFIG_TYPE_STRING; break;
      case "array": $type = QCL_CONFIG_TYPE_LIST; break;
      default: 
        throw new LogicException("Invalid default value for preference key '$key'");
    }
    $this->createKeyIfNotExists($key, $type, $customize, $default, $final);
  }
  
  /**
   * Returns the value of the given preference key. Alias of #getKey()
   * @param string $key The name of the preference
   * @return mixed
   */
  public function getPreference( $key )
  {
    return $this->getKey( $key );
  }
  
  /**
   * Sets the value of the given preference key. Alias of #setKey()
   * @param string $key The name of the preference
   * @return void
   */
  public function setPreference( $key, $value )
  {
    $this->setKey( $key, $value );
  }  

  /**
   * Sets up configuration keys if they do not already exist
   * @param array Map of maps with the name of the config key as
   * key and a map of "type","custom", "default", and "final" keys with values
   * as value.
   * @return void
   */
  public function setupConfigKeys( $map )
  {
    $configModel = $this->getConfigModel();
    foreach( $map as $key => $data )
    {
      qcl_assert_valid_string( $key, "Invalid key $key");
      qcl_assert_array_keys( $data, array("type","custom","default","final") );
      $configModel->createKeyIfNotExists(
        $key, $data['type'], $data['custom'], $data['default'], $data['final']
      );
    }
  }  

  //-------------------------------------------------------------
  // helper methods
  //-------------------------------------------------------------

  /**
   * Given a user id, return the user model. If no id is given or
   * the id is boolean false, return the active user model object
   * @param $userId
   * @throws InvalidArgumentException
   * @return qcl_access_model_User
   */
  protected function getUserFromId( $userId=false )
  {
    if ( $userId === false )
    {
      return $this->getActiveUser();
    }
    elseif ( is_numeric( $userId ) and $userId > 0 )
    {
      $user = User::findOne($userId);
      if ( ! $user ) {
        throw new InvalidArgumentException( "User id '$userId' does not exist.");
      }
    }
    else
    {
      throw new InvalidArgumentException( "Invalid user id '$userId'");
    }
  }

  /**
   * Casts the given config value to given value type.
   *
   * @param mixed $value
   * @param string $type
   * @param bool $phpType If false, convert the value for saving in the database,
   *   if true (default), convert them into the corresponding php type
   * @throws InvalidArgumentException
   * @return mixed $value
   * @todo rewrite the typecasting stuff, this is confusing.
   */
  protected function castType( $value, $type, $phpType = true )
  {
    switch ( $type )
    {
      case "number"  :
        if ( $phpType ) return floatval($value);
        return (string) $value;
      case "boolean" :
        if ( $phpType ) return $value == "true" ? true : false;
        return boolString($value);
      case "list" :
        if ( $phpType )
        {
          switch ( gettype( $value ) )
          {
            case "string":
              return explode(",", $value);
            case "array":
              return $value;
            default:
              return array();
          }
        }
        else
        {
          switch ( gettype( $value ) )
          {
            case "string":
              return $value;
            case "array":
              return implode( ",", $value );
            default:
              return "";
          }
        }

      case "string":
        if ( $phpType ) return strval($value);
        return (string) $value;
      default:
        throw new InvalidArgumentException("Invalid type '$type'");
    }
  }

  /**
   * Checks if the type of the configuration value is correct
   * @param mixed $value
   * @param string $type
   * @throws InvalidArgumentException
   * @return bool True if correct
   */
  protected function isType( $value, $type )
  {
    switch ( $type )
    {
      case "number"  :
        return is_numeric($value);
      case "boolean" :
        return is_bool( $value );
      case "list" :
        return is_array( $value );
      case "string":
        return is_string( $value );
      default:
        throw new InvalidArgumentException("Invalid type '$type'");
    }
  }

  /**
   * Checks if value is of the correct type and throws an exception if not
   * @param mixed $value
   * @param string $type
   * @return void
   * @throws InvalidArgumentException
   */
  protected function checkType( $value, $type )
  {
    if ( ! $this->isType( $value, $type ) )
    {
      throw new InvalidArgumentException( sprintf(
        "Incorrect type. Expected '%s', got '%s'", $type, typeof( $value )
      ) );
    }
  }

  /**
   * Returns the index code of the config key type
   * @param string $type
   * @return number
   */
  protected function getTypeIndex( $type )
  {
    return array_search( $type, $this->types );
  }

  /**
   * Given its numeric id, returns the string name of the config key type
   * @param number $index
   * @return string
   */
  protected function getTypeString( $index )
  {
    return $this->types[$index];
  }

  /**
   * Checks if a configuration key exists and throws an exception if not.
   * @param $key
   * @throws InvalidArgumentException
   * @return void
   * @todo This is inefficient. Methods should try to load the record and
   * abort if not found.
   */
  protected function checkKey( $key )
  {
    if ( ! $this->keyExists( $key ) )
    {
      throw new InvalidArgumentException( sprintf(
        "Configuration key '%s' does not exist.", $key
      ) );
    }
  }

  /**
   * Checks if the config key exists
   * @param string $key
   * @return bool True if it does.
   */
  public function keyExists( $key )
  {
    return (boolean) $this->findOne(['namedId'=>$key]);
  }


/**
   * Creates a config property
   *
   * @param $key
   *     The name ("key") of the config value
   * @param $type
   *     The type of the config value
   * @param boolean $customize
   *     If true, allow users to create their
   *     own variant of the configuration setting
   * @param mixed|null $default
   *     If not null, set a default value
   * @param bool $final
   *     If true, the value cannot be modified after creation
   * @throws InvalidArgumentException
   * @see app\controllers\ConfigController::$types
   * @return int|bool
   *     Returns the id of the newly created record, or false if
   *     key was not created.
   */
	public function createKey( $key, $type, $customize=false, $default=null, $final=false )
	{

		/*
		 * Check type
		 */
		if ( ! in_array( $type, $this->types ) )
		{
			throw new InvalidArgumentException("Invalid type '$type' for key '$key'");
		}

    /*
     * check if key exists
     */
    if ( $this->keyExists( $key ) )
    {
      throw new qcl_config_Exception("Config key '$key' already exists.");
    }

    $data = array(
      'type'      => $this->getTypeIndex( $type ),
      'customize' => $customize,
      'final'     => $final
    );

    if ( $default !== null )
    {
      $data['default'] = $this->castType( $default, $type, false );
    }

    /*
     * create new entry
     */
		return Config::create( $key, $data );

	}

	/**
	 * Create a config key if it doesn't exist already.
	 *
	 * @param $key
	 *     The name ("key") of the config value
	 * @param $type
	 *     The type of the config value
	 *     @see qcl_config_ConfigModel::$types
   * @param boolean $customize
   *     If true, allow users to create their
   *     own variant of the configuration setting
   * @param mixed|null $default
   *     If not null, set a default value
	 * @param bool $final
	 *     If true, the value cannot be modified after creation
	 * @return int|bool
	 *     Returns the id of the newly created record, or false if
	 *     key was not created.
	 *
	 */
	public function createKeyIfNotExists( $key, $type, $customize=false, $default=null, $final=false )
	{
    if ( ! $this->keyExists( $key ) )
    {
      return $this->createKey( $key, $type, $customize, $default, $final );
    }
    else
    {
      return false;
    }
  }

  /**
   * Returns an array of all config keys
   * @return array
   */
  public function keys()
  {
    return $this->getQueryBehavior()->fetchValues( "namedId" );
  }

  /**
   * Sets a default value for a config key
   * @param string $key
   * @param mixed $value
   * @throws qcl_config_Exception
   * @return void
   */
  public function setKeyDefault( $key, $value )
  {
    $this->checkKey( $key );
    $this->load( $key );
    if ( ! $this->getFinal() )
    {
      $this->setDefault( $this->castType( $value, $this->keyType(), false ) );
      $this->save();
    }
    else
    {
      throw new qcl_config_Exception("Config key '$key' cannot be changed.");
    }
  }

  /**
   * Returns true if value of the key can be edited
   * @param $key
   * @return bool
   */
  public function valueIsEditable( $key )
  {
    $this->checkKey( $key );
    $this->load( $key );
    return ! $this->getFinal();
  }

  /**
   * Returns true if value of the key is customizable by the user
   * @param $key
   * @return bool
   */
  public function valueIsCustomizable( $key )
  {
    $this->checkKey( $key );
    $this->load( $key );
    return $this->getCustomize();
  }

  /**
   * Gets the default value for a config key
   * @param $key
   * @return mixed
   */
  public function getKeyDefault( $key )
  {
    $this->checkKey( $key );
    $this->load( $key );
    return $this->castType( $this->getDefault(), $this->keyType(), true );
  }

  /**
   * Returns config property value. Raises an error if key does not exist.
   * @param string $key The name of the property (i.e., myapplication.config.locale)
   * @param bool|false|int|\qcl_access_model_User $user Optional user (id). If not given,
   *   get the config key for the current user. If no custom value exists for the given
   *   user, return the default value.
   * @return bool|array|string|int value of property.
   */
  public function getKey( $key, $user=false )
  {
    $this->checkKey( $key );
    $userModel = $this->checkUserModel( $user );
    $userId    = $userModel? $userModel->getId() : null;

    /*
     * are the results cached?
     */
    if ( isset( $this->cache[$key][$userId] ) )
    {
      return $this->cache[$key][$userId];
    }

    /*
     * load record
     */
    $this->load( $key );

    /*
     * look for user variant
     */
    $value = null;
    $userConfigModel = $this->getUserConfigModel();
    if( $userModel ) {
      $userConfigModel->findWhere( array(
        $userModel->foreignKey()   => $userId,
        $this->foreignKey()        => $this->id()
      ) );
      if ( $userConfigModel->foundSomething() )
      {
        $userConfigModel->loadNext();
        $value = $userConfigModel->getValue();
      }
    }

    if ( $value === null)
    {
      $value = $this->getDefault();
    }
    $value = $this->castType( $value, $this->keyType(), true );

    /*
     * retrieve, cache and return value
     */
    if ( ! isset( $this->cache[$key] ) )
    {
      $this->cache[$key] = array();
    }
    $this->cache[$key][$userId] = $value;
    return $value;
  }

  /**
   * Sets config property
   * @param string $key The name of the config key  (i.e., myapplication.config.locale)
   * @param string $value The value of the property.
   * @param bool|false|int|\qcl_access_model_User $user Optional user (id). If given, create
   *   or set a custom user value for that key.
   * @throws qcl_config_Exception
   * @return qcl_config_ConfigModel
   * @todo permissions
   */
  public function setKey( $key, $value, $user=false)
  {

    $this->checkKey( $key );
    $userModel = $this->checkUserModel( $user );
    $userId = $userModel? $userModel->id() : null;

    /*
     * load record and look for custom user value and check
     * whether this model allows customized values
     */
    $this->load( $key );

    if( ! $this->getCustomize() )
    {
      throw new qcl_config_Exception( sprintf(
        "Config key '%s' does not allow user values.", $key
      ) );
    }

    if ( $this->getFinal() )
    {
      throw new qcl_config_Exception("Config key '$key' cannot be changed.");
    }

    /*
     * look for custom user value
     */
    $userConfigModel = $this->getUserConfigModel();
    $userConfigModel->findWhere( array(
      $userModel->foreignKey()   => $userId,
      $this->foreignKey()        => $this->id()
    ) );

    /*
     * convert value into format that can be stored into
     * the database
     */
    $storeValue = $this->castType( $value, $this->keyType(), false );

    /*
     * if the custom user value exists, update it,
     * otherwise create it
     */
    if ( $userConfigModel->foundSomething() )
    {
      $userConfigModel->loadNext();
      $userConfigModel->setValue( $storeValue );
      $userConfigModel->save();
    }
    else
    {
      $userConfigModel->create( array(
        $userModel->foreignKey()  => $userId,
        $this->foreignKey()       => $this->id(),
        "value"                   => $storeValue
      ));
    }

    /*
     * update cache
     */
    if ( ! isset( $this->cache[$key] ) )
    {
      $this->cache[$key] = array();
    }
    $this->cache[$key][$userId] = $value;
    return $this;
  }

  /**
   * Deletes the user data of a config key. In order to delete
   * the key itself, use delete()
   *
   * @param string $key
   * @param bool|int $userId Optional id of the user to whom the custom value
   *   belongs or false if current user
   * @return void
   */
	public function deleteKey( $key, $userId= false )
	{
	  $this->checkKey( $key );
	  $userModel = $this->getUserFromId( $userId );

	  $this->load( $key );

	  $userConfigModel = $this->getUserConfigModel();
    $userConfigModel->deleteWhere( array(
      $this->foreignKey()      => $this->id(),
      $userModel->foreignKey() => $userModel->id()
    ) );
	}

  /**
   * Resets the user variant of a config value to the default value.
   * @param string $key
   * @param bool|int $userId Optional id of the user to whom the custom value
   *   belongs or false if current user
   * @return void
   */
  public function resetKey( $key, $userId = false )
  {
    $this->checkKey( $key );
    $this->setKey( $key, $this->getDefault( $key ), $userId );
  }

  /**
   * Returns the type of a key, either of the currently loaded record
   * or of the given key.
   * @todo bad bad
   * @param string $key
   * @return string
   */
  public function keyType( $key=null )
  {
    if ( $key !== null )
    {
      $this->checkKey( $key );
      $this->load( $key );
    }
    return $this->getTypeString( $this->_get("type") );
  }

  /**
   * Returns the data of config keys that are readable by the active user.
   *
   * @param string $mask return only a subset of entries that start with $mask
   * @param bool|int $userId Optional id of the user to whom the custom value
   *   belongs or false if current user
   * @return array Map with the keys 'keys', 'types' and 'values', each
   *  having an index array with all the values.
   */
	public function getAccessibleKeys( $mask=null, $userId = false  )
	{
    /*
     * no accessible keys if no active user
     */
    $userModel = $this->getUserFromId( $userId );
    if ( ! $userModel ) return array();

    /*
     * iterate through all keys and return either the
     * user custom value or the default value
     */
    $keys   = array();
    $types  = array();
    $values = array();

    foreach ( $this->keys() as $key )
    {
      if( ! $key )
      {
        $this->warn("Empty config key!");
        continue;
      }
      $keys[]   = $key;
      $values[] = $this->getKey( $key, $userId );
      $types[]  = $this->keyType();
    }

		return array(
		  'keys'    => $keys,
		  'values'  => $values,
		  'types'   => $types
		);
	}
}