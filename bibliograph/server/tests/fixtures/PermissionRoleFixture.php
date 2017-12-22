<?php

namespace tests\fixtures;

use yii\test\ActiveFixture;

class PermissionRoleFixture extends ActiveFixture
{
    public $modelClass = 'app\models\Permission_Role';
    public $dataFile = '@tests/_data/permission_role.php';
}