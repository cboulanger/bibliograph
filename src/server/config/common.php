<?php
require "parts/constants.php";

// Production
$config =  [
  'basePath' => dirname(__DIR__),
  'bootstrap' => ['log','channel'],
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
  'aliases' => [
    '@lib'    => __DIR__ . "/../lib/",
    '@tests'  => __DIR__ . "/../tests/",
    '@messages' => __DIR__ . "/../messages/",
    ],
  'components' => require('parts/components.php'),
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
    'fixture' => [
      'class' => 'yii\faker\FixtureController',
    ],
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