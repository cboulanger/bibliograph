<?php

namespace app\tests\unit\controllers;

// for whatever reason, this is not loaded early enough
require_once __DIR__ . '/../../_bootstrap.php';

use Yii;

use Guzzle\Http\Client;
use Guzzle\Stream\PhpStreamRequestFactory;

use lib\io\Channel;
use app\tests\unit\Base;
use app\models\Session;
use app\models\User;
use app\models\Message;

class Messages2RemoteTest extends Base
{

  public function testServerSideEvents()
  {
    // create a user and a session
    (new User(['namedId'=>"user1",'id'=>1,'token'=>'token1']))->save();
    (new Session(['namedId'=>"session1",'UserId'=>1]))->save();
    // create channels and send some messages
    $foo = new Channel('foo','session1');
    $foo->send("This is an urgent message");
    $bar = new Channel('bar','session1');
    $bar->send(['foo'=>'bar']);
    // create a client to simulate a browser
    $client = new Client('http://localhost:8080');
    $start = microtime(true);
    // while(true){
    //   $request = $client->get('?r=site/sse', ['Accept' => 'text/event-stream']);
    //   $request->getQuery()->set('sessionid', 'session1');
    //   $request->getQuery()->set('token', 'token1');
    //   $factory = new PhpStreamRequestFactory();
    //   $stream = $factory->fromRequest($request);
    //   // Read until the stream is closed
    //   while (!$stream->feof()) {
    //     $secondsElapsed = microtime(true)-$start;
    //     $line = $stream->readLine();
    //     \codecept_debug($line);
    //     if( $secondsElapsed > 5 ){
    //       break 2;
    //     }
    //   }
    // }
  }
}
