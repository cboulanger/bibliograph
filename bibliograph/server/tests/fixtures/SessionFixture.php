<?php

namespace tests\fixtures;

use yii\test\ActiveFixture;

class SessionFixture extends ActiveFixture
{
    public $modelClass = 'app\models\Session';
    public $dataFile = '@tests/_data/session.php';
}