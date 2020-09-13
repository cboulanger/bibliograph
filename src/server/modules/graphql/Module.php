<?php

namespace app\modules\graphql;

use lib\plugin\PluginInterface;

class Module
  extends \lib\Module
  implements PluginInterface
{

  /**
   * @inheritdoc
   */
  public $controllerNamespace =  __NAMESPACE__;

  /**
   * @inheritDoc
   * @return bool|void
   */
  public function install($enabled = false){
    parent::install(true);
  }
}
