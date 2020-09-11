<?php

namespace lib\schema;


use InvalidArgumentException;

/**
 * Class Field
 * @package lib\schema
 * @property Field $parent
 * @property Field[] $children
 */
class Field extends SchemaItem {

  /**
   * If the field is a subfield, the parent is stored here
   * @var Field
   */
  protected $parent;

  /**
   * The child fields, if any
   * @var Field[]
   */
  protected $children = [];

  /**
   * The item types the field belongs to
   * @var ItemType[]
   */
  protected $itemTypes;

  /**
   * @param ItemType $type
   */
  public function addItemType(ItemType $type) {
    if (in_array($type, $this->itemTypes)) {
      throw new InvalidArgumentException("Type '{$type->name}' has already been added");
    }
    $this->itemTypes[] = $type;
  }

  /**
   * @param Field $field
   */
  public function setParent(Field $field) {
    $this->parent = $field;
  }

  /**
   * @return Field
   */
  public function getParent(): Field {
    return $this->parent;
  }

  /**
   * @param Field $field
   */
  public function addChild(Field $field) {
    $field->parent = $this;
    $this->children[] = $field;
  }

  /**
   * @return Field[]
   */
  public function getChildren() {
    return $this->children;
  }
}
