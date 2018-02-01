<?php
namespace app\migrations\schema;
use yii\db\Migration;

class m171219_230854_create_table_join_Group_User extends Migration
{
    public function safeUp()
    {
        $tableOptions = null;
        if ($this->db->driverName === 'mysql') {
            $tableOptions = 'CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE=InnoDB';
        }

        $this->createTable('{{%join_Group_User}}', [
            'id' => $this->integer(11)->notNull()->append('AUTO_INCREMENT PRIMARY KEY'),
            'created' => $this->timestamp(),
            'modified' => $this->timestamp()->notNull()->defaultExpression('CURRENT_TIMESTAMP'),
            'UserId' => $this->integer(11),
            'GroupId' => $this->integer(11),
        ], $tableOptions);

        $this->createIndex('index_Group_User', '{{%join_Group_User}}', ['GroupId','UserId'], true);
    }

    public function safeDown()
    {
        $this->dropTable('{{%join_Group_User}}');
    }
}
