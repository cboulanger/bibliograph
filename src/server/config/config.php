<?php
//
// This assembles the configuration parts
//
$config =  [
  /* The directory of the application */
  'basePath' => dirname(APP_BACKEND_DIR),
  /* Components that are loaded at application startup  */
  'bootstrap' => [
    'log',
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
    '@app'      => APP_BACKEND_DIR,
    '@lib'      => APP_BACKEND_DIR . '/lib/',
    '@messages' => APP_BACKEND_DIR . '/messages/',
    '@runtime'  => APP_BACKEND_DIR . '/runtime',
    '@views'    => APP_BACKEND_DIR . '/views',
    ],
  /* Application components */
  'components' => require('components.php'),
  /* Extension libraries */
  'extensions' => require(VENDOR_DIR . '/yiisoft/extensions.php'),
  /* Events */
  /**
   * Switch backend language based on configuration and browser settings
   */
  'on beforeRequest' => function($event){
    try {
      $configLocale = Yii::$app->config->getPreference("application.locale");
      if( ! $configLocale ) {
        Yii::$app->utils->setLanguageFromBrowser();
      } else {
        $language = Yii::$app->sourceLanguage;
        foreach( Yii::$app->utils->getLanguages() as $lang){
          if( str_contains( $lang, $configLocale ) ){
            $language = $lang;
          }
        }
        Yii::$app->language = $language;
        //Yii::debug("Setting language from user setting to " . $language , __METHOD__);
      }
    } catch( Exception $e ){
      Yii::$app->utils->setLanguageFromBrowser();
      //Yii::warning("Fallback: Setting language from browser settings to " . Yii::$app->language );
    }
  }
];
return $config;
