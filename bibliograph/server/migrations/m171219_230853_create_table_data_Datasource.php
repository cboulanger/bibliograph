<?php

use yii\db\Migration;

class m171219_230853_create_table_data_Datasource extends Migration
{
    public function safeUp()
    {
        $tableOptions = null;
        if ($this->db->driverName === 'mysql') {
            $tableOptions = 'CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE=InnoDB';
        }

        $this->createTable('{{%data_Datasource}}', [
            'id' => $this->integer(11)->notNull()->append('AUTO_INCREMENT PRIMARY KEY'),
            'namedId' => $this->string(50),
            'created' => $this->timestamp(),
            'modified' => $this->timestamp()->notNull()->defaultExpression('CURRENT_TIMESTAMP'),
            'title' => $this->string(100),
            'description' => $this->string(255),
            'schema' => $this->string(100),
            'type' => $this->string(20),
            'host' => $this->string(200),
            'port' => $this->integer(11),
            'database' => $this->string(100),
            'username' => $this->string(50),
            'password' => $this->string(50),
            'encoding' => $this->string(20)->notNull()->defaultValue('utf-8'),
            'prefix' => $this->string(20),
            'resourcepath' => $this->string(255),
            'active' => $this->smallInteger(1)->notNull()->defaultValue('1'),
            'readonly' => $this->smallInteger(1)->notNull()->defaultValue('0'),
            'hidden' => $this->smallInteger(1)->notNull()->defaultValue('0'),
        ], $tableOptions);

        $this->createIndex('unique_namedId', '{{%data_Datasource}}', 'namedId', true);
    }

    public function safeDown()
    {
        $this->dropTable('{{%data_Datasource}}');
    }
}
