<?php

namespace tests\fixtures;

use yii\test\ActiveFixture;

class GroupFixture extends ActiveFixture
{
    public $modelClass = 'app\models\Group';
    public $dataFile = '@tests/_data/group.php';
}