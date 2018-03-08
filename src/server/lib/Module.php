<?php
/**
 * Created by PhpStorm.
 * User: cboulanger
 * Date: 08.03.18
 * Time: 18:52
 */

namespace lib;
use Yii;

/**
 * Class Module
 * @package lib
 * @property boolean $enabled
 *    Whether the module ist enabled
 * @property boolean $installed
 *    Whether the module has already been installed, i.e. the install() method
 *    has been called.
 * @property string configKeyPrefix
 * @property string configKeyEnabled
 */
class Module extends \yii\base\Module
{
  /**
   * @return string
   */
  public function getConfigKeyPrefix()
  {
    return "modules.{$this->id}.";
  }

  /**
   * @return string
   */
  public function getConfigKeyEnabled()
  {
    return $this->configKeyPrefix . "enabled";
  }

  /**
   * @return bool
   */
  public function getInstalled()
  {
    return Yii::$app->config->keyExists($this->configKeyEnabled);
  }

  /**
   * @return bool
   */
  public function getEnabled()
  {
    return $this->installed && Yii::$app->config->getPreference($this->configKeyEnabled);
  }

  /**
   * @param $value
   */
  public function setEnabled( $value)
  {
    Yii::$app->config->setPreference($this->configKeyEnabled,$value);
  }

  public function init()
  {
    $not = $this->enabled ? "" : "not";
    Yii::info("Module '{$this->id}' is $not enabled.");
    parent::init();
  }

  /**
   * Overriding methods must call `parent::install()` when installation succeeds.
   * @param boolean $enabled
   *    Whether the module should be enabled after installation (defaults to false)
   * @return bool
   */
  public function install($enabled=false)
  {
    if( ! Yii::$app->config->keyExists($this->configKeyEnabled) ){
      Yii::$app->config->createKey($this->configKeyEnabled,"boolean",false, $enabled);
    }
    return true;
  }
}