<?php

namespace app\modules\zotero\models;

use app\modules\zotero\Schema;
use lib\models\IHasSchema;

class Item
  extends Model
  implements IHasSchema
{

  /**
   * @var Schema
   */
  private static $schema;

  /**
   * @overridden
   * @return array|\lib\schema\Field[]
   */
  public function attributes() {
    return self::getSchema()->fields;
  }

  /**
   * @return Schema
   */
  public static function getSchema() : Schema {
    if (!self::$schema) {
      self::$schema = new Schema();
    }
    return self::$schema;
  }
}
