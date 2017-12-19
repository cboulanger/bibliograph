<?php
$config = [
    'id' => 'bibliograph-server',
    'bootstrap' => ['gii'],
    'basePath' => dirname(__DIR__) ,
    'controllerNamespace' => 'bibliograph\controllers',
    'aliases' => [
        '@bibliograph' => dirname(__DIR__)
    ],
    'components' => array_merge(require('db.php'),[

    ]),
    'modules' => [
        'gii' => [
            'class' => 'yii\gii\Module',
        ]
    ],
    'controllerMap' => [
        'migration' => [
            'class' => 'bizley\migration\controllers\MigrationController',
        ],
    ],
    'extensions' => require(__DIR__ . '/../vendor/yiisoft/extensions.php'),
];
return $config; 