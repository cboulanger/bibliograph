<?php

namespace tests\unit\components;

use Yii;
use yii\base\Event;

class EventQueueTest extends \tests\unit\Base
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
