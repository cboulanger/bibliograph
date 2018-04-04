<?php

namespace app\modules\extendedfields;

use app\models\Schema;
use Yii;
use lib\exceptions\RecordExistsException;


class Module extends \lib\Module
{
  /**
   * The version of the module
   * @var string
   */
  protected $version = "0.0.2";

  /**
   * Installs the plugin.
   * @param boolean $enabled
   *    Whether the module should be enabled after installation (defaults to false)
   * @return boolean
   * @throws \Exception
   */
  public function install($enabled = false)
  {
    // register datasource
    try {
      Schema::register("bibliograph_extended", Datasource::class);
    } catch (RecordExistsException $e) {
      Yii::debug("Extended fields schema already registered.");
    }

    // register module
    return parent::install(true);
  }
}
