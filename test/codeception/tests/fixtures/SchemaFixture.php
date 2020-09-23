<?php

namespace tests\fixtures;

use yii\test\ActiveFixture;

class SchemaFixture extends ActiveFixture
{
    public $modelClass = '\app\models\Schema';
    public $dataFile = APP_TESTS_DIR . '/_data/schema.php';
}
