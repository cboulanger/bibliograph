<?php

namespace app\migrations\schema\bibliograph_datasource;

use lib\migrations\Migration;

/**
 * Class M180516082630_update_table_data_Reference_expand_isbn
 */
class M180516082630_update_table_data_Reference_expand_isbn extends Migration
{
  /**
   * {@inheritdoc}
   */
  public function safeUp()
  {
    $table_name = $this->db->quoteTableName($this->db->tablePrefix . "data_Reference");
    $this->alterColumn($table_name, "isbn", $this->string(100));
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
