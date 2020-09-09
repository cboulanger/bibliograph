<?php

namespace app\modules\zotero;

use app\schema\AbstractReferenceSchema;

class Schema extends AbstractReferenceSchema {
  /**
   * Constructor
   */
  function init()
  {
    parent::init();
    $zotero_schema = json_decode(file_get_contents(__DIR__ . "/schema.json"), true);
    $zotero_types = $zotero_schema['itemTypes'];
    $locales = $zotero_schema['locales'];
    foreach($zotero_types as $type) {
      $name = $type['itemType'];
      $translation = $locales['en-US']['itemTypes'][$name];
      $data = [
        'label' => $translation,
        'type'  => "string",
        'public' => true,
        'formData' => [],
        'index' => $translation
      ];
      $this->addType($name, $data);
    }
  }
}
