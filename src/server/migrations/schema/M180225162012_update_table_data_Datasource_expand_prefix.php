<?php

namespace app\migrations\schema;

use lib\migrations\Migration;

/**
 * Class M180225162012_update_table_data_Datasource_expand_prefix
 */
class M180225162012_update_table_data_Datasource_expand_prefix extends Migration
{
  /**
   * {@inheritdoc}
   */
  public function safeUp()
  {
    $this->alterColumn("{{data_Datasource}}", "prefix", $this->string(100));
    return true;
  }

  /**
   * {@inheritdoc}
   */
  public function safeDown()
  {
    // do nothing
    return true;
  }
}
