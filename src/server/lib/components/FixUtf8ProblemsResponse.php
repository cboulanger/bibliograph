<?php

namespace lib\components;
use app\controllers\dto\Base;
use ForceUTF8\Encoding;
use georgique\yii2\jsonrpc\responses\SuccessResponse;
use Yii;

/**
 * This Response component wich takes care of invalid utf-8 in the data.
 * This is a bad hack working around a broken mysql server setup
 */
class FixUtf8ProblemsResponse extends \yii\web\Response
{
  public $format = yii\web\Response::FORMAT_JSON;

  /**
   * @param mixed $data
   * @return mixed
   */
  protected function fixUtf8($data) {
    if ($data instanceof Base) {
      $data = (array)$data;
    }
    $serialized = var_export($data, true);
    if (!mb_detect_encoding($serialized, 'UTF-8', true)) {
      Yii::error("*** INVALID UTF-8: " . $serialized);
      // try to fix them
      $serialized = Encoding::fixUTF8($serialized);
      // if this doesn't fix it, remove invalid characters
      if (!mb_detect_encoding($serialized, 'UTF-8', true)) {
        $serialized = iconv("UTF-8", "UTF-8//IGNORE", $serialized);
        $serialized = mb_convert_encoding($serialized , 'UTF-8', 'UTF-8');
      }
      $def = '$data = ' . $serialized . ';';
      eval($def);
    }
    return $data;
  }

  /**
   * @overridden
   */
  protected function prepare()
  {
    //Yii::debug("prepare for " . Yii::$app->requestedRoute . "." . Yii::$app->requestedAction->id, __METHOD__);
    if (isset($_SERVER['BIBLIOGRAPH_FIX_UTF8']) && $_SERVER['BIBLIOGRAPH_FIX_UTF8']) {
      //
      if (is_array($this->data)) {
        for ($i=0; $i < count($this->data); $i++) {
          if ($this->data[$i] instanceof SuccessResponse) {
            $this->data[$i]->result = $this->fixUtf8($this->data[$i]->result);
          }
        }
      } elseif ($this->data instanceof SuccessResponse) {
        $this->data->result = $this->fixUtf8($this->data->result);
      }
    }
    parent::prepare();
  }
}
