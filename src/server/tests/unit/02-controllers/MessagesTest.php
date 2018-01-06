<?php

namespace app\tests\unit\controllers;

// for whatever reason, this is not loaded early enough
require_once __DIR__ . '/../../_bootstrap.php';

use Yii;

use app\tests\unit\Base;
use app\models\Session;
use app\models\Message;
use lib\channel\Channel;

class MessagesTest extends Base
{
  /**
   * @var \UnitTester
   */
  protected $tester;

  /**
   * Helper method to create some fake session data;
   *
   * @return void
   */
  protected function createSessionData()
  {
    Message::deleteAll();
    Session::deleteAll();
    (new Session(['namedId'=>"session1",'UserId'=>1]))->save();
    (new Session(['namedId'=>"session2",'UserId'=>1]))->save();
    (new Session(['namedId'=>"session3",'UserId'=>2]))->save();
    (new Session(['namedId'=>"session4",'UserId'=>3]))->save();
  }

  public function testBroadcast()
  {
    $this->createSessionData();
    $channel = new Channel('channel1', 'session1');
    Message::broadcast('channel1',['hello'=>'world']);
    $this->assertEquals( 4, Message::find()->count() );
    $this->assertTrue( $channel->check() );
    $this->assertEquals( [['hello'=>'world']], $channel->update() );
    // now one message should be deleted
    $this->assertEquals( 3, Message::find()->count() );
    $this->assertFalse( $channel->check() );
    $this->assertEquals( [], $channel->update() );
  }

  public function testSend()
  {
    $this->createSessionData();
    $channel = new Channel('channel2', 'session4');
    Message::send( $channel, 'very special message', "session4" );
    $this->assertEquals( 1, Message::find()->count() );
    $this->assertTrue( $channel->check() );
    $this->assertEquals( ['very special message'], $channel->update());
    // now one message should be deleted
    $this->assertEquals( 0, Message::find()->count() );
    $this->assertFalse( $channel->check() );
    $this->assertEquals( [], $channel->update() );    
  }

  public function testCleanupSessions()
  {
    $this->createSessionData();
    $this->assertEquals( 4, Session::find()->count() );
    // cleaning up session now shouldn't delete any
    $this->assertEquals( 0, Session::cleanup() );
    sleep(2);
    // cleanup should delete all sessions with a timeour of one second
    $this->assertEquals( 4, Session::cleanup(1) );
  }

  public function testCleanupMessages()
  {
    $this->createSessionData();
    $channel = new Channel('channel3', 'session3');
    for( $i=0; $i<10; $i++){
      $channel->broadcast( "data" . $i );
    }
    sleep(5);
    for( $i=0; $i<10; $i++){
      $channel->broadcast( "data" . $i );
    }
    $this->assertEquals( 80, Message::find()->count() );
    // cleaning up messages now shouldn't delete any
    $this->assertEquals( 0, Message::cleanup() );
    sleep(2);
    // cleanup should delete all messages with a timeout of five seconda
    $this->assertEquals( 40, Message::cleanup(5) );
    // fourty messages should remain
    $this->assertEquals( 40, Message::find()->count() );
  }

  public function testChannelMethods()
  {
    $this->createSessionData();
    $channel1 = new Channel('channel1', 'session2');
    $channel2 = new Channel('channel2', 'session3');
    $channel1->send('message1 for channel1');
    $channel1->send('message2 for channel1');
    $channel2->broadcast('broadcast for channel2');
    $this->assertEquals( 6, Message::find()->count() );
    $this->assertEquals( 2, count( $channel1->update() ) );
    $this->assertEquals( 1, count( $channel2->update() ) );
    $this->assertEquals( 3, Message::find()->count() );
  }
}
