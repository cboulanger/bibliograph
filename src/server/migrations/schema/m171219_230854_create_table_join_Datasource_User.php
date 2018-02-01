<?php
namespace app\migrations\schema;
use yii\db\Migration;

class m171219_230854_create_table_join_Datasource_User extends Migration
{
    public function safeUp()
    {
        $tableOptions = null;
        if ($this->db->driverName === 'mysql') {
            $tableOptions = 'CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE=InnoDB';
        }

        $this->createTable('{{%join_Datasource_User}}', [
            'id' => $this->integer(11)->notNull()->append('AUTO_INCREMENT PRIMARY KEY'),
            'created' => $this->timestamp(),
            'modified' => $this->timestamp()->notNull()->defaultExpression('CURRENT_TIMESTAMP'),
            'DatasourceId' => $this->integer(11),
            'UserId' => $this->integer(11),
        ], $tableOptions);

        $this->createIndex('index_Datasource_User', '{{%join_Datasource_User}}', ['DatasourceId','UserId'], true);
    }

    public function safeDown()
    {
        $this->dropTable('{{%join_Datasource_User}}');
    }
}
