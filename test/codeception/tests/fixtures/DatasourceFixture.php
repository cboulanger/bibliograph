<?php

namespace tests\fixtures;

use yii\test\ActiveFixture;

class DatasourceFixture extends ActiveFixture
{
    public $modelClass = 'app\models\Datasource';
    public $dataFile = APP_TESTS_DIR . '/_data/datasource.php';
}
