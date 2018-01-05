<?php

namespace app\controllers;

use Yii;

use lib\io\AllChannels;
use app\models\Session;
use app\models\User;
use app\models\Message;
use app\controllers\AppController;

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
    $this->addToResponse("data: $message\n\n");
    return $this;
  }

  protected function addError( $message ){
    $this->addToResponse("error: $message\n\n");
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
    $headers = getallheaders();

    // $headers['X-Auth-Token']= "rl7KPO3QwJ7ro5ymJ8b7Li1jPwVB1ugi";
    // $headers['X-Auth-Session-Id'] = "kqo42l8dbfjnmnedvb9h7stum5";

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
    try{
      $sse->addEventListener('', new AllChannels( $session->namedId ) );
      $sse->start();
    } catch( \Exception $e) {
      $error = (string) $e;
      return "error: $error\n\n";
    }
  }
}