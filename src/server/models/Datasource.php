<?php

namespace app\models;

use Yii;
use lib\models\BaseModel;
use yii\helpers\ArrayHelper;
use fourteenmeister\helpers\Dsn;

// @todo Change column `schema` to `class`
// @todo Add column `dsn` to `class`, remove columns `type`, `host`, `port`, `database`

/**
 * This is the model class for table "data_Datasource".
 *
 * @property integer $id
 * @property string $namedId
 * @property string $created
 * @property string $modified
 * @property string $title
 * @property string $description
 * @property string $schema
 * @property string $type
 * @property string $host
 * @property integer $port
 * @property string $database
 * @property string $username
 * @property string $password
 * @property string $encoding
 * @property string $prefix
 * @property string $resourcepath
 * @property integer $active
 * @property integer $readonly
 * @property integer $hidden
 */
class Datasource extends BaseModel
{

  /**
   * Models that are attached to this datasource
   * @var array
   */
  private $modelMap = array();

  /**
   * @inheritdoc
   */
  public static function tableName()
  {
    return 'data_Datasource';
  }

  /**
   * @inheritdoc
   */
  public function rules()
  {
    return [
      [['created', 'modified'], 'safe'],
      [['port', 'active', 'readonly', 'hidden'], 'integer'],
      [['namedId', 'username', 'password'], 'string', 'max' => 50],
      [['title', 'schema', 'database'], 'string', 'max' => 100],
      [['description', 'resourcepath'], 'string', 'max' => 255],
      [['type', 'encoding', 'prefix'], 'string', 'max' => 20],
      [['host'], 'string', 'max' => 200],
      [['namedId'], 'unique'],
    ];
  }

  /**
   * @inheritdoc
   */
  public function attributeLabels()
  {
    return [
      'id' => Yii::t('app', 'ID'),
      'namedId' => Yii::t('app', 'Named ID'),
      'created' => Yii::t('app', 'Created'),
      'modified' => Yii::t('app', 'Modified'),
      'title' => Yii::t('app', 'Title'),
      'description' => Yii::t('app', 'Description'),
      'schema' => Yii::t('app', 'Schema'),
      'type' => Yii::t('app', 'Type'),
      'host' => Yii::t('app', 'Host'),
      'port' => Yii::t('app', 'Port'),
      'database' => Yii::t('app', 'Database'),
      'username' => Yii::t('app', 'Username'),
      'password' => Yii::t('app', 'Password'),
      'encoding' => Yii::t('app', 'Encoding'),
      'prefix' => Yii::t('app', 'Prefix'),
      'resourcepath' => Yii::t('app', 'Resourcepath'),
      'active' => Yii::t('app', 'Active'),
      'readonly' => Yii::t('app', 'Readonly'),
      'hidden' => Yii::t('app', 'Hidden'),
    ];
  }

  public function formData()
  {
    return array(
      'title'       => array(
        'label'       => $this->tr("Name")
      ),
      'description' => array(
        'type'        => "TextArea",
        'lines'       => 3,
        'label'       => $this->tr("Description")
      ),
      'schema'      => array(
        'type'        => "selectbox",
        'label'       => $this->tr("Schema"),
        'delegate'    => array(
          'options'     => "getSchemaOptions"
        )
      ),
      'type'        => array(
        'label'       => $this->tr("Type")
      ),
      'host'        => array(
        'label'       => $this->tr("Server host"),
        'placeholder' => "The database server host, usually 'localhost'"
      ),
      'port'        => array(
        'label'       => $this->tr("Server port"),
        'marshaler'   => array(
          'marshal'    => array( 'function' => "qcl_toString" ),
          'unmarshal'  => array( 'function' => "qcl_toInteger" )
        ),
        'placeholder' => "The database server port, usually 3306 for MySql"
      ),
      'database'    => array(
        'label'       => $this->tr("Database name"),
        'placeholder' => "The name of the database",
        'validation'  => array(
          'required'    => true
        )
      ),
      'username'    => array(
        'label'       => $this->tr("Database user name")
      ),
      'password'    => array(
        'label'       => $this->tr("Database user password")
      ),
      'encoding'    => array(
        'label'       => $this->tr("Database encoding"),
        'default'     => 'utf-8'
      ),
      'prefix'      => array(
        'label'       => $this->tr("Datasource prefix")
      ),
      'resourcepath' => array(
        'label'       =>  $this->tr("Resource path")
      ),
      'active'        => array(
        'type'    => "SelectBox",
        'label'   =>  $this->tr("Status"),
        'options' => array(
          array( 'label' => "Disabled", 'value' => false ),
          array( 'label' => "Active",   'value' => true )
        )
      )
    );      
  }

  // /**
  //  * Creates a new datasource
  //  *
  //  * @param [type] $config
  //  */
  // public function __construct( $config )
  // {
  //   parent::__construct( $config ); // calls init, whichs adds the models in the subclass
  // }


  /**
   * Returns the instance of a subclass which is specialized for this
   * datasource.
   *
   * @param string $datasourceName  
   * @return string
   * @todo add and use 'class' column instead of 'schema'
   */
  public static function getInstanceFor( $datasourceName )
  {
    // cache
    static $instances = array();
    if( isset( $instances[$datasourceName] ) ){
      return $instances[$datasourceName];
    }

    // create new instance
    $datasource = Datasource::findOne(['namedId' => $datasourceName]);
    if( is_null($datasource) ) throw new \InvalidArgumentException("Datasource '$datasourceName' does not exist.");
    // backwards compatibility
    $schema = $datasource->schema;
    switch( $schema ){
      case "bibliograph.schema.bibliograph2":
        $class = "app\models\BibliographicDatasource"; 
        break;
      case "qcl.schema.filesystem.local":
        $class = "lib\channel\Filesystem";
        break;
      default: 
        $class = str_replace(".","\\",$schema);
    }
    // create instance of subclass 
    $instance = $class::findOne(['namedId'=>$datasourceName]);
    if( is_null( $instance) ){
      throw new \InvalidArgumentException("Datasource '$datasourceName' does not exist.");
    }
    $instances[$datasourceName] = $instance;
    return $instance;
  }

  /**
   * Static shorthand method to get the classname of the datasource's model of
   * the given type.
   *
   * @param string $datasourceName The Name of the datasource. Can also be a string 
   * composed of the datasource name and the model type, separated by a dot. In this 
   * case, the model type can be left empty.
   * @param string $modelType
   * @return string The name of the class
   */
  public static function in( $datasourceName, $modelType=null ){
    if( strpos($datasourceName,".") > 0 ){
      list($datasourceName, $modelType) = explode( ".", $datasourceName );
    }
    return static::getInstanceFor( $datasourceName )->getClassFor( $modelType );
  }

  /**
   * Returns the yii Connection object for this datasource
   * @param string $datasourceName
   * @return \yii\db\Connection
   */
  public function getConnection()
  {
    // cache
    static $connection=null;
    if( is_null($connection) ){
      $this->useDsnDefaults();  
      switch( $this->type ){
        case "mysql":
        $dsn = "{$this->type}:host={$this->host};port={$this->port};dbname={$this->database}";
        break;
        default: 
        throw new LogicException("Support for datasource type '{$this->type}' has not been implemented yet.");
      }
      // determine table prefix from database or datasource name
      // @todo add global prefix from ini file
      if( ! is_null($this->prefix) ){
        $prefix = $this->prefix;
      } else {
        $prefix = $this->namedId . "_";
      }
      $connection = new \yii\db\Connection([
        'dsn' => $dsn,
        'username' => $this->username,
        'password' => $this->password,
        'tablePrefix' => $prefix
      ]);
    }
    return $connection;
  }

  /**
   * Uses the application dsn defaults if the datasource information doesn't
   * contain it. 
   *
   * @return void
   */
  protected function useDsnDefaults(){
    foreach( ["host","port","database","username","password"] as $key ){
      $connection = self::parseAppDsn(); 
      if( ! $this->$key ){
        $this->$key = $connection[$key];
      }
    }
  }

  /**
   * Registers a models that is part of the datasource
   * @param string $type The name of the type
   * @param string $class The class of the model
   * @param string|null $service (optional) The service that provides access to model data
   * type of model to the model classes
   * @throws InvalidArgumentException
   * @return void
   */
  public function addModel( $type, $class, $service=null)
  {
    if( !$type or !is_string($type) ) throw new \InvalidArgumentException("Invalid type");
    if( !$class or !is_string($class) ) throw new \InvalidArgumentException("Invalid class");

    ArrayHelper::setValue( $this->modelMap, [$type, "model", "class"], $class );
    ArrayHelper::setValue( $this->modelMap, [$type, "controller", "service"], $service );
  }

  /**
   * Returns the types all the models registered
   * @return array
   */
  public function modelTypes()
  {
    return array_keys( $this->modelMap );
  }

  /**
   * Returns the class name of the model of the given type, which can be used
   * to create instances of this class via
   * Datasource::getInstanceFor('database1')::getClassFor('reference')::find()->...
   * This implicitly sets the static property 'datasource' of the class to the current
   * datasource name. 
   * @param string $type
   * @throws InvalidArgumentException
   * @return string The class name
   */
  public function getClassFor( $type )
  {
    if( !$type or !is_string($type) ) throw new \InvalidArgumentException("Invalid type");
    if ( ! isset( $this->modelMap[$type] ) )
    {
      throw new InvalidArgumentException("Model of type '$type' is not registered");
    }
    $class = $this->modelMap[$type]['model']['class'];
    $class::setDatasource($this->namedId);
    return $class;
  }

  /**
   * Returns the rpc service name for the given model type, if defined.
   * @param string $type
   *    The model type
   * @return string|null
   *    The service name or null if none exists
   */
  public function getServiceName( $type )
  {
    if( !$type or !is_string($type) ) throw new \InvalidArgumentException("Invalid type");
    if ( ! isset( $this->modelMap[$type]['controller']['service'] ) )
    {
      return null;
    }
    return $this->modelMap[$type]['controller']['service'];
  }

  /**
   * Creates a new datasource and returns it. You should set at least the `title` property before saving the ActiveRecord 
   * to the database. By default, it will use the database connection provided by the `db` component. 
   *
   * @param string $datasourceName
   * @param string $className|null  Optional class name of the datasource, defaults to `app\models\BibliographicDatasource`
   * @return app\models\BibliographicDatasource 
   */
  public static function create( $datasourceName, $className = '\app\models\BibliographicDatasource' )
  {
    if( !$datasourceName or ! is_string($datasourceName) ) throw new \InvalidArgumentException("Invalid datasource name");
    $datasource = new Datasource([
      'namedId' => $datasourceName,
      'title' => $datasourceName,
      'schema' => $className,
      'prefix' => $datasourceName,
      'active' => 1
    ]);
    $datasource->setAttributes(self::parseAppDsn());
    return $datasource;
  }

  /**
   * Parses the Yii2 datasource connection information in a way that
   * can be stored in the datasource db record
   *
   * @return array
   */
  protected static function parseAppDsn(){
    $dsn = Dsn::parse(Yii::$app->db->dsn);
    $db  = Yii::$app->db;
    return [
      'type' => $dsn->sheme,
      'host' => $dsn->host,
      'port' => $dsn->port,
      'database' => $dsn->database,
      'username' => $db->username,
      'password' => $db->password,
      'encoding' => $db->charset,
    ];
  }

  /**
   * Returns the prefix for the model table, consisting of the named id of
   * this datasource and an underscore
   * @property string modelTablePrefix
   * @return string
   */
  public function getModelTablePrefix()
  {
    return $this->namedId . "_";
  }


  /**
   * Create the tables for the datasource models
   *
   * @return void
   */
  public function createModelTables()
  {
    foreach( $this->modelTypes() as $type){
      $modelClass = $this->getClassFor( $type );
      $db = $modelClass::getDb();
      $prototypeTableName = "data_" . ucfirst($type);
      $prefix = $this->modelTablePrefix;
      $modelTableName = $prefix . $prototypeTableName;
      $migrationClass = APP_MIGRATION_ID . "_create_table_" . $prototypeTableName;
      $migrationFile = Yii::getAlias("@app/migrations/schema/{$migrationClass}.php"); //@todo very fragile and hackish!!
      Yii::trace( "Creating table $modelTableName from class $migrationClass...");
      if( !\class_exists($migrationClass) ){
        include_once($migrationFile);
      }
      $migration = new $migrationClass();
      $migration->db->tablePrefix = Yii::$app->utils->getIniValue('database.tableprefix') . $prefix;
      $migration->safeUp();
    }
  }
}