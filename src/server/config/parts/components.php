<?php
$ini = require "ini.php";
$components = [

  /*
   * Framework components
   */

  // identity class
  'user' => [
    'class' => yii\web\User::class,
    'identityClass' => app\models\User::class,
  ],
  // logging
  'log' => require('log.php'),
  // Override http response component
  'response' => [ 'class' => \lib\components\EventTransportResponse::class  ],
  // Internationalization
  // @todo move module translations into module
  // https://stackoverflow.com/questions/34357254/override-translation-path-of-module-on-yii2
  // https://www.yiiframework.com/doc/guide/2.0/en/tutorial-i18n#module-translation
  // catchall "*" doesn't work
  'i18n' => [
    'translations' => [
      'app' => [
        'class' => \yii\i18n\GettextMessageSource::class,
        'basePath' => '@messages',
        'catalog' => 'messages',
        'useMoFile' => false
      ],
      'extendedfields' => [
        'class' => \yii\i18n\GettextMessageSource::class,
        'basePath' => '@messages',
        'catalog' => 'messages',
        'useMoFile' => false
      ],
      'z3950' => [
        'class' => \yii\i18n\GettextMessageSource::class,
        'basePath' => '@messages',
        'catalog' => 'messages',
        'useMoFile' => false
      ],
      'webservices' => [
        'class' => \yii\i18n\GettextMessageSource::class,
        'basePath' => '@messages',
        'catalog' => 'messages',
        'useMoFile' => false
      ],
      'setup' => [
        'class' => \yii\i18n\GettextMessageSource::class,
        'basePath' => '@messages',
        'catalog' => 'messages',
        'useMoFile' => false
      ],
    ],
  ],
  // Cache
  'cache' => [ 'class' => 'yii\caching\FileCache' ],
  // Mailer
  'mailer' => [
    'class' => yii\swiftmailer\Mailer::class,
    'transport' => isset($ini['email']['transport']) and $ini['email']['transport'] === "smtp"
      ? [
        'class' => 'Swift_SmtpTransport',
        'host'        => $ini['email']['host'],
        'username'    => $ini['email']['username'],
        'password'    => $ini['email']['password'],
        'port'        => $ini['email']['port'],
        'encryption'  => $ini['email']['encryption'],
      ] : null,
  ],

  /*
   * Composer components
   */

  // Module autoloader
  'moduleLoader' => [
    'class' => bmsrox\autoloader\ModuleLoader::class,
    'modules_paths' => [
      '@app/modules'
    ]
  ],
  
  /*
   * Custom applications components
   */  

  // The application configuration
  'config' => [
    'class' => \lib\components\Configuration::class
  ],    
  'ldap' => require('ldap.php'),
  'ldapAuth'  => [
    'class' => \lib\components\LdapAuth::class
  ],  
  // a queue of Events to be transported to the browser
  'eventQueue' => [
    'class' => \lib\components\EventQueue::class
  ],
  // access manager, handles all things access
  'accessManager' => [
    'class' => \lib\components\AccessManager::class
  ],
  // datasource manager, handles creation and migration of datasource tables
  'datasourceManager' => [
    'class' => \lib\components\DatasourceManager::class
  ],  
  // various utility methods
  'utils' => [ 
    'class' => \lib\components\Utils::class
  ],
  //server-side events, not used
  'sse' => [
    'class' => \odannyc\Yii2SSE\LibSSE::class
  ],
  //message channels, not working yet
  'channel' => [
    'class' => \lib\channel\Component::class
  ]
];
return array_merge(
  // merge db components
  require('db.php'),
  $components
);