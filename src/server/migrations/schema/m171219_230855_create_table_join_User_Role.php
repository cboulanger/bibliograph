<?php

namespace app\migrations\schema;

use lib\migrations\Migration;

class m171219_230855_create_table_join_User_Role extends Migration
{
  public function safeUp()
  {
    $tableOptions = $this->getTableOptions();

    $this->createTable('{{%join_User_Role}}', [
      'id' => $this->integer(11)->notNull()->append('AUTO_INCREMENT PRIMARY KEY'),
      'created' => $this->timestamp(),
      'modified' => $this->timestamp()->notNull()->defaultExpression('CURRENT_TIMESTAMP'),
      'UserId' => $this->integer(11),
      'RoleId' => $this->integer(11),
      'GroupId' => $this->integer(11),
    ], $tableOptions);

    $this->createIndex('index_User_Role', '{{%join_User_Role}}', ['GroupId', 'RoleId', 'UserId'], true);
    return true;
  }

  public function safeDown()
  {
    $this->dropTable('{{%join_User_Role}}');
    return true;
  }
}
