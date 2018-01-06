<?php

namespace app\tests\unit\controllers;

// for whatever reason, this is not loaded early enough
require_once __DIR__ . '/../../_bootstrap.php';

use Yii;
use yii\base\Event;

class EventQueueTest extends \app\tests\unit\Base
{
  public function testEventQueue()
  {
    $q = Yii::$app->eventQueue;
    $q->add( new Event([ 'name' => 'bar', 'data' => 'bar' ]) );
    $q->add( new Event([ 'name' => 'baz' ]) );

    $expected = [
      [ 'name' => 'bar', 'data' => 'bar' ],
      [ 'name' => 'baz', 'data' => null ]
    ];
    $this->assertEquals( $q->toArray(), $expected );
  }
}
