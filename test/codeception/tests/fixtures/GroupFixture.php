<?php

namespace tests\fixtures;

use yii\test\ActiveFixture;

class GroupFixture extends ActiveFixture
{
    public $modelClass = 'app\models\Group';
    public $dataFile = APP_TESTS_DIR . '/_data/group.php';
}
