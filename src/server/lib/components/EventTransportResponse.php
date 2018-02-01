<?php

namespace lib\components;
use Yii;

/**
 * This Response component adds an event transport layer to the
 * JSONRPC response
 * Event Transport protocol:
 * {
 *   "type" : "ServiceResult"
 *   "events" : [ { "name": "...", "data": <event data> }],
 *   "data" : <result data>
 * }
 */
class EventTransportResponse extends \yii\web\Response
{
  public $format = yii\web\Response::FORMAT_JSON;

  /**
   * @inheritDoc
   */
  protected function prepare()
  {
    if( isset($this->data['error']) and $this->data['error'] )
    {
      return parent::prepare();
    }
    
    if( Yii::$app->eventQueue->hasEvents() )
    {
      $this->data['result'] = [
        "type"    => "ServiceResult",
        "events"  => Yii::$app->eventQueue->toArray(),
        "data"    => $this->data
      ];
    }
    parent::prepare();
    //Yii::trace( "****************************");
    //Yii::trace( $this->data );    
  }
}