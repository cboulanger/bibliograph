<?php
/**
 * Created by PhpStorm.
 * User: cboulanger
 * Date: 03.04.18
 * Time: 23:28
 */

namespace modules\extendedfields;

use app\modules\extendedfields\Reference;

class Datasource extends \app\models\BibliographicDatasource
{
  static $name = "Extended";

  static $description = "Bibliograph Datasource with extended field list";

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