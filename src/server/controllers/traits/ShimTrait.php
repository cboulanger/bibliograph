<?php

namespace app\controllers\traits;
use Yii;

/**
 * Trait that should become obsolete once the code has been updated
 */
trait ShimTrait
{
  public function log($msg)
  {
    Yii::trace($msg);
  }  
  public function debug($msg)
  {
    Yii::trace($msg);
  }
  public function info($msg)
  {
    Yii::info($msg);
  }
  public function warn($msg)
  {
    Yii::warning($msg);
  }
  public function error($msg)
  {
    Yii::error($msg);
  }
  protected function tr($string)
  {
    return Yii::t('app', $string );
  }  
}