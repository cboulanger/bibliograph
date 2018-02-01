<?php
namespace app\migrations\schema;
use yii\db\Migration;

class m171219_230853_create_table_data_ExportFormat extends Migration
{
    public function safeUp()
    {
        $tableOptions = null;
        if ($this->db->driverName === 'mysql') {
            $tableOptions = 'CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE=InnoDB';
        }

        $this->createTable('{{%data_ExportFormat}}', [
            'id' => $this->integer(11)->notNull()->append('AUTO_INCREMENT PRIMARY KEY'),
            'namedId' => $this->string(50),
            'created' => $this->timestamp(),
            'modified' => $this->timestamp()->notNull()->defaultExpression('CURRENT_TIMESTAMP'),
            'class' => $this->string(100)->notNull()->defaultValue('invalid'),
            'name' => $this->string(100)->notNull()->defaultValue('invalid'),
            'description' => $this->string(255),
            'active' => $this->smallInteger(1)->notNull()->defaultValue('1'),
            'type' => $this->string(20),
            'extension' => $this->string(20)->notNull()->defaultValue('txt'),
        ], $tableOptions);

        $this->createIndex('unique_namedId', '{{%data_ExportFormat}}', 'namedId', true);
    }

    public function safeDown()
    {
        $this->dropTable('{{%data_ExportFormat}}');
    }
}
