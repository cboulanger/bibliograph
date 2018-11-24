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
  'response' => [
    'class' => \lib\components\EventTransportResponse::class
  ],

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
      'backup' => [
        'class' => \yii\i18n\GettextMessageSource::class,
        'basePath' => '@messages',
        'catalog' => 'messages',
        'useMoFile' => false
      ],
      'email' => [
        'class' => \yii\i18n\GettextMessageSource::class,
        'basePath' => '@messages',
        'catalog' => 'messages',
        'useMoFile' => false
      ],
    ],
  ],
  // Cache
  'cache' => [ 'class' => yii\caching\FileCache::class ],

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

  /*
   * i/o events/message handling
   * @todo needs to be streamlined
   */

  'eventQueue' => [
    'class' => \lib\components\EventQueue::class
  ],
  // The message bus, currently only a stub
  'message' => [
    'class' => \lib\components\MessageBus::class
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

// Mailer
if (isset($ini['email']['transport'])) {
  $components['mailer']['class'] = yii\swiftmailer\Mailer::class;
  if ($ini['email']['transport'] === "smtp"){
    $components['mailer']['transport'] = [
      'class'       => 'Swift_SmtpTransport',
      'host'        => $ini['email']['host'] ?? null,
      'username'    => $ini['email']['username'] ?? null,
      'password'    => $ini['email']['password'] ?? null,
      'port'        => $ini['email']['port'] ?? null,
      'encryption'  => $ini['email']['encryption'] ?? null,
    ];
  }
}

return array_merge(
  // merge db components
  require('db.php'),
  $components
);