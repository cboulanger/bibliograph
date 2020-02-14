<?php

namespace lib\components;
use ForceUTF8\Encoding;
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
    //Yii::debug("prepare for " . Yii::$app->requestedRoute . "." . Yii::$app->requestedAction->id, __METHOD__);
    // FIXME
    // This is a bad hack working around a broken mysql server setup
    $data = var_export($this->data, true);
    if( ! preg_match("//u", $data) ) {
      $data = Encoding::fixUTF8($data);
      $def = '$this->data = ' . $data . ';';
      eval($def);
    }
    if( isset($this->data->error) and $this->data->error ) {
      return parent::prepare();
    }
    //Yii::debug("has events: " . Yii::$app->eventQueue->hasEvents());
    if( Yii::$app->eventQueue->hasEvents() ){
      $events = Yii::$app->eventQueue->toArray();
      Yii::$app->eventQueue->clean();
      // see above
      $data = var_export($events, true);
      if( ! preg_match("//u", $data) ) {
        $data = Encoding::fixUTF8($data);
        $def = '$events = ' . $data . ';';
        eval($def);
      }
      $this->data->result = [
        "type"    => "ServiceResult",
        "events"  => $events,
        "data"    => $this->data->result
      ];
    }
    parent::prepare();
    //Yii::debug( "****************************", __METHOD__);
    //Yii::debug( $this->data , __METHOD__);
  }
}
