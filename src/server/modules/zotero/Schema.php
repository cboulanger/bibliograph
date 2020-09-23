<?php

namespace app\modules\zotero;

use lib\schema\Field;
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
      $itemType = ItemType::createInstance([
        'name'  => $name,
        'label' => $locales['en-US']['itemTypes'][$name]
      ]);
      foreach ($type['fields'] as $data) {
        $name = $data['field'];
        $field = Field::createInstance([
            'name'  => $name,
            'label' => $locales['en-US']['fields'][$name]
          ], Field::DUPLICATE_IGNORE);
        $itemType->addField($field);
        if (isset($data['baseField'])) {
          $name = $data['baseField'];
          $alias = Field::createInstance([
            'name'  => $name,
            'label' => $locales['en-US']['fields'][$name] ?? $name
          ], Field::DUPLICATE_IGNORE);
          $field->setAlias($alias);
        }
      }
      $this->addItemType($itemType);
    }
  }
}
