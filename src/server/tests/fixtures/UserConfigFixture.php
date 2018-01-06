<?php

namespace tests\fixtures;

use yii\test\ActiveFixture;

class UserConfigFixture extends ActiveFixture
{
    public $modelClass = 'app\models\UserConfig';
    public $dataFile = '@tests/_data/userconfig.php';
}