<?php

namespace app\modules\z3950\migrations;

use yii\db\Migration;

/**
 * Class M180307221333_create_table_data_Search
 */
class M180307221333_create_table_data_Search extends Migration
{

  public function safeUp()
  {
    $tableOptions = null;
    if ($this->db->driverName === 'mysql') {
      $tableOptions = 'CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE=InnoDB';
    }

    $this->createTable('{{%data_Search}}', [
      'id' => $this->integer(11)->notNull()->append('AUTO_INCREMENT PRIMARY KEY'),
      'created' => $this->timestamp(),
      'modified' => $this->timestamp()->notNull()->defaultExpression('CURRENT_TIMESTAMP'),
      'query' => $this->string(500),
      'datasource' => $this->string(50),
      'hits' => $this->integer(11)->notNull()->defaultValue(0),
      'UserId' => $this->integer(11),
    ], $tableOptions);
  }

  public function safeDown()
  {
    $this->dropTable('{{%data_Search}}');
  }
}
