<?php

namespace app\modules\graphql;

use Exception;
use GraphQL\Server\RequestError;
use GraphQL\Type\Definition\ObjectType;
use GraphQL\Type\Definition\Type;
use lib\models\BaseModel;
use ReflectionClass;
use ReflectionException;
use Yii;

/**
 *
 */
class ModelProxyType extends ObjectType {

  /**
   * Classes providing the GraphQl type classes. Must be manually included
   * above.
   */
  protected const TYPE_CONTAINER_CLASSES = [
    Type::class
  ];

  /**
   * Creates the type instances of the given class and caches it or returns the
   * cached version.
   * @param $class
   * @return ModelProxyType
   * @throws ReflectionException
   */
  public static function getInstance($class) {
    if (!isset(self::$instances[$class])) {
      self::$instances[$class] = new self($class);
    }
    return self::$instances[$class];
  }

  /**
   * A registry of type instances
   * @var array
   */
  protected static $instances = [];

  /**
   * The yii model class
   * @var string
   */
  protected $modelClass;

  /**
   * The current active record
   * @var array;
   */
  protected $recordData;

  /**
   * The id of the current ActiveRecord
   * @var string
   */
  protected $recordId;


  /**
   * YiiModelType constructor.
   * @param $class
   * @throws ReflectionException
   */
  function __construct($class) {
    $this->modelClass = $class;
    parent::__construct([
      'name' => $class,
      'fields' => $this->fields()
    ]);
  }

  /**
   * Returns the class name of the Yii model for which this instance is a proxy
   * @return string
   */
  public function getModelClass() {
    return $this->modelClass;
  }

  /**
   * Returns the args part of the query
   * @return array
   */
  public function getArgs() {
    return [
      'id' => [
        'type' => Type::string(),
        'description' => "The numeric or alphanumeric id of the data record"
      ]
    ];
  }

  /**
   * @return array|void
   * @throws ReflectionException
   * @throws Exception
   */
  private function fields() {
    $fields = [];
    $reflection = new ReflectionClass($this->modelClass);
    $doc = $reflection->getDocComment();
    $regex =  "/@property (?<type>\w+) \\$(?<name>\w+) ?(?<description>.*)/";
    preg_match_all($regex, $doc, $matches, PREG_SET_ORDER);
    foreach ($matches as $property) {
      $type = $property['type'];
      $graphql_type = null;
      /** @var Type $class */
      foreach (self::TYPE_CONTAINER_CLASSES as $class) {
        if (method_exists($class, $type)) {
          $graphql_type = $class::{$type}();
          break;
        }
      }
      if ($graphql_type) {
        $fields[$property['name']] = [
          'type' => $graphql_type,
          'description' => $property['description']
        ];
      } else {
        Yii::debug("Cannot determine GraphQL type for type/class '$type'");
      }
    }
    return $fields;
  }


  /**
   * @param $rootValue
   * @param $args
   * @return mixed
   * @throws RequestError
   */
  public function resolve($rootValue, $args) {
    yii::debug("Requested {$this->name} with id {$args['id']}");
    if ($args['id'] !== $this->recordId) {
      /** @var BaseModel $class */
      $class = $this->getModelClass();
      /** @var BaseModel $instance */
      $instance = null;
      if (is_numeric($args['id'])) {
        $instance = $class::findOne($args['id']);
      } else {
        $instance = $class::findByNamedId($args['id']);
      }
      if (!$instance) {
        throw new RequestError("Invalid id {$args['id']} for {$this->getModelClass()}");
      }
      $this->recordData = $instance->toArray();
    }
    return $this->recordData;
  }
}
