<?php

namespace tests\fixtures;

use yii\test\ActiveFixture;

class ConfigFixture extends ActiveFixture
{
    public $modelClass = 'app\models\Config';
    public $dataFile = '@tests/_data/config.php';
}