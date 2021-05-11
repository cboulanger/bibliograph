<?php

namespace app\migrations\schema\bibliograph_datasource;

use lib\migrations\Migration;

/**
 * Class M180511110319_move_editor_data_in_collections
 * This moves the editor info form the 'author' to the 'editor' field in collections, fixing an
 * incorrect field mapping in Bibliograph v2
 */
class M180511110319_move_editor_data_in_collections extends Migration
{
  /**
   * {@inheritdoc}
   * @throws \yii\db\Exception
   */
  public function safeUp()
  {
    $table_name = $this->db->quoteTableName($this->db->tablePrefix . "data_Reference");
    $sql = "
      update $table_name 
      set `editor` = `author` , `author` = null 
      where (`reftype` = 'collection' or `reftype`  = 'proceedings')
        and (`author` is not null and `author` != '')
        and (`editor` is null or `editor` = '');";
    $this->db->createCommand($sql)->execute();
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
