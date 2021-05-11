<?php

namespace app\migrations\schema;

use lib\components\MigrationException;
use lib\migrations\Migration;

/**
 * Class M180224070636_create_table_data_schema
 */
class M180224070636_create_table_data_schema extends Migration
{
  public function safeUp()
  {
    $tableOptions = $this->getTableOptions();

    $this->createTable('{{%data_Schema}}', [
      'id' => $this->integer(11)->notNull()->append('AUTO_INCREMENT PRIMARY KEY'),
      'namedId' => $this->string(50),
      'created' => $this->timestamp(),
      'modified' => $this->timestamp()->notNull()->defaultExpression('CURRENT_TIMESTAMP'),
      'class' => $this->string(100),
      'name' => $this->string(100),
      'description' => $this->string(255),
      'active' => $this->smallInteger(1)->notNull()->defaultValue(1),
      'protected' => $this->smallInteger(1)->notNull()->defaultValue(0)
    ], $tableOptions);

    $this->createIndex('unique_namedId', '{{%data_Schema}}', 'namedId', true);
    $this->update('{{data_Datasource}}',['schema' => 'bibliograph_datasource'],['schema'=>'bibliograph.schema.bibliograph2']);
    $this->update('{{data_Datasource}}',['schema' => 'file'],['schema'=>'qcl.schema.filesystem.local']);
    $this->delete('{{data_Datasource}}',['schema'=>'bibliograph.schema.z3950']);

    $sql = "
      SELECT concat('DROP TABLE `', `TABLE_NAME`, '`;') AS 'sql'
      FROM INFORMATION_SCHEMA.TABLES 
      WHERE TABLE_NAME LIKE 'z3950_%'
      AND TABLE_SCHEMA = DATABASE();
     ";
    $rows = $this->db->createCommand($sql)->query()->readAll();
    foreach ($rows as $row) {
      $this->execute($row['sql']);
    }
    return true;
  }

  public function safeDown()
  {
    echo "M180224070636_create_table_data_schema cannot be reverted.\n";

    return false;
  }
}
