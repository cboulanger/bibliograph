<?php

namespace app\modules\z3950\migrations;

use yii\db\Migration;

/**
 * Class M180307221446_create_table_data_Result
 */
class M180307221446_create_table_data_Result extends Migration
{
  public function safeUp()
  {
    $tableOptions = null;
    if ($this->db->driverName === 'mysql') {
      $tableOptions = 'CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE=InnoDB';
    }
    $this->createTable('{{%data_Result}}', [
      'id' => $this->integer(11)->notNull()->append('AUTO_INCREMENT PRIMARY KEY'),
      'created' => $this->timestamp(),
      'modified' => $this->timestamp()->notNull()->defaultExpression('CURRENT_TIMESTAMP'),
      'firstRow' => $this->integer(11),
      'lastRow' => $this->integer(11),
      'firstRecordId' => $this->integer(11),
      'lastRecordId' => $this->integer(11),
      'SearchId' => $this->integer(11),
    ], $tableOptions);
  }

  public function safeDown()
  {
    $this->dropTable('{{%data_Result}}');
  }
}
