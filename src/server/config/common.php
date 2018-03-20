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
    '@tests'    => __DIR__ . "/../tests/",
    '@messages' => __DIR__ . "/../messages/"
    ],

  /* Application components */
  'components' => require('parts/components.php'),

  /* Extension libraries */
  'extensions' => require(__DIR__ . '/../vendor/yiisoft/extensions.php'),
];

// Development
// @todo move into own config file
if (YII_ENV_DEV) {
  $config['bootstrap'][] = 'gii';
  $config['modules']['gii'] = [
    'class' => 'yii\gii\Module',
    'generators' => [
      'fixture' => [
      'class' => 'elisdn\gii\fixture\Generator',
      ],
    ]
  ];
  $config['controllerMap'] = array_merge(
    $config['controllerMap'],[
    'migrate' => [
      'class' => 'yii\console\controllers\MigrateController',
      'migrationNamespaces' => [
        'app\migrations\schema', 
        'app\migrations\data'
      ],
      'migrationPath' => null
    ],
    'migration' => [
      'class' => 'bizley\migration\controllers\MigrationController'
    ],
    'stubs' => [
      'class' => 'bazilio\stubsgenerator\StubsController',
    ],    
  ]);    
}
return $config;