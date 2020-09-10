<?php

namespace app\modules\zotero\models;

use app\modules\zotero\Schema;
use lib\models\IHasSchema;
use yii\base\BaseObject;

class Item
  extends BaseObject
  implements IHasSchema {

  /**
   * @return Schema
   */
  public static function getSchema() : Schema {
    return new Schema();
  }
}
