<?php

namespace tests\fixtures;

use yii\test\ActiveFixture;

class UserConfigFixture extends ActiveFixture
{
    public $modelClass = 'app\models\UserConfig';
    public $dataFile = APP_TESTS_DIR . '/_data/userconfig.php';
}
