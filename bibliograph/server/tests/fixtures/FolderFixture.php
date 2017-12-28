<?php

namespace tests\fixtures;

use yii\test\ActiveFixture;

class FolderFixture extends ActiveFixture
{
    public $modelClass = 'app\models\Folder';
    public $dataFile = '@tests/_data/folder.php';
}