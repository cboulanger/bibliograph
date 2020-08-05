<?php

namespace lib\components;
use app\controllers\dto\Base;
use ForceUTF8\Encoding;
use georgique\yii2\jsonrpc\responses\JsonRpcResponse;
use georgique\yii2\jsonrpc\responses\SuccessResponse;
use Yii;

/**
 * This Response component wich takes care of invalid utf-8 in the data
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
    if ($this->data instanceof SuccessResponse && isset($_SERVER['BIBLIOGRAPH_FIX_UTF8']) && $_SERVER['BIBLIOGRAPH_FIX_UTF8']) {
      // This is a bad hack working around a broken mysql server setup
      $data = var_export($this->data->result, true);
      if( ! preg_match("//u", $data) ) {
        $data = Encoding::fixUTF8($data);
        $def = '$this->data->result = ' . $data . ';';
        eval($def);
      }
    }
    parent::prepare();
  }
}
