<?php

use yii\db\Schema;
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
        'default' => 'chicago-author-date',
        'customize' => '1',
        'final' => '0',
        'namedId' => 'csl.style.default',
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
        
        'type' => '0',
        'default' => 'normal',
        'customize' => '0',
        'final' => '0',
        'namedId' => 'bibliograph.access.mode',
    ],
    [
        
        'type' => '0',
        'default' => null,
        'customize' => '0',
        'final' => '0',
        'namedId' => 'bibliograph.access.no-access-message',
    ],
    [
        
        'type' => '1',
        'default' => '50',
        'customize' => '0',
        'final' => '0',
        'namedId' => 'bibliograph.duplicates.threshold',
    ],
    [
        
        'type' => '1',
        'default' => '500',
        'customize' => '0',
        'final' => '0',
        'namedId' => 'plugin.csl.bibliography.maxfolderrecords',
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
    
]
        );
    }

    public function safeDown()
    {
        $this->truncateTable('{{%data_Config}}');
    }
}
