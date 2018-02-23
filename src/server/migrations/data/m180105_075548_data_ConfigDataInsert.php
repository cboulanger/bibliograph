<?php

namespace app\migrations\data;

use yii\db\Migration;

class m180105_075548_data_ConfigDataInsert extends Migration
{

  public function safeUp()
  {
    $this->batchInsert('{{%data_Config}}',
      ["type", "default", "customize", "final", "namedId"],
      [
        [
          'type' => '0',
          /** just for testing - a non-existent 2.x version */
          'default' => '2.9.9',
          'customize' => '0',
          'final' => '0',
          'namedId' => 'app.version',
        ],
        [
          'type' => '0',
          'default' => 'en',
          'customize' => '1',
          'final' => '0',
          'namedId' => 'application.locale',
        ],
        [
          'type' => '0',
          'default' => 'Bibliograph Online Bibliographic Data Manager',
          'customize' => '0',
          'final' => '0',
          'namedId' => 'application.title',
        ],
        [
          'type' => '0',
          'default' => 'bibliograph/icon/bibliograph-logo.png',
          'customize' => '0',
          'final' => '0',
          'namedId' => 'application.logo',
        ],
        [
          'type' => '1',
          'default' => '50',
          'customize' => '0',
          'final' => '0',
          'namedId' => 'bibliograph.duplicates.threshold',
        ],
        [
          'type' => '2',
          'default' => 'false',
          'customize' => '0',
          'final' => '0',
          'namedId' => 'access.enforce_https_login',
        ],
        [
          'type' => '0',
          'default' => 'plaintext',
          'customize' => '0',
          'final' => '0',
          'namedId' => 'authentication.method',
        ],
        [
          'type' => '0',
          'default' => 'chicago-author-date',
          'customize' => '1',
          'final' => '0',
          'namedId' => 'csl.style.default',
        ],
      ]
    );
  }

  public function safeDown()
  {
    $this->truncateTable('{{%data_Config}}');
  }
}
