<?php

namespace app\migrations\schema;

use lib\migrations\Migration;

class m171219_230854_create_table_join_Group_User extends Migration
{
  public function safeUp()
  {
    $tableOptions = $this->getTableOptions();

    $this->createTable('{{%join_Group_User}}', [
      'id' => $this->integer(11)->notNull()->append('AUTO_INCREMENT PRIMARY KEY'),
      'created' => $this->timestamp(),
      'modified' => $this->timestamp()->notNull()->defaultExpression('CURRENT_TIMESTAMP'),
      'UserId' => $this->integer(11),
      'GroupId' => $this->integer(11),
    ], $tableOptions);

    $this->createIndex('index_Group_User', '{{%join_Group_User}}', ['GroupId', 'UserId'], true);
    return true;
  }

  public function safeDown()
  {
    $this->dropTable('{{%join_Group_User}}');
    return true;
  }
}
