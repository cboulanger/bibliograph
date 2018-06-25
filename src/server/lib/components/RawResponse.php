<?php
/**
 * Created by PhpStorm.
 * User: cboulanger
 * Date: 25.06.18
 * Time: 21:20
 */

namespace lib\components;

use yii\web\Response;

class RawResponse extends \yii\web\Response
{
  public $format = Response::FORMAT_RAW;

  /**
   * This overrides the default implementation to do ... nothing - since it is a
   * raw repsonse.
   */
  protected function prepare()
  {
    // does nothing
  }

}