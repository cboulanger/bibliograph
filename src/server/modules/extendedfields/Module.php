<?php

namespace app\modules\extendedfields;


use app\models\Schema;
use app\modules\extendedfields\Datasource;
use Yii;
use lib\exceptions\RecordExistsException;


class Module extends \lib\Module
{
  /**
   * The version of the module
   * @var string
   */
  protected $version = "0.0.4";

  /**
   * Installs the plugin.
   * @param boolean $enabled
   *    Whether the module should be enabled after installation (defaults to false)
   * @return boolean
   * @throws \Exception
   */
  public function install($enabled = false)
  {
    // register schema
    try {
      Schema::register(Datasource::SCHEMA_ID, Datasource::class);
    } catch (RecordExistsException $e) {
      Yii::info("Extended fields datasource schema already registered.");
    }

    // register module
    return parent::install(true);
  }
}
