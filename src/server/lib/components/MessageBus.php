<?php
/**
 * Created by PhpStorm.
 * User: cboulanger
 * Date: 14.06.18
 * Time: 08:13
 */

namespace lib\components;

use yii\base\BaseObject;
use Yii;

class MessageBus extends BaseObject
{
  /**
   * @todo move implementation here
   * @param $name
   * @param $data
   */
  public function dispatch(string $name, $data )
  {
    throw new \BadMethodCallException("Not implemented");
  }

  /**
   * @param string $name event name
   * @param $data
   * @param $selfAlso
   */
  public function broadcast(string $name, $data, $selfAlso=false )
  {
    if( $selfAlso ) $this->dispatch($name, $data);
    Yii::debug("Broadcasting not implemented");
  }
}