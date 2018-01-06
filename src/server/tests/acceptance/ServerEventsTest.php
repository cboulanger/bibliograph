<?php

namespace app\tests\unit\controllers;

// for whatever reason, this is not loaded early enough
require_once __DIR__ . '/../../_bootstrap.php';

use Yii;

use lib\channel\Channel;
use app\tests\unit\Base;
use app\models\Session;
use app\models\User;
use app\models\Message;

class ServerEventsTest extends Base
{

  // public function testServerSideEvents()
  // {
  //   return $this->markTestSkipped('Currently failing due to the difficulty of simulating an EventSource in PHP.');
  //   // create a user and a session
  //   (new User(['namedId'=>"user1",'id'=>1,'token'=>'token1']))->save();
  //   (new Session(['namedId'=>"session1",'UserId'=>1]))->save();
  //   // create channels and send some messages
  //   $foo = new Channel('foo','session1');
  //   $foo->send("This is an urgent message");
  //   $bar = new Channel('bar','session1');
  //   $bar->send(['foo'=>'bar']);
  //   sleep(2);
  //   // create a client to simulate a browser
  //   $client = new Client('http://localhost:8080');
  //   $start = microtime(true);
  //   $expect = 'data:[{"event":"foo","data":"This is an urgent message"},{"event":"bar","data":{"foo":"bar"}}]';
  //   while(true){
  //     $request = $client->get('?r=site/sse', ['Accept' => 'text/event-stream']);
  //     $request->getQuery()->set('sessionid', 'session1');
  //     $request->getQuery()->set('token', 'token1');
  //     $factory = new PhpStreamRequestFactory();
  //     $stream = $factory->fromRequest($request);
  //     $output=[];
  //     // Read until the stream is closed
  //     while (!$stream->feof()) {
  //       $s= round(microtime(true)-$start);
  //       $bar->send("$s seconds elapsed.");
  //       sleep(1);
  //       $line = $stream->readLine();
  //       if( trim($line) == $expect ) break 2;
  //       $ouput[] = $line;
  //       \codecept_debug($line);
  //       if( $s > 10 ){
  //         //throw new \Exception("Did not receive expected data. Output was: \n" . implode("\n",$output));
  //         $this->markTestSkipped('Currently failing due to the difficulty of simulating an EventSource in PHP.');
  //       }
  //     }
  //   }
  //   // cleanup
  //   Message::deleteAll();
  //   Session::deleteAll();
  // }
}
