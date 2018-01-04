<?php

namespace app\controllers;

use Yii;
use yii\web\Controller;
use lib\io\Channel;

class SiteController extends Controller
{
  /**
   * Renders the start page
   *
   * @return void
   */
  public function actionIndex()
  {
    return $this->renderPartial('index');
  }

  /**
   * Endpoint for the EventSource URL
   *
   * @return void
   */
  public function actionMessage()
  {
    $sse = Yii::$app->sse;
    $sse->addEventListener('message', new Channel('message'));
    $sse->start();
  }
}