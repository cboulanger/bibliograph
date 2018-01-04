<?php

namespace app\tests\unit\controllers;

// for whatever reason, this is not loaded early enough
require_once __DIR__ . '/../../_bootstrap.php';

use Yii;

use app\tests\unit\Base;
use app\models\Session;
use app\models\Message;
use app\controllers\sse\Channel;

class MessageTransportTest extends Base
{
  /**
   * @var \UnitTester
   */
  protected $tester;

  protected function createSessionData()
  {
    (new Session(['namedId'=>"session1",'UserId'=>1]))->save();
    (new Session(['namedId'=>"session2",'UserId'=>1]))->save();
    (new Session(['namedId'=>"session3",'UserId'=>2]))->save();
    (new Session(['namedId'=>"session4",'UserId'=>3]))->save();
  }

  public function testBroadcast()
  {
    $this->createSessionData();
    $channel = new Channel('message1', 'session1');
    Message::broadcast('message1',['hello'=>'world']);
    $this->assertEquals( 4, Message::find()->count() );
    $this->assertTrue( $channel->check() );
    $data = $channel->update();
    $this->assertTrue( is_array( $data ) and count($data) == 1);
    // now one message should be deleted
    $this->assertEquals( 3, Message::find()->count() );
    $this->assertFalse( $channel->check() );
    $this->assertEquals( [], $channel->update() );
  }

  public function testSend()
  {
    $this->createSessionData();
    $channel = new Channel('message2', 'session4');
    Message::send( $channel, 'very special message', "session4" );
    $this->assertEquals( 1, Message::find()->count() );
    $this->assertTrue( $channel->check() );
    $this->assertEquals( ['very special message'], $channel->update());
    // now one message should be deleted
    $this->assertEquals( 0, Message::find()->count() );
    $this->assertFalse( $channel->check() );
    $this->assertEquals( [], $channel->update() );    
  }
}
