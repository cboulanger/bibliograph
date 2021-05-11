<?php

namespace app\migrations\schema;

use lib\migrations\Migration;

class m171219_230853_create_table_data_Permission extends Migration
{
  public function safeUp()
  {
    $tableOptions = $this->getTableOptions();

    $this->createTable('{{%data_Permission}}', [
      'id' => $this->integer(11)->notNull()->append('AUTO_INCREMENT PRIMARY KEY'),
      'namedId' => $this->string(50),
      'created' => $this->timestamp(),
      'modified' => $this->timestamp()->notNull()->defaultExpression('CURRENT_TIMESTAMP'),
      'name' => $this->string(100),
      'description' => $this->string(255),
      'active' => $this->smallInteger(1)->notNull()->defaultValue(1),
    ], $tableOptions);

    $this->createIndex('unique_namedId', '{{%data_Permission}}', 'namedId', true);
    return true;
  }

  public function safeDown()
  {
    $this->dropTable('{{%data_Permission}}');
    return true;
  }
}
