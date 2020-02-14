<?php

namespace tests\fixtures;

use yii\test\ActiveFixture;

class DatasourceSchemaFixture extends ActiveFixture
{
    public $modelClass = 'app\models\Schema';
    public $dataFile = APP_TESTS_DIR . '/_data/datasource_schema.php';
}
