<?php

namespace tests\fixtures;

use yii\test\ActiveFixture;

class RoleSchemaFixture extends ActiveFixture
{
    public $modelClass = '\app\models\Role_Schema';
    public $dataFile = '@tests/_data/role_schema.php';
}