<?php

namespace app\modules\bibutils;

use Yii;


class Module extends \lib\Module
{
  /**
   * The version of the module
   * @var string
   */
  protected $version = "0.0.1";

  /**
   * Installs the plugin.
   * @param boolean $enabled
   *    Whether the module should be enabled after installation (defaults to false)
   * @return boolean
   * @throws \Exception
   */
  public function install($enabled = false)
  {
    try {
      // register bibutils import formats
    } catch (\yii\db\Exception $e) {
      Yii::debug("Bibutils import/export format ... already registered.");
    }
    return parent::install(true);
  }
}
