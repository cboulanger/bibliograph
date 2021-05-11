<?php

namespace app\migrations\schema\bibliograph_datasource;

use Yii;
use lib\migrations\Migration;

/**
 * Class M180602120949_fix_integer_notnull_columns
 */
class M180602120949_fix_integer_notnull_columns extends Migration
{
  /**
   * {@inheritdoc}
   * @throws \yii\db\Exception
   */
  public function safeUp()
  {
    // Reference
    $table_name = $this->db->quoteTableName($this->db->tablePrefix . "data_Reference");
    $this->db->createCommand("update $table_name set `markedDeleted` = 0 where `markedDeleted` IS NULL;")->execute();
    $this->alterColumn($table_name, 'markedDeleted', $this->smallInteger(1)->notNull()->defaultValue(0));
    $this->db->createCommand("update $table_name set `attachments` = 0 where `attachments` IS NULL;")->execute();
    $this->alterColumn($table_name, 'attachments',   $this->integer(11)->notNull()->defaultValue(0));

    // Folder
    $table_name = $this->db->quoteTableName($this->db->tablePrefix . "data_Folder");
    $columns = ['searchable', 'searchfolder', 'public', 'opened', 'locked', 'hidden', 'markedDeleted'];
    foreach ( $columns as $column) {
      $this->db->createCommand("update $table_name set `$column` = 0 where `$column` IS NULL;")->execute();
      try {
        $this->alterColumn($table_name, $column, $this->smallInteger(1)->notNull()->defaultValue(0));
      } catch (\PDOException $e) {
        Yii::warning($e->getMessage());
      }
    }
    try {
      $this->alterColumn( $table_name, 'childCount', $this->integer(11)->notNull()->defaultValue(0));
    } catch (\Throwable $e) {
      Yii::warning($e->getMessage());
    }
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
