<?php

namespace app\migrations\schema;

use lib\migrations\Migration;

class m171219_230853_create_table_data_Messages extends Migration
{
  public function safeUp()
  {
    $tableOptions = $this->getTableOptions();

    $this->createTable('{{%data_Messages}}', [
      'id' => $this->integer(11)->notNull()->append('AUTO_INCREMENT PRIMARY KEY'),
      'created' => $this->timestamp(),
      'modified' => $this->timestamp()->notNull()->defaultExpression('CURRENT_TIMESTAMP'),
      'name' => $this->string(100),
      'data' => $this->binary(),
      'SessionId' => $this->integer(11),
    ], $tableOptions);
    return true;
  }

  public function safeDown()
  {
    $this->dropTable('{{%data_Messages}}');
  }
}
