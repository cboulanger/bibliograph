<?php

namespace app\modules\converters;

use app\models\ImportFormat;
use app\modules\converters\import\AbstractParser;
use Yii;

class Module extends \lib\Module
{

  /**
   * A string constant defining the category for logging and translation
   */
  const CATEGORY="converters";

  /**
   * The version of the module
   * @var string
   */
  protected $version = "0.0.2";

  /**
   * Defines the converter classes to install
   * @var array
   */
  protected $install_classes = [
    'import' =>['BibtexUtf8']
  ];

  /**
   * Installs the plugin.
   * @param boolean $enabled
   *    Whether the module should be enabled after installation (defaults to false)
   * @return boolean
   */
  public function install($enabled = false)
  {
    foreach ($this->install_classes as $converterType => $converterData) {
      $registryClass = sprintf("\\app\\models\\%sFormat", ucfirst($converterType));
      foreach ($converterData as $install_class) {
        try {
          $converterClass = sprintf("\\app\\modules\\%s\\%s\\%s", $this->id, $converterType, $install_class);
          /** @var AbstractParser $converter */
          $converter = new $converterClass();
          $attributes = [
            'namedId' => $converter->id,
            'extension' => $converter->extension,
            'name' => $converter->name,
            'type' => $converter->type,
            'description' => $converter->description,
            'active' => 1,
            'class' => $converterClass
          ];
          /** @var ImportFormat $registry */
          $registry = $registryClass::find()
            ->where(['namedId'=>$converter->id])
            ->exists()
              ? $registryClass::findOne(['namedId'=>$converter->id])
              : new $registryClass();
          $registry->setAttributes($attributes);
          $registry->save();
        } catch (\yii\db\Exception $e) {
          Yii::warning($e->getMessage());
        }
      }
    }
    return parent::install(true);
  }
}
