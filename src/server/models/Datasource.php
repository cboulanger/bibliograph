<?php

namespace app\models;

use Yii;
use lib\models\BaseModel;
use app\models\Role;
use yii\helpers\ArrayHelper;

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
   * A descriptive name (should be set)
   * @var string
   */
  static $name = "";

  /**
   * More detailed description of the Datasource (optional)
   * @var string
   */
  static $description = "";

  /**
   * Models that are attached to this datasource
   * @var array
   */
  private $modelMap = array();

  /**
   * A cache of datasource instance
   * @todo move to manager
   * @var array
   */
  public static $instances =[];

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
      [['title', 'schema', 'database','prefix'], 'string', 'max' => 100],
      [['description', 'resourcepath'], 'string', 'max' => 255],
      [['type', 'encoding'], 'string', 'max' => 20],
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

  //-------------------------------------------------------------
  // Virtual properties
  //-------------------------------------------------------------

  public function getFormData()
  {
    return [
      'title' => [
        'label' => Yii::t('app', "Name")
      ],
      'description' => [
        'type' => "TextArea",
        'lines' => 3,
        'label' => Yii::t('app', "Description")
      ],
      'schema' => [
        'type' => "selectbox",
        'label' => Yii::t('app', "Schema"),
        'delegate' => [
          'options' => "getSchemaOptions"
        ]
      ],
      'type' => [
        'label' => Yii::t('app', "Type")
      ],
      'host' => [
        'label' => Yii::t('app', "Server host"),
        'placeholder' => "The database server host, usually 'localhost'"
      ],
      'port' => [
        'label' => Yii::t('app', "Server port"),
        'marshal' => function($v){ return (string) $v;},
        'unmarshal' => function($v){ return (int) $v;},
        'placeholder' => "The database server port, usually 3306 for MySql"
      ],
      'database' => [
        'label' => Yii::t('app', "Database name"),
        'placeholder' => "The name of the database",
        'validation' => [
          'required' => true
        ]
      ],
      'username' => [
        'label' => Yii::t('app', "Database user name")
      ],
      'password' => [
        'type' => "passwordfield",
        'label' => Yii::t('app', "Database user password")
      ],
      'encoding' => [
        'label' => Yii::t('app', "Database encoding"),
        'default' => 'utf-8'
      ],
      'prefix' => [
        'label' => Yii::t('app', "Datasource prefix")
      ],
      'resourcepath' => [
        'label' => Yii::t('app', "Resource path")
      ],
      'active' => [
        'type' => "SelectBox",
        'label' => Yii::t('app', "Status"),
        'options' => [
          ['label' => "Disabled", 'value' => 0 ],
          ['label' => "Active", 'value' => 1 ]
        ],
        'marshal' => function($v){ return $v ? 1:0;}
      ]
    ];
  }

  //-------------------------------------------------------------
  // Relations
  //-------------------------------------------------------------

  /**
   * @return \yii\db\ActiveQuery
   */
  protected function getDatasourceRoles()
  {
    return $this->hasMany(Datasource_Role::class, ['DatasourceId' => 'id']);
  }

  /**
   * @return \yii\db\ActiveQuery
   */
  protected function getRoles()
  {
    return $this->hasMany(Role::class, ['id' => 'RoleId'])->via('datasourceRoles');
  }

  /**
   * @return \yii\db\ActiveQuery
   */
  protected function getDatasourceUsers()
  {
    return $this->hasMany(Datasource_User::class, ['DatasourceId' => 'id']);
  }

  /**
   * @return \yii\db\ActiveQuery
   */
  protected function getUsers()
  {
    return $this->hasMany(User::class, ['id' => 'UserId'])->via('datasourceUsers');
  }

  /**
   * @return \yii\db\ActiveQuery
   */
  protected function getDatasourceGroups()
  {
    return $this->hasMany(Datasource_Group::class, ['DatasourceId' => 'id']);
  }

  /**
   * @return \yii\db\ActiveQuery
   */
  protected function getGroups()
  {
    return $this->hasMany(Group::class, ['id' => 'GroupId'])->via('datasourceGroups');
  }


  /**
   * Public to avoid magic property access
   * @return \yii\db\ActiveQuery
   */
  public function getSchema()
  {
    return $this->hasOne(Schema::class, [ 'namedId' => 'schema' ] );
  }

  /*
  ---------------------------------------------------------------------------
     API
  ---------------------------------------------------------------------------
  */

  /**
   * Returns the prefix for the model table. Default is the named
   * plus an underscore
   * @property string modelTablePrefix
   * @return string
   */
  public static function createTablePrefix($namedId)
  {
    return $namedId . "_";
  }

  /**
   * Returns the instance of a subclass which is specialized for this
   * datasource.
   *
   * @param string $datasourceName
   * @return \app\models\Datasource
   * @throws \InvalidArgumentException
   * @throws \AssertionError
   * @todo move to manager
   */
  public static function getInstanceFor($datasourceName)
  {
    if (isset(static::$instances[$datasourceName])) {
      return static::$instances[$datasourceName];
    }
    $baseclass = Yii::$app->config->getPreference('app.datasource.baseclass');
    // create new instance
    /** @var Datasource $datasource */
    $datasource = Datasource::findByNamedId($datasourceName);
    if (is_null($datasource)) {
      throw new \InvalidArgumentException("Datasource '$datasourceName' does not exist.");
    }
    if( ! $datasource->schema ){
      throw new \RuntimeException("Datasource '$datasourceName' has no linked schema.");
    }
    $schema = $datasource->getSchema()->one();
    if( ! $schema ){
      throw new \RuntimeException("Schema '{$datasource->schema}' does not exist.");
    }
    $class = $schema->class;
    if( ! \class_exists($class) ){
      $class = $baseclass;
    }

    // create instance of subclass
    /** @var BibliographicDatasource $instance */
    $instance = $class::findOne(['namedId' => $datasourceName]);

    if (is_null($instance)) {
      throw new \InvalidArgumentException("Datasource '$datasourceName' does not exist.");
    }
    static::$instances[$datasourceName] = $instance;
    return $instance;
  }

  /**
   * @inheritdoc
   */
  public function delete()
  {
    unset(static::$instances[$this->namedId]);
    return parent::delete();
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
   * @todo rename
   */
  public static function in($datasourceName, $modelType = null)
  {
    if (strpos($datasourceName, ".") > 0) {
      list($datasourceName, $modelType) = explode(".", $datasourceName);
    }
    return static::getInstanceFor($datasourceName)->getClassFor($modelType);
  }


  /**
   * Returns all Datasource instances that belong to the schema with the given name
   * @param $schemaName
   * @return Datasource[]
   */
  public static function findBySchema( $schemaName )
  {
    return static :: find()->where(['schema'=>$schemaName])->all();
  }

  /**
   * Returns the yii Connection object for this datasource
   * @param string $datasourceName
   * @return \yii\db\Connection
   * @throws \LogicException
   * @throws \Exception
   */
  public function getConnection()
  {
    // cache
    static $connections = [];
    if (!isset($connections[$this->namedId])) {
      $this->useDsnDefaults();

      switch ($this->type) {
        case "mysql":
          $dsn = "{$this->type}:host={$this->host};port={$this->port};dbname={$this->database}";
          break;
        default:
          throw new \LogicException("Support for datasource type '{$this->type}' has not been implemented yet.");
      }
      // determine table prefix from database or datasource name
      $global_prefix = trim(Yii::$app->config->getIniValue("database.tableprefix"));
      if (!is_null($this->prefix)) {
        $prefix = $global_prefix . $this->prefix;
      } else {
        $prefix = $global_prefix . $this->namedId . "_"; //@todo: shouldn't default be just global prefix?
      }

      $connection = new \yii\db\Connection([
        'dsn' => $dsn,
        'username' => $this->username,
        'password' => $this->password,
        'tablePrefix' => $prefix
      ]);

      $connections[$this->namedId] = $connection;
      return $connection;
    }
    return $connections[$this->namedId];
  }

  /**
   * Uses the application dsn defaults if the datasource information doesn't
   * contain it.
   *
   * @return void
   * @throws \Exception
   */
  protected function useDsnDefaults()
  {
    foreach (["host", "port", "database", "username", "password"] as $key) {
      $connection = Yii::$app->datasourceManager->parseDsn();
      if (!$this->$key) {
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
   * @throws \InvalidArgumentException
   * @return void
   */
  public function addModel($type, $class, $service = null)
  {
    if (!$type or !is_string($type)) throw new \InvalidArgumentException("Invalid type");
    if (!$class or !is_string($class)) throw new \InvalidArgumentException("Invalid class");

    ArrayHelper::setValue($this->modelMap, [$type, "model", "class"], $class);
    ArrayHelper::setValue($this->modelMap, [$type, "controller", "service"], $service);
  }

  /**
   * Returns the types all the models registered
   * @return array
   */
  public function modelTypes()
  {
    return array_keys($this->modelMap);
  }

  /**
   * Returns the class name of the model of the given type, which can be used
   * to create instances of this class via
   * Datasource::getInstanceFor('database1')::getClassFor('reference')::find()->...
   * This implicitly sets the static property 'datasource' of the class to the current
   * datasource name.
   * @param string $type
   * @throws \InvalidArgumentException
   * @return string The class name
   */
  public function getClassFor($type)
  {
    if (!$type or !is_string($type)) throw new \InvalidArgumentException("Invalid type");
    if (!isset($this->modelMap[$type])) {
      throw new \InvalidArgumentException("Model of type '$type' is not registered");
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
  public function getServiceName($type)
  {
    if (!$type or !is_string($type)) throw new \InvalidArgumentException("Invalid type");
    if (!isset($this->modelMap[$type]['controller']['service'])) {
      return null;
    }
    return $this->modelMap[$type]['controller']['service'];
  }

  /**
   * Creates the tables for the models associated with this datasource
   *
   * @return void
   * @throws \Exception
   */
  public function createModelTables()
  {
    Yii::$app->datasourceManager->createModelTables($this);
  }

  public function getSchemaOptions()
  {
    return Schema::find()
      ->select("name as label, namedId as value")
      ->asArray()
      ->all();
  }
}