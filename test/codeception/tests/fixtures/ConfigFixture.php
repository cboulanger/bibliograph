<?php

namespace tests\fixtures;

use yii\test\ActiveFixture;

class ConfigFixture extends ActiveFixture
{
    public $modelClass = 'app\models\Config';
    public $dataFile = APP_TESTS_DIR . '/_data/config.php';
}
