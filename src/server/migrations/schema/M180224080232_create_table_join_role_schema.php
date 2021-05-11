<?php

namespace app\migrations\schema;

use lib\migrations\Migration;

/**
 * Class M180224080232_create_table_join_role_schema
 */
class M180224080232_create_table_join_role_schema extends Migration
{
  public function safeUp()
  {
    $tableOptions = $this->getTableOptions();

    $this->createTable('{{%join_Role_Schema}}', [
      'created' => $this->timestamp(),
      'modified' => $this->timestamp()->notNull()->defaultExpression('CURRENT_TIMESTAMP'),
      'RoleId' => $this->integer(11),
      'SchemaId' => $this->integer(11),
    ], $tableOptions);

    $this->createIndex('index_Role_Schema', '{{%join_Role_Schema}}', ['RoleId','SchemaId'], true);
    return true;
  }

  public function safeDown()
  {
    $this->dropTable('{{%join_Role_Schema}}');
    return true;
  }
}
