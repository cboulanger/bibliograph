<?php

namespace tests\fixtures;

use yii\test\ActiveFixture;

class PermissionRoleFixture extends ActiveFixture
{
    public $modelClass = 'app\models\Permission_Role';
    public $dataFile = APP_TESTS_DIR . '/_data/permission_role.php';
}
