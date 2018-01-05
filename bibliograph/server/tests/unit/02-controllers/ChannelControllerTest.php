<?php

namespace app\tests\unit\controllers;

// for whatever reason, this is not loaded early enough
require_once __DIR__ . '/../../_bootstrap.php';

use Yii;

class ChannelControllerTest extends \app\tests\unit\Base
{

  use \app\controllers\traits\JsonRpcTrait;

  /**
   * @var \UnitTester
   */
  protected $tester;

  public function _fixtures(){
    return require __DIR__ . '/../../fixtures/_access_models.php';
  }
  
  // public function testSendingMessages()
  // {
  //   // login user to create session
  //   $this->sendJsonRpc('authenticate',['user','user'],"access");
  //   // login admin 
  //   $this->token($this->sendJsonRpc('authenticate',['admin','admin'])->getRpcResult()['token']);
  //   // admin sends messages
  //   codecept_debug( "admin -> channel/send: " . $this->sendJsonRpc('send',['channel1','message via send'],"channel")->getRpcResult() );
  //   codecept_debug( "admin -> channel/broadcast: " . $this->sendJsonRpc('broadcast',['channel2','message via broadcast'])->getRpcResult() );
  //   //codecept_debug( "admin -> channel/fetch: " . $this->sendJsonRpc('fetch' )->getRpcResult());
  //   // login user
  //   $this->token($this->sendJsonRpc('authenticate',['user','user'],"access")->getRpcResult()['token']);
  //   // fetch messages
  //   codecept_debug( "user -> channel/fetch: " . $this->sendJsonRpc('fetch',null,"channel" )->getRpcResult() );    
  // }
}
