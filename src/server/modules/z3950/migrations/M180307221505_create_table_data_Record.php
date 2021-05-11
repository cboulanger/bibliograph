<?php

namespace app\modules\z3950\migrations;

use yii\db\Migration;

/**
 * Class M180307221505_create_table_data_Record
 */
class M180307221505_create_table_data_Record extends Migration
{
  public function safeUp()
  {
    $tableOptions = null;
    if ($this->db->driverName === 'mysql') {
      $tableOptions = 'CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE=InnoDB';
    }

    $this->createTable('{{%data_Record}}', [
      'id' => $this->integer(11)->notNull()->append('AUTO_INCREMENT PRIMARY KEY'),
      'SearchId' => $this->integer(11),
      'created' => $this->timestamp(),
      'modified' => $this->timestamp()->notNull()->defaultExpression('CURRENT_TIMESTAMP'),
      'citekey' => $this->string(50),
      'reftype' => $this->string(20),
      'abstract' => $this->text(),
      'address' => $this->string(255),
      'affiliation' => $this->string(50),
      'annote' => $this->text(),
      'author' => $this->string(255),
      'booktitle' => $this->string(255),
      'subtitle' => $this->string(255),
      'contents' => $this->text(),
      'copyright' => $this->string(150),
      'crossref' => $this->string(50),
      'date' => $this->string(50),
      'doi' => $this->string(50),
      'edition' => $this->string(50),
      'editor' => $this->string(255),
      'howpublished' => $this->string(255),
      'institution' => $this->string(255),
      'isbn' => $this->string(30),
      'issn' => $this->string(20),
      'journal' => $this->string(150),
      'key' => $this->string(20),
      'keywords' => $this->string(255),
      'language' => $this->string(20),
      'lccn' => $this->string(255),
      'location' => $this->string(150),
      'month' => $this->string(50),
      'note' => $this->text(),
      'number' => $this->string(30),
      'organization' => $this->string(150),
      'pages' => $this->string(30),
      'price' => $this->string(30),
      'publisher' => $this->string(150),
      'school' => $this->string(150),
      'series' => $this->string(200),
      'size' => $this->string(50),
      'title' => $this->string(255),
      'translator' => $this->string(100),
      'type' => $this->string(50),
      'url' => $this->string(255),
      'volume' => $this->string(50),
      'year' => $this->string(20),
      'uuid' => $this->string(40)->unique(),
    ], $tableOptions);
  }

  public function safeDown()
  {
    $this->dropTable('{{%data_Record}}');
  }
}
