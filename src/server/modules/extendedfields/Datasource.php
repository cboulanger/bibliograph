<?php
/**
 * Created by PhpStorm.
 * User: cboulanger
 * Date: 03.04.18
 * Time: 23:28
 */

namespace app\modules\extendedfields;

class Datasource extends \app\models\BibliographicDatasource
{
  /**
   * Override schema migration namespace
   * @var string
   */
  static $migrationNamespace = "\\app\\modules\\extendedfields\\migrations";

  /**
   * Initialize the datasource, registers the models
   * @throws \InvalidArgumentException
   */
  public function init()
  {
    parent::init();
    $this->addModel( 'reference',   Reference::class,   'reference');
  }
}