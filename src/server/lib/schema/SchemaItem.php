<?php

namespace lib\schema;

use JsonSerializable;
use yii\base\BaseObject;
use yii\base\InvalidArgumentException;

/**
 * Class SchemaItem
 * @package lib\schema
 * @property string $name The name of the Schema Item, must be unique at the class level
 */
class SchemaItem
  extends BaseObject
{

  /**
   * @var int
   */
  private static $idCounter = 0;

  /**
   * @var int
   */
  private $id = 0;

  /**
   * The static instance cache
   * @var static[]
   */
  private static $instances=[];

  /**
   * The main name of the type, which acts as its unique id.
   * @var string
   */
  public $name = null;

  /**
   * The human-readable label of the schema item, which is translated or
   * can be translated to other languages
   * @var string
   */
  public $label = "";

  /**
   * The name of the field in other contexts, if different from its name
   * @var array
   */
  private $names = [];

  /**
   * The english label of the field in other contexts, if different from its label
   * @var array
   */
  private $labels = [];

  /**
   * @var Relation[]
   */
  private $relations = [];

  /**
   * Returns an id used for storing the particular instance
   * @internal
   * @param $name
   * @return string
   */
  private static function cacheId($name) {
    return static::class . "-$name";
  }

  /**
   * Returns a singleton instance for the given name, creating it if it does
   * not exist already
   * @param string $name
   * @return static
   */
  static public function getInstance($name) {

    if (!isset(self::$instances[self::cacheId($name)])) {
      return self::createInstance(['name' => $name]);
    }
    return self::$instances[self::cacheId($name)];
  }

  /**
   * Creates a new singleton instance of this class with the given "name" property
   * @param array $config
   * @return static
   */
  static public function createInstance($config=[]) {
    if (!isset($config['name'])) {
      throw new InvalidArgumentException("Config array must have a 'name' key");
    }
    $cacheId = self::cacheId($config['name']);
    self::$instances[$cacheId] = new static($config);
    return self::$instances[$cacheId];
  }

  public function __construct($config = [])
  {
    // generate new id
    $this->id = ++self::$idCounter;
    if (!isset($config['name'])) {
      throw new InvalidArgumentException("Config map must have 'name' key");
    }
    $name = $config['name'];
    if (isset(static::$instances[$name])) {
      throw new InvalidArgumentException("A an instance of " . static::class . " with the property 'name' having value '$name' already exists.");
    }
    parent::__construct($config);
  }

  /**
   * Returns the numeric id. Internal use only, since the identification
   * of the instance might be implemented differently later.
   * @internal
   * @return int
   */
  protected function getId() {
    return $this->id;
  }

  /**
   * Returns a string that uniquely identifies this instance for use in maps.
   * Internal use only. Treat this as an opaque string since its format might
   * change.
   * @internal
   * @return string
   */
  protected function getIndexName() {
    return "{$this->name}[{$this->id}]";
  }

  /**
   * @param SchemaItem $item
   * @param string $name
   */
  public function addName(SchemaItem $item, string $name) {
    $this->names[$item->getIndexName()] = $name;
  }

  /**
   * @param SchemaItem $item
   * @param string $label
   */
  public function addLabel(SchemaItem $item, string $label) {
    $this->labels[$item->getIndexName()] = $label;
  }

  /**
   * Adds the given SchemaItem
   * @param SchemaItem $item
   */
  public function addRelation(SchemaItem $item, Relation $relation) {
    $this->labels[$item->getIndexName()] = $relation;
  }

  /**
   * Get the name for this item in the context of the given item. Defaults
   * to the local name if no alias has been stored
   * @param SchemaItem|null $item
   * @return string
   */
  public function name(SchemaItem $item=null) {
    return $item ? $this->names[$item->name] : $this->name;
  }

  /**
   * Get the label for this item in the context of the given item. Defaults
   * to the local label if no alias has been stored
   * @param SchemaItem|null $item
   * @return string
   */
  public function label(SchemaItem $item=null) {
    return $item ? $this->labels[$item->name] : $this->label;
  }

  /**
   * @param SchemaItem|null $item
   * @return Relation
   */
  public function relation(SchemaItem $item) {
    return $this->relations[$item->getIndexName()];
  }
}
