<?php

namespace app\migrations\schema\bibliograph_datasource;

use yii\db\Migration;

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
   * {@inheritdoc}
   */
  public function safeDown()
  {
    echo "M180516082630_update_table_data_Reference_expand_isbn cannot be reverted.\n";
    return false;
  }
}
