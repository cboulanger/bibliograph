<?php

namespace app\migrations\schema;

use lib\migrations\Migration;

class m171219_230853_create_table_data_User extends Migration
{
  public function safeUp()
  {
    $tableOptions = $this->getTableOptions();

    $this->createTable('{{%data_User}}', [
      'id' => $this->integer(11)->notNull()->append('AUTO_INCREMENT PRIMARY KEY'),
      'namedId' => $this->string(50),
      'created' => $this->timestamp()->null(),
      'modified' => $this->timestamp()->null(),
      'name' => $this->string(100),
      'password' => $this->string(50),
      'email' => $this->string(255),
      'anonymous' => $this->smallInteger(1)->notNull()->defaultValue(0),
      'ldap' => $this->smallInteger(1)->notNull()->defaultValue(0),
      'active' => $this->smallInteger(1)->notNull()->defaultValue(1),
      'lastAction' => $this->timestamp()->null(),
      'confirmed' => $this->smallInteger(1)->notNull()->defaultValue(0),
      'online' => $this->smallInteger(1)->notNull()->defaultValue(1),
    ], $tableOptions);

    $this->createIndex('unique_namedId', '{{%data_User}}', 'namedId', true);
    return true;
  }

  public function safeDown()
  {
    $this->dropTable('{{%data_User}}');
    return true;
  }
}
