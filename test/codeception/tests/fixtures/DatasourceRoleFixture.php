<?php

namespace tests\fixtures;

use yii\test\ActiveFixture;

class DatasourceRoleFixture extends ActiveFixture
{
    public $modelClass = 'app\models\Datasource_Role';
    public $dataFile = APP_TESTS_DIR . '/_data/datasource_role.php';
}
