<?php

namespace app\modules\zotero\models;
use lib\models\IHasDatasource;
use lib\models\ModelDatasourceTrait;

abstract class Model
  extends \yii\base\Model
  implements IHasDatasource
{
  use ModelDatasourceTrait;
}
