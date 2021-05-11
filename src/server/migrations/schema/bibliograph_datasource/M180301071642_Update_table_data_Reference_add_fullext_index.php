<?php

namespace app\migrations\schema\bibliograph_datasource;

use lib\migrations\Migration;

/**
 * Class M180301071642Update_table_data_Reference_add_fullext_index
 */
class M180301071642_Update_table_data_Reference_add_fullext_index extends Migration
{
  /**
   * {@inheritdoc}
   */
  public function safeUp()
  {
    $table_name = $this->db->quoteTableName($this->db->tablePrefix . "data_Reference");
    // todo: get from schema
    $columns = ['abstract', 'annote', 'author', 'booktitle', 'subtitle', 'contents', 'editor', 'howpublished', 'journal', 'keywords', 'note', 'publisher', 'school', 'title', 'year'];
    $definition = "`" . implode("`,`", $columns) . "`";
    $this->execute("ALTER TABLE $table_name ADD FULLTEXT INDEX `fulltext` ($definition)");
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
