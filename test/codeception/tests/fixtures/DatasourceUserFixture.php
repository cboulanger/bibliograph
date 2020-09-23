<?php

namespace tests\fixtures;

use yii\test\ActiveFixture;

class DatasourceUserFixture extends ActiveFixture
{
    public $modelClass = 'app\models\Datasource_User';
    public $dataFile = APP_TESTS_DIR . '/_data/datasource_user.php';
}
