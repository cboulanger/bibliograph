<?php

namespace tests\fixtures;

use yii\test\ActiveFixture;

class DatasourceGroupFixture extends ActiveFixture
{
    public $modelClass = 'app\models\Datasource_Group';
    public $dataFile = '@tests/_data/datasource_group.php';
}