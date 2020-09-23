<?php

namespace tests\fixtures;

use yii\test\ActiveFixture;

class UserRoleFixture extends ActiveFixture
{
    public $modelClass = 'app\models\User_Role';
    public $dataFile = APP_TESTS_DIR . '/_data/user_role.php';
}
