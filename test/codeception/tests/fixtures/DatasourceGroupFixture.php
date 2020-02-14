<?php

namespace tests\fixtures;

use yii\test\ActiveFixture;

class DatasourceGroupFixture extends ActiveFixture
{
    public $modelClass = 'app\models\Datasource_Group';
    public $dataFile = APP_TESTS_DIR . '/_data/datasource_group.php';
}
