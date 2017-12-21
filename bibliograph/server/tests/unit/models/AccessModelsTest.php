<?php

namespace app\tests\unit\models;

class AccessModelsTest extends \Codeception\Test\Unit
{   
    public function fixtures()
    {
        return [
            'user' => [
                'class' => \tests\fixtures\UserFixture::className(),
                'dataFile' => '@tests/_data/user.php',
            ],            
            'group' => [
                'class' => \tests\fixtures\GroupFixture::className(),
                'dataFile' => '@tests/_data/group.php',
            ],
            'role' => [
                'class' => \tests\fixtures\RoleFixture::className(),
                'dataFile' => '@tests/_data/role.php',
            ],
            'permission' => [
                'class' => \tests\fixtures\PermissionFixture::className(),
                'dataFile' => '@tests/_data/permission.php',
            ],
            'config' => [
                'class' => \tests\fixtures\ConfigFixture::className(),
                'dataFile' => '@tests/_data/config.php',
            ],
            'session' => [
                'class' => \tests\fixtures\SessionFixture::className(),
                'dataFile' => '@tests/_data/session.php',
            ],                                                
        ];
    }

    // ...test methods...
}