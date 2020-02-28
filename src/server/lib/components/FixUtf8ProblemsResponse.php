<?php

namespace lib\components;
use app\controllers\dto\Base;
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
class FixUtf8ProblemsResponse extends \yii\web\Response
{
  public $format = yii\web\Response::FORMAT_JSON;

  /**
   * @inheritDoc
   */
  protected function prepare()
  {
    //Yii::debug("prepare for " . Yii::$app->requestedRoute . "." . Yii::$app->requestedAction->id, __METHOD__);
    if (!is_object($this->data) && defined('BIBLIOGRAPH_FIX_UTF8')) {
      // This is a bad hack working around a broken mysql server setup
      $data = var_export($this->data, true);
      if( ! preg_match("//u", $data) ) {
        $data = Encoding::fixUTF8($data);
        $def = '$this->data = ' . $data . ';';
        eval($def);
      }
    }
    parent::prepare();
  }
}
