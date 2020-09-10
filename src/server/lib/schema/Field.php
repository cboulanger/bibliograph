<?php

namespace lib\schema;


use InvalidArgumentException;

class Field extends SchemaItem {

  /**
   * If the field is a subfield, the parent is stored here
   * @var Field
   */
  public Field $parent;

  /**
   * The child fields, if any
   * @var Field[]
   */
  public array $children = [];

  /**
   * An array of field aliases
   * @var Field[]
   */
  public array $aliases;

  /**
   * The item types the field belongs to
   * @var ItemType[]
   */
  public array $itemTypes;

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
  public function addChild(Field $field) {
    $field->parent = $this;
    $this->children[] = $field;
  }
}
