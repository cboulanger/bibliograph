<?php

namespace app\modules\zotero;

use app\models\Schema;
use app\modules\zotero\models\Datasource;
use lib\exceptions\RecordExistsException;
use lib\plugin\PluginInterface;
use Yii;

class Module
  extends \lib\Module
  implements PluginInterface
{

  /**
   * The version of the module
   * @var string
   */
  protected $version = "0.0.5";

  /**
   * Installs the plugin.
   * @param boolean $enabled
   *    Whether the module should be enabled after installation (defaults to false)
   * @return boolean
   */
  public function install($enabled = false)
  {
    $schema_id = Datasource::SCHEMA_ID;
    // register schema
    try {
      Schema::register($schema_id, Datasource::class);
    } catch (RecordExistsException $e) {
      Yii::info("Schema '$schema_id' already registered.");
    }
    return parent::install(true);
  }
}
