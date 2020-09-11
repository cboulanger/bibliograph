<?php

namespace app\modules\zotero;

use lib\schema\ItemType;

class Schema extends \lib\schema\Schema {

  public function __construct($config = [])
  {
    $config['name'] = 'zotero';
    parent::__construct($config);
  }

  /**
   * Initialization
   */
  function init()
  {
    parent::init();
    $zotero_schema = json_decode(file_get_contents(__DIR__ . "/schema.json"), true);
    $zotero_types = $zotero_schema['itemTypes'];
    $locales = $zotero_schema['locales'];
    foreach($zotero_types as $type) {
      $name = $type['itemType'];
      $this->addItemType(ItemType::createInstance([
        'name'  => $name,
        'label' => $locales['en-US']['itemTypes'][$name]
      ]));
    }
  }
}
