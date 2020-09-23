<?php

namespace tests\fixtures;

use yii\test\ActiveFixture;

class SessionFixture extends ActiveFixture
{
    public $modelClass = 'app\models\Session';
    public $dataFile = APP_TESTS_DIR . '/_data/session.php';
}
