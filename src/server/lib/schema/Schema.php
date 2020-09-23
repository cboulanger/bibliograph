<?php

namespace lib\schema;

use InvalidArgumentException;
use JsonSerializable;
use yii\base\BaseObject;

/**
 * Class Schema
 * @package lib\schema
 * @property ItemType[] $itemTypes
 * @property Field[] $fields
 */
class Schema
  extends SchemaItem
  implements ISchema, JsonSerializable
{
  /**
   * @var ItemType[]
   */
  protected $itemTypes = [];

  /**
   * @param ItemType $itemType
   */
  public function addItemType(ItemType $itemType) {
    if (in_array($itemType, $this->itemTypes, true)) {
      throw new InvalidArgumentException("Type '{$itemType->name}' has already been added");
    }
    $itemType->addSchema($this);
    $this->itemTypes[] = $itemType;
  }

  /**
   * @return ItemType[]
   */
  public function getItemTypes() {
    return $this->itemTypes;
  }

  /**
   * @return Field[]
   */
  public function getFields() {
    $fields = [];
    foreach ($this->itemTypes as $itemType) {
      foreach ($itemType->fields as $field) {
        $fields[$field->getId()] = $field;
      }
    }
    return array_values($fields);
  }

  /**
   * @return array
   */
  public function jsonSerialize()
  {
    return [
      'name'        => $this->name,
      'label'       => $this->label,
      'itemTypes'   => $this->itemTypes,
      'fields'      => $this->fields
    ];
  }
}
