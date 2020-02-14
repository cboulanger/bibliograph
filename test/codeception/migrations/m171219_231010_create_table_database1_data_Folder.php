<?php

namespace tests\migrations;

use yii\db\Migration;

class m171219_231010_create_table_database1_data_Folder extends Migration
{
  public function safeUp()
  {
    $tableOptions = null;
    if ($this->db->driverName === 'mysql') {
      $tableOptions = 'CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE=InnoDB';
    }

    $this->createTable('database1_data_Folder', [
      'id' => $this->integer(11)->notNull()->append('AUTO_INCREMENT PRIMARY KEY'),
      'created' => $this->timestamp(),
      'modified' => $this->timestamp()->notNull()->defaultExpression('CURRENT_TIMESTAMP'),
      'parentId' => $this->integer(11)->notNull()->defaultValue(0),
      'position' => $this->integer(11)->notNull()->defaultValue(0),
      'label' => $this->string(100),
      'type' => $this->string(20),
      'description' => $this->string(100),
      'searchable' => $this->smallInteger(1)->notNull()->defaultValue(1),
      'searchfolder' => $this->smallInteger(1)->notNull()->defaultValue(0),
      'query' => $this->string(255),
      'public' => $this->smallInteger(1)->notNull()->defaultValue(0),
      'opened' => $this->smallInteger(1)->notNull()->defaultValue(0),
      'locked' => $this->smallInteger(1)->notNull()->defaultValue(0),
      'path' => $this->string(100),
      'owner' => $this->string(30),
      'hidden' => $this->smallInteger()->notNull()->defaultValue(0),
      'createdBy' => $this->string(20),
      'markedDeleted' => $this->smallInteger(1)->notNull()->defaultValue(0),
      'childCount' => $this->smallInteger(1)->notNull()->defaultValue(0),
      'referenceCount' => $this->integer(11)->notNull()->defaultValue(0),
    ], $tableOptions);
  }

  public function safeDown()
  {
    $this->dropTable('database1_data_Folder');
  }

}
