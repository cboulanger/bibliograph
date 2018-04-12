<?php

use app\modules\z3950\Module;
use yii\base\ModelEvent;
use yii\db\BaseActiveRecord;
use yii\web\User;
use yii\web\UserEvent;

/** @noinspection MissedFieldInspection */
return [
  'id' => 'z3950',
  'class' => Module::class,
  'events' => [
    [
      'class' => User::class,
      'event' => User::EVENT_AFTER_LOGOUT,
      'callback' => function ( UserEvent $e) {
        /** @var \app\models\User|null $user */
        $user = $e->identity;
        if( ! $user ) return;
        /** @var Module $module */
        $module = \Yii::$app->getModule('z3950');
        try{
          $module->clearSearchData($user);
        } catch (Throwable $e){
          \Yii::warning($e->getMessage());
        }
      }
    ],
    [
      'class' => \app\models\User::class,
      'event' => BaseActiveRecord::EVENT_AFTER_DELETE,
      'callback' => function ( \yii\base\Event $e) {
        /** @var \app\models\User|null $user */
        $user = $e->sender;
        if( ! $user ) return;
        /** @var Module $module */
        $module = \Yii::$app->getModule('z3950');
        try{
          $module->clearSearchData($user);
        } catch (Throwable $e){
          \Yii::warning($e->getMessage());
        }
      }
    ],
  ]
];