<?php

namespace app\modules\zotero\models;

use app\modules\zotero\Schema;
use lib\models\IHasSchema;
use yii\base\BaseObject;
use yii\base\Model as ModelAlias;

class Item
  extends ModelAlias
  implements IHasSchema
{

  private static $schema;

  public function attributes()
  {
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
