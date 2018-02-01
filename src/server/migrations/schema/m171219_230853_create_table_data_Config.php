<?php
namespace app\migrations\schema;
use yii\db\Migration;

class m171219_230853_create_table_data_Config extends Migration
{
    public function safeUp()
    {
        $tableOptions = null;
        if ($this->db->driverName === 'mysql') {
            $tableOptions = 'CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE=InnoDB';
        }

        $this->createTable('{{%data_Config}}', [
            'id' => $this->integer(11)->notNull()->append('AUTO_INCREMENT PRIMARY KEY'),
            'type' => $this->smallInteger(6),
            'default' => $this->string(255),
            'customize' => $this->integer(1)->notNull()->defaultValue('0'),
            'final' => $this->integer(1)->notNull()->defaultValue('0'),
            'namedId' => $this->string(50),
            'created' => $this->timestamp(),
            'modified' => $this->timestamp()->notNull()->defaultExpression('CURRENT_TIMESTAMP'),
        ], $tableOptions);

        $this->createIndex('unique_namedId', '{{%data_Config}}', 'namedId', true);
    }

    public function safeDown()
    {
        $this->dropTable('{{%data_Config}}');
    }
}
