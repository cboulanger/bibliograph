<?php

namespace app\migrations\schema\bibliograph_datasource;

use lib\migrations\Migration;

class m171219_230854_create_table_join_Folder_Reference extends Migration
{
  public function safeUp()
  {
    $tableOptions = $this->getTableOptions();

    $this->createTable('{{%join_Folder_Reference}}', [
      'id' => $this->integer(11)->notNull()->append('AUTO_INCREMENT PRIMARY KEY'),
      'created' => $this->timestamp(),
      'modified' => $this->timestamp()->notNull()->defaultExpression('CURRENT_TIMESTAMP'),
      'FolderId' => $this->integer(11),
      'ReferenceId' => $this->integer(11),
    ], $tableOptions);

    $this->createIndex('index_Folder_Reference', '{{%join_Folder_Reference}}', ['FolderId', 'ReferenceId'], true);
    return true;
  }

  public function safeDown()
  {
    $this->dropTable('{{%join_Folder_Reference}}');
    return true;
  }
}
