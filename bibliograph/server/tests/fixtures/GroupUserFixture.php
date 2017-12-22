<?php

namespace tests\fixtures;

use yii\test\ActiveFixture;

class GroupUserFixture extends ActiveFixture
{
    public $modelClass = 'app\models\Group_User';
    public $dataFile = '@tests/_data/group_user.php';
}