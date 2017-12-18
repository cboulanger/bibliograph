<?php
$ini = parse_ini_file ( __DIR__ . "/../../services/config/bibliograph.ini.php", true, INI_SCANNER_RAW );
return [
    'id' => 'bibliograph-server',
    'bootstrap' => ['gii'],
    'basePath' => dirname(__DIR__) ,
    'controllerNamespace' => 'bibliograph\controllers',
    'aliases' => [
        '@bibliograph' => dirname(__DIR__),
    ],
    'components' => [
        'db' => [
            'class' => 'yii\db\Connection',
            'dsn' => "{$ini['database']['type']}:host={$ini['database']['host']};dbname={$ini['database']['admindb']}",
            'username' => 'bibliograph',
            'password' => 'bibliograph',
            'charset' => 'utf8',
        ],
    ],
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