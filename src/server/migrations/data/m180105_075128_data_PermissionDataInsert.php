<?php
namespace app\migrations\data;
use yii\db\Schema;
use yii\db\Migration;

class m180105_075128_data_PermissionDataInsert extends Migration
{

    public function safeUp()
    {
        $this->batchInsert('{{%data_Permission}}',
                           ["id", "namedId", "name", "description", "active"],
                            [
    [
        'id' => '1',
        'namedId' => '*',
        'name' => null,
        'description' => 'Can do everything',
        'active' => '1',
    ],
    [
        'id' => '2',
        'namedId' => 'access.manage',
        'name' => null,
        'description' => 'Manage users, roles, permissions, and datasources',
        'active' => '1',
    ],
    [
        'id' => '3',
        'namedId' => 'application.reportBug',
        'name' => null,
        'description' => null,
        'active' => '1',
    ],
    [
        'id' => '4',
        'namedId' => 'config.default.edit',
        'name' => null,
        'description' => null,
        'active' => '1',
    ],
    [
        'id' => '5',
        'namedId' => 'config.key.add',
        'name' => null,
        'description' => null,
        'active' => '1',
    ],
    [
        'id' => '6',
        'namedId' => 'config.key.edit',
        'name' => null,
        'description' => null,
        'active' => '1',
    ],
    [
        'id' => '7',
        'namedId' => 'config.value.edit',
        'name' => null,
        'description' => null,
        'active' => '1',
    ],
    [
        'id' => '8',
        'namedId' => 'folder.add',
        'name' => null,
        'description' => null,
        'active' => '1',
    ],
    [
        'id' => '9',
        'namedId' => 'folder.delete',
        'name' => null,
        'description' => null,
        'active' => '1',
    ],
    [
        'id' => '10',
        'namedId' => 'folder.edit',
        'name' => null,
        'description' => null,
        'active' => '1',
    ],
    [
        'id' => '11',
        'namedId' => 'folder.move',
        'name' => null,
        'description' => null,
        'active' => '1',
    ],
    [
        'id' => '12',
        'namedId' => 'folder.remove',
        'name' => null,
        'description' => null,
        'active' => '1',
    ],
    [
        'id' => '13',
        'namedId' => 'plugin.manage',
        'name' => null,
        'description' => null,
        'active' => '1',
    ],
    [
        'id' => '14',
        'namedId' => 'reference.add',
        'name' => null,
        'description' => null,
        'active' => '1',
    ],
    [
        'id' => '15',
        'namedId' => 'reference.batchedit',
        'name' => null,
        'description' => null,
        'active' => '1',
    ],
    [
        'id' => '16',
        'namedId' => 'reference.delete',
        'name' => null,
        'description' => null,
        'active' => '1',
    ],
    [
        'id' => '17',
        'namedId' => 'reference.edit',
        'name' => null,
        'description' => null,
        'active' => '1',
    ],
    [
        'id' => '18',
        'namedId' => 'reference.export',
        'name' => null,
        'description' => null,
        'active' => '1',
    ],
    [
        'id' => '19',
        'namedId' => 'reference.import',
        'name' => null,
        'description' => null,
        'active' => '1',
    ],
    [
        'id' => '20',
        'namedId' => 'reference.move',
        'name' => null,
        'description' => null,
        'active' => '1',
    ],
    [
        'id' => '21',
        'namedId' => 'reference.remove',
        'name' => null,
        'description' => null,
        'active' => '1',
    ],
    [
        'id' => '22',
        'namedId' => 'reference.search',
        'name' => null,
        'description' => null,
        'active' => '1',
    ],
    [
        'id' => '23',
        'namedId' => 'reference.view',
        'name' => null,
        'description' => null,
        'active' => '1',
    ],
    [
        'id' => '24',
        'namedId' => 'system.manage',
        'name' => null,
        'description' => null,
        'active' => '1',
    ],
    [
        'id' => '25',
        'namedId' => 'system.menu.view',
        'name' => null,
        'description' => null,
        'active' => '1',
    ],
    [
        'id' => '26',
        'namedId' => 'trash.empty',
        'name' => null,
        'description' => null,
        'active' => '1',
    ]
]
        );
    }

    public function safeDown()
    {
        $this->truncateTable('{{%data_Permission}}');
    }
}
