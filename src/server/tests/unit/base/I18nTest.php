<?php

namespace app\tests\unit\base;

// for whatever reason, this is not loaded early enough
require_once __DIR__ . "/../../_bootstrap.php";

use Yii;
use yii\i18n\GettextMessageSource;

class I18nTest extends \app\tests\unit\Base
{
  /**
   * @var \UnitTester
   */
  protected $tester;


  public function testTranslation()
  {
    $tests = [
      'en-US' => [
        'Folder' => 'Folder'
      ],
      'de-DE' => [
        'Folder' => 'Ordner'
      ]
    ];
    foreach ($tests as $locale => $translations) {
      Yii::$app->language = $locale;
      foreach ($translations as $original => $translation) {
        $this->tester->assertEquals($translation, Yii::t('app', $original));
      }
    }
  }
}