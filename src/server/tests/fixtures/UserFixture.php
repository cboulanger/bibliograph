<?php

namespace tests\fixtures;

use yii\test\ActiveFixture;

class UserFixture extends ActiveFixture
{
    public $modelClass = 'app\models\User';
    public $dataFile = '@tests/_data/user.php';
}