<?php

namespace app\migrations\schema;

use yii\db\Migration;

/**
 * Class M180601192435_create_table_data_Clipboard
 */
class M180601192435_create_table_data_Clipboard extends Migration
{
  /**
   * {@inheritdoc}
   */
  public function safeUp()
  {
    $tableOptions = null;
    if ($this->db->driverName === 'mysql') {
      $tableOptions = 'CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE=InnoDB';
    }

    $this->createTable('{{%data_Clipboard}}', [
      'id' => $this->integer(11)->notNull()->append('AUTO_INCREMENT PRIMARY KEY'),
      'created' => $this->timestamp(),
      'modified' => $this->timestamp()->notNull()->defaultExpression('CURRENT_TIMESTAMP'),
      'mime_type' => $this->string(50),
      'data' => $this->binary(),
      'UserId' => $this->integer(11)->notNull()->unique()
    ], $tableOptions);
    return true;
  }

  /**
   * {@inheritdoc}
   */
  public function safeDown()
  {
    echo "M180601192435_create_table_clipboard cannot be reverted.\n";
    return false;
  }

  /*
  // Use up()/down() to run migration code without a transaction.
  public function up()
  {

  }

  public function down()
  {
      echo "M180601192435_create_table_clipboard cannot be reverted.\n";

      return false;
  }
  */
}
