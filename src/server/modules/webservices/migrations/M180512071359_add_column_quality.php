<?php

namespace app\modules\webservices\migrations;

use yii\db\Migration;

/**
 * Class M180512071359_add_column_quality
 */
class M180512071359_add_column_quality extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
      $this->addColumn('{{%data_Record}}','quality', $this->integer(11)->notNull()->defaultValue(0));
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        echo "M180512071359_add_column_quality cannot be reverted.\n";

        return false;
    }

    /*
    // Use up()/down() to run migration code without a transaction.
    public function up()
    {

    }

    public function down()
    {
        echo "M180512071359_add_column_quality cannot be reverted.\n";

        return false;
    }
    */
}
