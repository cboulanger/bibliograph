<?php

namespace app\migrations\schema;

use yii\db\Migration;

class m171219_230854_create_table_join_Permission_Role extends Migration
{
  public function safeUp()
  {
    $tableOptions = null;
    if ($this->db->driverName === 'mysql') {
      $tableOptions = 'CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE=InnoDB';
    }

    $this->createTable('{{%join_Permission_Role}}', [
      'id' => $this->integer(11)->notNull()->append('AUTO_INCREMENT PRIMARY KEY'),
      'created' => $this->timestamp(),
      'modified' => $this->timestamp()->notNull()->defaultExpression('CURRENT_TIMESTAMP'),
      'RoleId' => $this->integer(11),
      'PermissionId' => $this->integer(11),
    ], $tableOptions);

    $this->createIndex('index_Permission_Role', '{{%join_Permission_Role}}', ['PermissionId', 'RoleId'], true);
    return true;
  }

  public function safeDown()
  {
    $this->dropTable('{{%join_Permission_Role}}');
    return true;
  }
}
