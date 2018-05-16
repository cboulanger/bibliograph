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
        //Yii::debug("Setting language from user setting to " . $language );
      }
    } catch( Exception $e ){
      Yii::$app->utils->setLanguageFromBrowser();
      //Yii::warning("Fallback: Setting language from browser settings to " . Yii::$app->language );
    }
  }
];
return $config;