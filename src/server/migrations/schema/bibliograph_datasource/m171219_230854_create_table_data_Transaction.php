<?php

namespace app\migrations\schema\bibliograph_datasource;

use lib\migrations\Migration;

class m171219_230854_create_table_data_Transaction extends Migration
{
  public function safeUp()
  {
    $tableOptions = $this->getTableOptions();

    $this->createTable('{{%data_Transaction}}', [
      'id' => $this->integer(11)->notNull()->append('AUTO_INCREMENT PRIMARY KEY'),
      'created' => $this->timestamp(),
      'modified' => $this->timestamp()->notNull()->defaultExpression('CURRENT_TIMESTAMP'),
      'datasource' => $this->string(50),
      'class' => $this->string(100),
      'transactionId' => $this->integer(11)->defaultValue(0),
    ], $tableOptions);

    $this->createIndex('datasource_class_index', '{{%data_Transaction}}', ['datasource', 'class'], true);
    return true;
  }

  public function safeDown()
  {
    $this->dropTable('{{%data_Transaction}}');
    return true;
  }
}
