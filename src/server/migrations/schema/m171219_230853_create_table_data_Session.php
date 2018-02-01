<?php
namespace app\migrations\schema;
use yii\db\Migration;

class m171219_230853_create_table_data_Session extends Migration
{
    public function safeUp()
    {
        $tableOptions = null;
        if ($this->db->driverName === 'mysql') {
            $tableOptions = 'CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE=InnoDB';
        }

        $this->createTable('{{%data_Session}}', [
            'id' => $this->integer(11)->notNull()->append('AUTO_INCREMENT PRIMARY KEY'),
            'namedId' => $this->string(50),
            'created' => $this->timestamp(),
            'modified' => $this->timestamp()->notNull()->defaultExpression('CURRENT_TIMESTAMP'),
            'parentSessionId' => $this->string(50),
            'ip' => $this->string(32),
            'UserId' => $this->integer(11),
        ], $tableOptions);

        $this->createIndex('unique_namedId', '{{%data_Session}}', 'namedId', true);
        $this->createIndex('session_index', '{{%data_Session}}', ['namedId','ip'], true);
    }

    public function safeDown()
    {
        $this->dropTable('{{%data_Session}}');
    }
}
