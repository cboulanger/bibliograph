<?php

namespace lib\schema;

use InvalidArgumentException;
use yii\base\BaseObject;

/**
 * Class Type
 * @package lib\schema
 * @property Field[] $fields
 */
class ItemType extends SchemaItem {

  /**
   * The schemas the itemType belangs to
   * @var Schema[]
   */
  protected array $schemas;

  /**
   * The fields of this type
   * @var Field[]
   */
  protected array $fields;

  /**
   * @return Schema[]
   */
  public function getSchemas(){
    return $this->schemas;
  }

  /**
   * @return Field[]
   */
  public function getFields() {
    return $this->fields;
  }

  /**
   * @param Schema $schema
   */
  public function addSchema(Schema $schema){
    if (in_array($schema, $this->schemas)) {
      throw new InvalidArgumentException("Schema '{$schema->name}' has already been added");
    }
    $this->schemas[] = $schema;
  }

  /**
   * @param Field $field
   */
  public function addField(Field $field){
    if (in_array($field, $this->fields)) {
      throw new InvalidArgumentException("Field '{$field->name}' has already been added");
    }
    $field->addItemType($this);
    $this->fields[] = $field;
  }
}
