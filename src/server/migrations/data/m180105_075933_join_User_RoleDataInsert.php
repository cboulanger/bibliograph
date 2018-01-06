<?php

use yii\db\Schema;
use yii\db\Migration;

class m180105_075933_join_User_RoleDataInsert extends Migration
{

    public function safeUp()
    {
        $this->batchInsert('{{%join_User_Role}}',
                           ["UserId", "RoleId", "GroupId"],
                            [
    [
        
        'UserId' => '1',
        'RoleId' => '1',
        'GroupId' => null,
    ],
    [
        
        'UserId' => '1',
        'RoleId' => '3',
        'GroupId' => null,
    ],
    [
        
        'UserId' => '2',
        'RoleId' => '3',
        'GroupId' => null,
    ],
    [
        
        'UserId' => '1',
        'RoleId' => '4',
        'GroupId' => null,
    ],
    [
        
        'UserId' => '2',
        'RoleId' => '4',
        'GroupId' => null,
    ],
    [
        
        'UserId' => '3',
        'RoleId' => '4',
        'GroupId' => null,
    ],
]
        );
    }

    public function safeDown()
    {
        $this->truncateTable('{{%join_User_Role}} CASCADE');
    }
}
