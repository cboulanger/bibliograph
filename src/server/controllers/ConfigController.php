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
use app\models\Config;
use app\models\User;
use app\models\UserConfig;
use app\controllers\dto\ConfigLoadResult;

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
    "string","number","boolean","list"
  );
  
  //-------------------------------------------------------------
  // JSONRPC service methods
  //-------------------------------------------------------------

 /**
  * Service method to load config data
  * @param string|null $filter Filter
  * xxreturn \app\controllers\dto\ConfigLoadResult
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
    // check key
    if ( ! $this->keyExists( $key ) )
    {
      throw new InvalidArgumentException("Configuration key '$key' does not exist");
    }
    if ( ! $this->valueIsEditable( $key ) )
    {
      throw new InvalidArgumentException("The value of configuration key '$key' is not editable");
    }

    // if value is customizable, set the user variant of the key
    if ( $this->valueIsCustomizable( $key ) )
    {
      $this->requirePermission("config.value.edit");
      $this->setKey( $key, $value );
    }

    // else, you need special permission to edit the default
    else
    {
      $this->requirePermission("config.default.edit");
      $this->setKeyDefault( $key, $value );
    }
    return "OK";
  }

  /**
   * Service method to get a config value
   * @param string $key Key
   * @throws InvalidArgumentException 
   * @return mixed
   */
  function actionGet( $key )
  {
    // check key
    if ( ! $this->keyExists( $key ) )
    {
      throw new InvalidArgumentException($this->tr("Configuration key '$key' does not exist"));
    }
    return $this->getKey($key);
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
      case "boolean": 
        $type = "boolean"; break;
      case "integer":    
      case "double":  
        $type =  "number"; break;
      case "string": 
        $type = "string"; break;
      case "array": 
        $type = "list"; break;
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
      throw new \InvalidArgumentException( sprintf(
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
    return (boolean) Config::findOne(['namedId'=>$key]);
  }

  /**
   * Returns the config record with that key
   * @param string $key
   * @return \app\models\Config
   * @throws \InvalidArgumentException If key does not exist
   */
  protected function getModel( $key )
  {
    $config = Config::findOne(['namedId'=>$key]);
    if ( ! $config  ) {
      throw new \InvalidArgumentException( sprintf(
        "Configuration key '%s' does not exist.", $key
      ) );
    }
    return $config;
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
			throw new \InvalidArgumentException("Invalid type '$type' for key '$key'");
		}

    /*
     * check if key exists
     */
    if ( $this->keyExists( $key ) )
    {
      throw new \InvalidArgumentException("Config key '$key' already exists.");
    }

    $data = array(
      'namedId'   => $key,
      'type'      => $this->getTypeIndex( $type ),
      'customize' => $customize,
      'final'     => $final
    );

    if ( $default !== null )
    {
      $data['default'] = $this->castType( $default, $type, false );
    }

    // create new entry
    $config = new Config( $data );
    if( $config->save() ) return true;

    // throw validation errors
    throw new \LogicError( $config->errors );
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
    return Config::find()->select('namedId')->column();
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
    $config = $this->getModel($key);
    if ( ! $config->final )
    {
      $config->default = $this->castType( $value, $this->keyType($key), false );
      $config->save();
    }
    else
    {
      throw new LogicException("Config key '$key' cannot be changed.");
    }
  }

  /**
   * Returns true if value of the key can be edited
   * @param $key
   * @return bool
   */
  public function valueIsEditable( $key )
  {
    $config = $this->getModel($key);
    return ! $config->final;
  }

  /**
   * Returns true if value of the key is customizable by the user
   * @param $key
   * @return bool
   */
  public function valueIsCustomizable( $key )
  {
    $config = $this->getModel($key);
    return $config->customize;
  }

  /**
   * Gets the default value for a config key
   * @param $key
   * @return mixed
   * @throws \InvalidArgumentException
   */
  public function getKeyDefault( $key )
  {
    $config = $this->getModel( $key );
    return $this->castType( $config->default, $this->keyType( $key ), true );
  }

  /**
   * Returns config property value. Raises an error if key does not exist.
   * @param string $key The name of the property (i.e., myapplication.config.locale)
   * @param \app\models\User $user (optional) user. If not given,
   *   get the config key for the current user. If no custom value exists for the given
   *   user, return the default value.
   * @return bool|array|string|int value of property.
   * @throws \InvalidArgumentException
   */
  public function getKey( $key, $user=null )
  {
    if( ! $user ) $user = $this->getActiveUser();
    $config = $this->getModel( $key );
    return $this->castType( 
      $config->getUserConfigValue($user),
      $this->keyType( $key ),
      true 
    );
  }

  /**
   * Sets config property
   * @param string $key The name of the config key  (i.e., myapplication.config.locale)
   * @param string $value The value of the property.
   * @param \app\models\User $user (optional) user.
   * @throws \InvalidArgumentException
   */
  public function setKey( $key, $value, $user=false)
  {
    $config = $this->getModel( $key );
    if( ! $user ) $user = $this->getActiveUser();
    if( ! $config->customize ) {
      throw new \LogicException( sprintf(
        "Config key '%s' does not allow user values.", $key
      ) );
    }
    if ( $config->final ) {
      throw new \LogicException("Config key '$key' cannot be changed.");
    }
    $storeValue = $this->castType( $value, $this->keyType($key), false );
    $userConfig = $config->getUserConfig($user);
    if ( $userConfig )
    {
      $userConfig->value = $storeValue;
      $userConfig->save();
    }
    else
    {
      $userConfig = new UserConfig([
        'UserId' => $user->id,
        'ConfigId' => $config->id,
        'value'   => $storeValue
      ]);
      $userConfig->save(); 
    }
    return $this;
  }

  /**
   * Deletes the user data of a config key. In order to delete
   * the key itself, use delete()
   *
   * @param string $key
   * @param \app\models\User $user (optional) user.
   * @return void
   */
	public function deleteKey( $key, $user= false )
	{
    $config = $this->getModel( $key );
    if( ! $user ) $user = $this->getActiveUser();
	  UserConfig::deleteAll(['UserId' => $user->id]);
	}

  /**
   * Resets the user variant of a config value to the default value.
   * @param string $key
   * @param \app\models\User $user (optional) user 
   * @return void
   */
  public function resetKey( $key, $user = false )
  {
    $config = $this->getModel( $key );
    if( ! $user ) $user = $this->getActiveUser();
    $userConfig = $config->getUserConfig($user);
    if( $userConfig ){
      $userConfig->value = $config->default;
      $userConfig->save();
    }
  }

  /**
   * Returns the type of a key
   * @param string $key
   * @return string
   */
  public function keyType( $key )
  {
    $config = $this->getModel( $key );
    return $this->getTypeString( $config->type );
  }

  /**
   * Returns the data of config keys that are readable by the active user.
   *
   * @param string $mask return only a subset of entries that start with $mask
   * @param \app\models\User $user (optional) user 
   * @return array Map with the keys 'keys', 'types' and 'values', each
   *  having an index array with all the values.
   */
	public function getAccessibleKeys( $mask=null, $user = false  )
	{
    if( ! $user ) $user = $this->getActiveUser();

    $keys   = array();
    $types  = array();
    $values = array();

    foreach ( $this->keys() as $key )
    {
      $keys[]   = $key;
      $values[] = $this->getKey( $key, $user );
      $types[]  = $this->keyType( $key );
    }

		return array(
		  'keys'    => $keys,
		  'values'  => $values,
		  'types'   => $types
		);
	}
}