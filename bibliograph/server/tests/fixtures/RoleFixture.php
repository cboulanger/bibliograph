<?php

namespace tests\fixtures;

use yii\test\ActiveFixture;

class RoleFixture extends ActiveFixture
{
    public $modelClass = 'app\models\Role';
    public $dataFile = '@tests/_data/role.php';
}