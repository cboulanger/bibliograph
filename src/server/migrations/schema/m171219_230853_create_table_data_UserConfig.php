<?php

namespace app\migrations\schema;

use lib\migrations\Migration;

class m171219_230853_create_table_data_UserConfig extends Migration
{
  public function safeUp()
  {
    $tableOptions = $this->getTableOptions();

    $this->createTable('{{%data_UserConfig}}', [
      'id' => $this->integer(11)->notNull()->append('AUTO_INCREMENT PRIMARY KEY'),
      'value' => $this->string(255),
      'created' => $this->timestamp(),
      'modified' => $this->timestamp()->notNull()->defaultExpression('CURRENT_TIMESTAMP'),
      'UserId' => $this->integer(11),
      'ConfigId' => $this->integer(11),
    ], $tableOptions);
    return true;
  }

  public function safeDown()
  {
    $this->dropTable('{{%data_UserConfig}}');
    return true;
  }
}
