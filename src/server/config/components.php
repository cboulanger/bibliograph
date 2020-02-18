<?php
return [
  // identity class
  'user' => [
    'class' => yii\web\User::class,
    'identityClass' => app\models\User::class,
  ],
  // logging
  'log' => require('components/log.php'),
  'request' => [
    'enableCookieValidation' => false,
    'enableCsrfValidation' => false,
    'parsers' => ['application/json' => 'yii\web\JsonParser']
  ],
  // Override http response component
  'response' => ['class' => \lib\components\EventTransportResponse::class],
  'urlManager' => [
    'showScriptName' => false,
    'enableStrictParsing' => false,
    'enablePrettyUrl' => true,
    'rules' => array(
      '<controller:\w+>/<id:\d+>' => '<controller>/view',
      '<controller:\w+>/<action:\w+>/<id:\d+>' => '<controller>/<action>',
      '<controller:\w+>/<action:\w+>' => '<controller>/<action>',
    )
  ],
  'i18n' => require "components/i18n.php",
  // Cache
  'cache' => [ 'class' => yii\caching\FileCache::class ],
  // Module autoloader
  'moduleLoader' => [
    'class' => bmsrox\autoloader\ModuleLoader::class,
    'modules_paths' => [ '@app/modules' ]
  ],
  // The application configuration
  'config' => [ 'class' => \lib\components\Configuration::class ],
  // database configuration
  'db' => require 'components/db.php',
  // LDAP support
  'ldap' => require 'components/ldap.php',
  'ldapAuth'  => [ 'class' => \lib\components\LdapAuth::class ],
  // email
  'mailer' => require 'components/mailer.php',
  // access manager, handles all things access
  'accessManager' => [ 'class' => \lib\components\AccessManager::class ],
  // handling of creation and migration of datasource tables
  'datasourceManager' => [ 'class' => \lib\components\DatasourceManager::class],
  // various utility methods
  'utils' => [ 'class' => \lib\components\Utils::class ],
  // a queue of Events to be transported to the browser
  'eventQueue' => [ 'class' => \lib\components\EventQueue::class ],


  // The message bus, currently only a stub
  // 'message' => [ 'class' => \lib\components\MessageBus::class ],
  // server-side events, not used
  // 'sse' => [ 'class' => \odannyc\Yii2SSE\LibSSE::class ],
  // message channels, not working yet
  // 'channel' => [ 'class' => \lib\channel\Component::class ]
];
