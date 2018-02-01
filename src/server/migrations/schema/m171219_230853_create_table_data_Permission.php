<?php
namespace app\migrations\schema;
use yii\db\Migration;

class m171219_230853_create_table_data_Permission extends Migration
{
    public function safeUp()
    {
        $tableOptions = null;
        if ($this->db->driverName === 'mysql') {
            $tableOptions = 'CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE=InnoDB';
        }

        $this->createTable('{{%data_Permission}}', [
            'id' => $this->integer(11)->notNull()->append('AUTO_INCREMENT PRIMARY KEY'),
            'namedId' => $this->string(50),
            'created' => $this->timestamp(),
            'modified' => $this->timestamp()->notNull()->defaultExpression('CURRENT_TIMESTAMP'),
            'name' => $this->string(100),
            'description' => $this->string(255),
            'active' => $this->integer(1)->notNull()->defaultValue('1'),
        ], $tableOptions);

        $this->createIndex('unique_namedId', '{{%data_Permission}}', 'namedId', true);
    }

    public function safeDown()
    {
        $this->dropTable('{{%data_Permission}}');
    }
}
