<?php
require "constants.php";
$config = [
    'id' => 'bibliograph-server',
    'bootstrap' => ['gii'],
    'basePath' => dirname(__DIR__) ,
    'controllerNamespace' => 'bibliograph\controllers',
    'aliases' => [
        '@bibliograph' => dirname(__DIR__),
        '@lib' => __DIR__ . "/../lib/"
    ],
    'components' => array_merge(
        require('db.php'),[
        'utils' => [
            'class' => 'lib\component\Utils'
        ],
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