<?php

namespace lib\schema;

use JsonSerializable;
use Yii;

/**
 * Class Field
 * @package lib\schema
 * @property Field $parent
 * @property Field[] $children readonly
 * @property Field $alias
 * @property ItemType[] $itemTypes readonly
 */
class Field
  extends SchemaItem
  implements JsonSerializable
{

  /**
   * @var Field
   */
  protected $alias;

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
  protected $itemTypes = [];

  /**
   * @param ItemType $itemType
   */
  public function addItemType(ItemType $itemType) {
    if (in_array($itemType, $this->itemTypes)) {
      Yii::debug("itemType '{$itemType->name}' has already been added to field '{$this->name}'");
    }
    $this->itemTypes[] = $itemType;
  }

  /**
   * @return ItemType[]
   */
  public function getItemTypes() {
    return $this->itemTypes;
  }

  /**
   * @param Field $field
   */
  public function setAlias(Field $field) {
    $this->alias = $field;
  }

  /**
   * @return Field
   */
  public function getAlias() : Field {
    return $this->alias;
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

  public function jsonSerialize()
  {
    return [
      'name'      => $this->name,
      'label'     => $this->label,
      'children'  => $this->children,
      'alias'     => $this->alias
    ];
  }
}
