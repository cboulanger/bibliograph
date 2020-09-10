<?php

namespace lib\schema;

use yii\base\BaseObject;
use yii\base\InvalidArgumentException;

/**
 * Class SchemaItem
 * @package lib\schema
 * @property string $name The name of the Schema Item, must be unique at the class level
 */
class SchemaItem extends BaseObject {

  /**
   * The static instance cache
   * @var static[]
   */
  private static array $instances=[];

  /**
   * Mappings of this item to other items.
   * @var array
   */
  private array $mappings = [];

  /**
   * The main name of the type, which acts as its unique id.
   * @var string
   */
  public string $name;

  /**
   * The english language label of the schema item, which can be translate to
   * other languages
   * @var string
   */
  public string $label;

  /**
   * The name of the field in other contexts, if different from its name
   * @var array
   */
  protected array $names = [];

  /**
   * The english label of the field in other contexts, if different from its label
   * @var array
   */
  protected array $labels = [];


  /**
   * Returns a singleton instance for the given name, creating it if it does
   * not exist already
   * @param string $name
   * @return static
   */
  static public function getInstance($name) {
    if (!isset(static::$instances[$name])) {
      return static::createInstance(['name' => $name]);
    }
    return static::$instances[$name];
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
    $name = $config['name'];
    static::$instances[$name] = new static($config);
    return static::$instances[$name];
  }

  public function __construct($config = [])
  {
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
   * @param SchemaItem $itemType
   * @param string $name
   */
  public function setNames(SchemaItem $itemType, string $name) {
    $this->names[$itemType->name] = $name;
  }

  /**
   * @param SchemaItem $itemType
   * @param string $label
   */
  public function setLabels(SchemaItem $itemType, string $label) {
    $this->labels[$itemType->name] = $label;
  }

  /**
   * @param ItemType|null $itemType
   * @return string
   */
  public function name(ItemType $itemType=null) {
    return $itemType ? $this->names[$itemType->name] : $this->name;
  }

  /**
   * @param ItemType|null $itemType
   * @return string
   */
  public function label(ItemType $itemType=null) {
    return $itemType ? $this->labels[$itemType->name] : $this->label;
  }

  public function mapTo(SchemaItem $item) {

  }
}
