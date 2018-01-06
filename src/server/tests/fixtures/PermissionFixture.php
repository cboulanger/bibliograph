<?php

namespace tests\fixtures;

use yii\test\ActiveFixture;

class PermissionFixture extends ActiveFixture
{
    public $modelClass = 'app\models\Permission';
    public $dataFile = '@tests/_data/permission.php';
}