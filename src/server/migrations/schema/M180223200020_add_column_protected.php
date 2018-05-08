<?php

namespace app\migrations\schema;

use yii\db\Migration;

/**
 * Class M180223200020_add_column_protected
 */
class M180223200020_add_column_protected extends Migration
{
  /**
   * The tables to add the new column to
   * @var array
   */
  protected $tables = [
    'data_User' => [ 'maxId' => 1 ],
    'data_Group'=> [ 'maxId' => 0 ],
    'data_Role'=> [ 'maxId' => 4 ],
    'data_Datasource'=> [ 'maxId' => 0 ],
    'data_Permission'=> [ 'maxId' => 26 ],
    'data_Config'=> [ 'maxId' => 9 ]
  ];

  /**
   * Adds a column "protected" and sets column "active" to 1
   * {@inheritdoc}
   * @throws \yii\db\Exception
   */
  public function safeUp()
  {
    foreach ($this->tables as $tableName => $tableData ) {
      $tableSchema = $this->db->schema->getTableSchema($tableName);
      $this->addColumn($tableName, "protected", $this->smallInteger(1)->notNull()->defaultValue(0));
      $this->getDb()
        ->createCommand()
        ->update($tableName, ['protected' => 1], 'id <= ' . $tableData['maxId'])
        ->execute();
      if( $tableSchema->getColumn('active' ) ){
        $this->getDb()
          ->createCommand()
          ->update($tableName, ['active' => 1])
          ->execute();
      }
    }

    return true;
  }

  /**
   * {@inheritdoc}
   */
  public function safeDown()
  {
    foreach ($this->tables as $tableName => $tableData ) {
      $this->dropColumn($tableName, "protected");
    }
    return true;
  }

  /*
  // Use up()/down() to run migration code without a transaction.
  public function up()
  {

  }

  public function down()
  {
      echo "M180223200020_add_column_protected cannot be reverted.\n";

      return false;
  }
  */
}
