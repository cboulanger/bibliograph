<?php

namespace lib\schema;

use InvalidArgumentException;
use JsonSerializable;
use yii\base\BaseObject;

/**
 * Class Schema
 * @package lib\schema
 * @property ItemType[] $itemTypes
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
    if (in_array($itemType, $this->itemTypes)) {
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

  public function jsonSerialize()
  {
    return [
      'name'    => $this->name,
      'label'   => $this->label,
      'itemTypes'  => $this->itemTypes
    ];
  }
}
