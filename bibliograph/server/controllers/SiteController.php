<?php

namespace app\controllers;

use Yii;
use yii\web\Controller;
use lib\io\AllChannels;
use app\models\Session;
use app\models\User;
use app\models\Message;

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
  public function actionSse()
  { 
    // @TODO secure 
    $sse = Yii::$app->sse;
    try{
      $sse->addEventListener('', new AllChannels( $_GET['sessionid'] ) );
      $sse->start();
    } catch( \Exception $e) {
      echo "error: $e->getMessage()\n\n";
    }
  }
}