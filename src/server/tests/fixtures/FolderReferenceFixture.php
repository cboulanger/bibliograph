<?php

namespace tests\fixtures;

use yii\test\ActiveFixture;

class FolderReferenceFixture extends ActiveFixture
{
    public $modelClass = 'app\models\Folder_Reference';
    public $dataFile = '@tests/_data/folder_reference.php';
}