<?php
// config for gii server
$config = require "web.php";
$config['id'] = 'bibliograph-gii-server';
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
return $config;
