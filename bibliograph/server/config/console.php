<?php
return [
    'id' => 'bibliograph-server',
    'basePath' => dirname(__DIR__) ,
    'controllerNamespace' => 'bibliograph\controllers',
    'aliases' => [
        '@bibliograph' => dirname(__DIR__),
    ],
    'components' => [
        'db' => [
            'class' => 'yii\db\Connection',
            'dsn' => 'mysql:host=localhost;dbname=bibliograph',
            'username' => 'bibliograph',
            'password' => 'bibliograph',
            'charset' => 'utf8',
        ],
    ],
    'controllerMap' => [
        'migration' => [
            'class' => 'bizley\migration\controllers\MigrationController',
        ],
    ] 
];