<?php

namespace app\controllers;

use Yii;

use lib\channel\Channel;
use lib\channel\Aggregator;
use app\models\Session;
use app\models\User;
use app\models\Message;
use app\controllers\AppController;
use Sse\Event;

// A simple time event to push server time to clients 
class TimeEvent implements Event {
  public function check(){
    return true;
  }
  public function update(){
    sleep(1);
    return date('l, F jS, Y, h:i:s A');
  }
}

class SseController extends \yii\web\Controller
{
  /**
   * Renders a pure HTML test client
   *
   * @return void
   */
  public function actionTest()
  {
    return $this->renderPartial('test');
  }

  protected $response;

  protected function addToResponse( $line ){
    $this->response .= $line;
    return $this;
  }

  protected function addData( $message ){
    $this->addToResponse("data:$message\n\n");
    return $this;
  }

  protected function addError( $message ){
    $this->addToResponse("error:$message\n\n");
    return $this;
  }

  protected function getResponse(){
    return $this->response;
  }

  /**
   * Endpoint for the EventSource URL
   *
   * @return void
   */
  public function actionIndex()
  { 
    try{
      $headers = getallheaders();

      $headers['X-Auth-Token'] = User::findOne(1)->token;
      $sessionId = $headers['X-Auth-Session-Id'] = Session::findOne(['UserId' => 1])->namedId;
      for ($i=0; $i < 10; $i++) { 
        (new Channel("foo",$sessionId ))->send("bar $i");
      }
      
      if( ! isset( $headers['X-Auth-Token'] ) ){
        return $this->addData("No auth token. Pass it in the X-Auth-Token header.")
          ->addError("Access denied")->getResponse();
      }
      if( ! isset( $headers['X-Auth-Session-Id'] ) ){
        return $this->addData("No session id. Pass it in the X-Auth-Session-Id header.")
          ->addError("Access denied")->getResponse();
      }    
      $token = $headers['X-Auth-Token'];
      $user = User::findOne(['token' => $token]);
      if( ! $user ){
        return $this->addData("Invalid Auth Token")
          ->addError("Access denied")->getResponse();
      }
      if ( ! $user->active) {
        return $this->addData("User is deactivated.")
          ->addError("Access denied")->getResponse();
      }    
      $session = Session::findOne(['UserId' => $user->id]);
      if( $session ) {
        session_id( $session->namedId );      
      } else {
        return $this->addData("data: No valid session id.")
          ->addError("Access denied")->getResponse();
      }
      Yii::$app->session->open();
      $sse = Yii::$app->sse;

      $sse->addEventListener('', new Aggregator( $session->namedId ) );
      $sse->start();
    } catch( \Exception $e) {
      $error = (string) $e;
      return $this->addData((string)$error)->addError("Internal Error")->getResponse();
    }
  }

  /**
   * Server Side Events source mainly for testing that will 
   * return the current time
   * @return void
   */
  public function actionTime(){
    $sse = Yii::$app->sse;
    $sse->addEventListener('', new TimeEvent());
    $sse->start();
  }
}