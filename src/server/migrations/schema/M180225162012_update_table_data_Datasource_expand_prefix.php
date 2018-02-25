<?php

namespace app\migrations\schema;

use yii\db\Migration;

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
      $this->alterColumn("{{data_Datasource}}","prefix", $this->string(100));
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
      // do nothing
        return true;
    }

    /*
    // Use up()/down() to run migration code without a transaction.
    public function up()
    {

    }

    public function down()
    {
        echo "M180225162012_update_table_data_Datasource_expand_prefix cannot be reverted.\n";

        return false;
    }
    */
}
