<?php

namespace tests\fixtures;

use yii\test\ActiveFixture;

class DatasourceRoleFixture extends ActiveFixture
{
    public $modelClass = 'app\models\Datasource_Role';
    public $dataFile = '@tests/_data/datasource_role.php';
}