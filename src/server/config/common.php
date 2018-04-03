<?php
require "parts/constants.php";

// Production
$config =  [

  /* The directory of the application */
  'basePath' => dirname(__DIR__),

  /* Components that are loaded at application startup  */
  'bootstrap' => [
    'log',
    'channel',
    'moduleLoader'
  ],

  /* Conntroller configuration */
  'controllerNamespace' => 'app\controllers',
  'controllerMap' => [
    'migrate' => [
      'class' => 'yii\console\controllers\MigrateController',
      'migrationNamespaces' => [
        'app\migrations\schema', 
        'app\migrations\data'
      ],
      'migrationPath' => null
    ],
  ],

  /* Path aliases */
  'aliases' => [
    '@lib'      => __DIR__ . "/../lib/",
    '@messages' => __DIR__ . "/../messages/"
    ],

  /* Application components */
  'components' => require('parts/components.php'),

  /* Extension libraries */
  'extensions' => require(__DIR__ . '/../vendor/yiisoft/extensions.php'),
];

return $config;