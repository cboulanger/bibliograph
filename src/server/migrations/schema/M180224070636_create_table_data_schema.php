<?php

namespace app\migrations\schema;

use yii\db\Migration;

/**
 * Class M180224070636_create_table_data_schema
 */
class M180224070636_create_table_data_schema extends Migration
{
  public function safeUp()
  {
    $tableOptions = null;
    if ($this->db->driverName === 'mysql') {
      $tableOptions = 'CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE=InnoDB';
    }

    $this->createTable('{{%data_Schema}}', [
      'id' => $this->integer(11)->notNull()->append('AUTO_INCREMENT PRIMARY KEY'),
      'namedId' => $this->string(50),
      'created' => $this->timestamp(),
      'modified' => $this->timestamp()->notNull()->defaultExpression('CURRENT_TIMESTAMP'),
      'class' => $this->string(100),
      'name' => $this->string(100),
      'description' => $this->string(255),
      'active' => $this->smallInteger(1)->notNull()->defaultValue('1'),
      'protected' => $this->integer(1)->notNull()->defaultValue('0')
    ], $tableOptions);

    $this->createIndex('unique_namedId', '{{%data_Schema}}', 'namedId', true);
    $this->update('{{data_Datasource}}',['schema' => 'bibliograph'],['schema'=>'bibliograph.schema.bibliograph2']);
    $this->update('{{data_Datasource}}',['schema' => 'file'],['schema'=>'qcl.schema.filesystem.local']);
    $this->update('{{data_Datasource}}',['schema' => 'z3950'],['schema'=>'bibliograph.schema.z3950']);
  }

  public function safeDown()
  {
    $this->dropTable('{{%data_Schema}}');
    $this->update('{{data_Datasource}}',['schema'=>'bibliograph.schema.bibliograph2'],['schema' => 'bibliograph']);
    $this->update('{{data_Datasource}}',['schema'=>'qcl.schema.filesystem.local'],['schema' => 'file']);
    $this->update('{{data_Datasource}}',['schema'=>'bibliograph.schema.z3950'],['schema' => 'z3950']);
  }
}
