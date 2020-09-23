<?php

namespace app\migrations\schema\bibliograph_datasource;

use yii\db\Migration;

/**
 * Class M180602204038_table_data_Reference_add_column_uuid
 */
class M180602204038_table_data_Reference_add_column_uuid extends Migration
{
  /**
   * {@inheritdoc}
   * @throws \yii\db\Exception
   */
  public function safeUp()
  {
    $table_name = $this->db->quoteTableName($this->db->tablePrefix . "data_Reference");
    $this->addColumn($table_name, 'uuid', $this->string(40)->unique());
    $this->db->createCommand("update $table_name SET `uuid` = (SELECT uuid());")->execute();
    return true;
  }

  /**
   * This exists so that migrate/down can delete the model tables, not for downgrading
   * the database
   * {@inheritdoc}
   */
  public function safeDown()
  {
    return true;
  }
}
