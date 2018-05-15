<?php

use app\modules\z3950\Module;
use yii\db\BaseActiveRecord;
use yii\web\User;

/** @noinspection MissedFieldInspection */
return [
  'id' => 'z3950',
  'class' => Module::class,
  'events' => [
    [
      'class' => User::class,
      'event' => User::EVENT_AFTER_LOGOUT,
      'callback' => [Module::class, "on_after_logout"]
    ],
    [
      'class' => \app\models\User::class,
      'event' => BaseActiveRecord::EVENT_AFTER_DELETE,
      'callback' => [Module::class, "on_after_delete"]
    ]
  ]
];
