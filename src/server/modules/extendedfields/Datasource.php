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
   * The named id of the datasource schema
   */
  const SCHEMA_ID = "bibliograph_extended";

  /**
   * @inheritdoc
   * @var string
   */
  static $name = "Extended Fields";

  /**
   * @inheritdoc
   * @var string
   */
  static $description = "An example how to extend the standard datasource schema";

  /**
   * The migration namespace
   * @return string
   */
  public function getMigrationNamespace()
  {
    return "\\app\\modules\\extendedfields\\migrations";
  }

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