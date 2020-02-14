<?php

namespace tests\fixtures;

use yii\test\ActiveFixture;

class PermissionFixture extends ActiveFixture
{
    public $modelClass = 'app\models\Permission';
    public $dataFile = APP_TESTS_DIR . '/_data/permission.php';
}
