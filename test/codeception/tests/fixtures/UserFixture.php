<?php

namespace tests\fixtures;

use yii\test\ActiveFixture;

class UserFixture extends ActiveFixture
{
    public $modelClass = 'app\models\User';
    public $dataFile = APP_TESTS_DIR . '/_data/user.php';
}
