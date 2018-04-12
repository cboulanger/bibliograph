<?php
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
  'i18n' => [
    'translations' => [
      'app*' => [
        'class' => yii\i18n\GettextMessageSource::class,
        'basePath' => '@messages',
        'catalog' => 'messages',
        'useMoFile' => false
      ],
      /** @todo move into module https://stackoverflow.com/questions/34357254/override-translation-path-of-module-on-yii2 */
      'z3950' => [
        'class' => yii\i18n\GettextMessageSource::class,
        'basePath' => '@messages',
        'catalog' => 'z3950',
        'useMoFile' => false
      ],
    ],
  ],
  // Cache
  'cache' => [ 'class' => 'yii\caching\FileCache' ],
  // Mailer
  'mailer' => [
    'class' => yii\swiftmailer\Mailer::class,
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