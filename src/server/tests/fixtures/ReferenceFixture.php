<?php

namespace tests\fixtures;

use yii\test\ActiveFixture;

class ReferenceFixture extends ActiveFixture
{
    public $modelClass = 'app\models\Reference';
    public $dataFile = '@tests/_data/reference.php';
}