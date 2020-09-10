<?php

namespace lib\schema;

use InvalidArgumentException;
use yii\base\BaseObject;

class Schema extends SchemaItem {

  /**
   * @var ItemType[]
   */
  protected array $itemTypes;

  /**
   * @param ItemType $itemType
   */
  public function addItemType(ItemType $itemType, $name) {
    if (in_array($itemType, $this->itemTypes)) {
      throw new InvalidArgumentException("Type '{$itemType->name}' has already been added");
    }
    $itemType->addSchema($this);
    $itemType->
    $this->itemTypes[] = $itemType;
  }
}
