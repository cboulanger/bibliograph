<?php

namespace app\migrations\schema;

use lib\migrations\Migration;

class m171219_230854_create_table_join_Datasource_Role extends Migration
{
  public function safeUp()
  {
    $tableOptions = $this->getTableOptions();

    $this->createTable('{{%join_Datasource_Role}}', [
      'id' => $this->integer(11)->notNull()->append('AUTO_INCREMENT PRIMARY KEY'),
      'created' => $this->timestamp(),
      'modified' => $this->timestamp()->notNull()->defaultExpression('CURRENT_TIMESTAMP'),
      'DatasourceId' => $this->integer(11),
      'RoleId' => $this->integer(11),
    ], $tableOptions);

    $this->createIndex('index_Datasource_Role', '{{%join_Datasource_Role}}', ['DatasourceId', 'RoleId'], true);
    return true;
  }

  public function safeDown()
  {
    $this->dropTable('{{%join_Datasource_Role}}');
    return true;
  }
}
