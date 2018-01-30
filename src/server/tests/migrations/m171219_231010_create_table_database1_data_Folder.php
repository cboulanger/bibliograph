<?php

use yii\db\Migration;

class m171219_231010_create_table_database1_data_Folder extends Migration
{
    public function safeUp()
    {
        $tableOptions = null;
        if ($this->db->driverName === 'mysql') {
            $tableOptions = 'CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE=InnoDB';
        }

        $this->createTable('database1_data_Folder', [
            'id' => $this->integer(11)->notNull()->append('AUTO_INCREMENT PRIMARY KEY'),
            'created' => $this->timestamp(),
            'modified' => $this->timestamp()->notNull()->defaultExpression('CURRENT_TIMESTAMP'),
            'parentId' => $this->integer(11)->notNull(),
            'position' => $this->integer(11)->notNull(),
            'label' => $this->string(100),
            'type' => $this->string(20),
            'description' => $this->string(100),
            'searchable' => $this->integer(1),
            'searchfolder' => $this->integer(1),
            'query' => $this->string(255),
            'public' => $this->integer(1),
            'opened' => $this->integer(1),
            'locked' => $this->integer(1),
            'path' => $this->string(100),
            'owner' => $this->string(30),
            'hidden' => $this->integer(1),
            'createdBy' => $this->string(20),
            'markedDeleted' => $this->integer(1),
            'childCount' => $this->integer(11),
            'referenceCount' => $this->integer(11),
        ], $tableOptions);
    }

    public function safeDown()
    {
        $this->dropTable('database1_data_Folder');
    }
}
