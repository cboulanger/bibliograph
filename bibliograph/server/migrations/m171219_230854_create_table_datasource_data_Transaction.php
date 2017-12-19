<?php

use yii\db\Migration;

class m171219_230854_create_table_datasource_data_Transaction extends Migration
{
    public function safeUp()
    {
        $tableOptions = null;
        if ($this->db->driverName === 'mysql') {
            $tableOptions = 'CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE=InnoDB';
        }

        $this->createTable('{{%datasource_data_Transaction}}', [
            'id' => $this->integer(11)->notNull()->append('AUTO_INCREMENT PRIMARY KEY'),
            'created' => $this->timestamp(),
            'modified' => $this->timestamp()->notNull()->defaultExpression('CURRENT_TIMESTAMP'),
            'datasource' => $this->string(50),
            'class' => $this->string(100),
            'transactionId' => $this->integer(11)->defaultValue('0'),
        ], $tableOptions);

        $this->createIndex('datasource_class_index', '{{%datasource_data_Transaction}}', ['datasource','class'], true);
    }

    public function safeDown()
    {
        $this->dropTable('{{%datasource_data_Transaction}}');
    }
}
