<?php

namespace app\modules\extendedfields;

use app\models\Schema;
use modules\extendedfields\Datasource;
use Yii;
use lib\exceptions\RecordExistsException;


class Module extends \lib\Module
{

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
      // ignore
    }

    // register module
    return parent::install(true);
  }
}
