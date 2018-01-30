<?php

use yii\db\Schema;
use yii\db\Migration;

class m180105_075600_join_Permission_RoleDataInsert extends Migration
{

    public function safeUp()
    {
        $this->batchInsert('{{%join_Permission_Role}}',
                           [ "RoleId", "PermissionId"],
                            [
    [
        
        'RoleId' => '1',
        'PermissionId' => '1',
    ],
    [
        
        'RoleId' => '1',
        'PermissionId' => '2',
    ],
    [
        
        'RoleId' => '4',
        'PermissionId' => '3',
    ],
    [
        
        'RoleId' => '1',
        'PermissionId' => '4',
    ],
    [
        
        'RoleId' => '1',
        'PermissionId' => '5',
    ],
    [
        
        'RoleId' => '1',
        'PermissionId' => '6',
    ],
    [
        
        'RoleId' => '1',
        'PermissionId' => '7',
    ],
    [
        
        'RoleId' => '2',
        'PermissionId' => '7',
    ],
    [
        
        'RoleId' => '3',
        'PermissionId' => '7',
    ],
    [
        
        'RoleId' => '4',
        'PermissionId' => '7',
    ],
    [
        
        'RoleId' => '4',
        'PermissionId' => '8',
    ],
    [
        
        'RoleId' => '3',
        'PermissionId' => '9',
    ],
    [
        
        'RoleId' => '3',
        'PermissionId' => '10',
    ],
    [
        
        'RoleId' => '4',
        'PermissionId' => '11',
    ],
    [
        
        'RoleId' => '4',
        'PermissionId' => '12',
    ],
    [
        
        'RoleId' => '1',
        'PermissionId' => '13',
    ],
    [
        
        'RoleId' => '4',
        'PermissionId' => '14',
    ],
    [
        
        'RoleId' => '3',
        'PermissionId' => '15',
    ],
    [
        
        'RoleId' => '4',
        'PermissionId' => '16',
    ],
    [
        
        'RoleId' => '4',
        'PermissionId' => '17',
    ],
    [
        
        'RoleId' => '2',
        'PermissionId' => '18',
    ],
    [
        
        'RoleId' => '4',
        'PermissionId' => '18',
    ],
    [
        
        'RoleId' => '4',
        'PermissionId' => '19',
    ],
    [
        
        'RoleId' => '4',
        'PermissionId' => '20',
    ],
    [
        
        'RoleId' => '4',
        'PermissionId' => '21',
    ],
    [
        
        'RoleId' => '2',
        'PermissionId' => '22',
    ],
    [
        
        'RoleId' => '4',
        'PermissionId' => '22',
    ],
    [
        
        'RoleId' => '2',
        'PermissionId' => '23',
    ],
    [
        
        'RoleId' => '4',
        'PermissionId' => '23',
    ],
    [
        
        'RoleId' => '3',
        'PermissionId' => '25',
    ],
    [
        
        'RoleId' => '3',
        'PermissionId' => '26',
    ],
    [
        
        'RoleId' => '1',
        'PermissionId' => '27',
    ],
    [
        
        'RoleId' => '1',
        'PermissionId' => '28',
    ],
    [
        
        'RoleId' => '1',
        'PermissionId' => '29',
    ],
    [
        
        'RoleId' => '1',
        'PermissionId' => '30',
    ],
    [
        
        'RoleId' => '3',
        'PermissionId' => '30',
    ],
]
        );
    }

    public function safeDown()
    {
        $this->truncateTable('{{%join_Permission_Role}}');
    }
}
