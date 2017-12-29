<?php

namespace app\tests\unit\models;

use app\models\Datasource;

// for whatever reason, this is not loaded early enough
require_once __DIR__ . "/../../_bootstrap.php";

/**
 * Undocumented class
 */
class DatasourceTest extends \Codeception\Test\Unit
{
  /**
   * @var \UnitTester
   */
  protected $tester;

  public function _fixtures()
  {
      return include __DIR__ . '/../../fixtures/_biblio_models.php';
  }

  public function testDatasourceExists()
  {
    $datasource = Datasource::findOne(['namedId'=>'database1']);
    $this->assertEquals(false, is_null($datasource), "Cannot find datasource 'database1'");
    $this->assertEquals('mysql', $datasource->type);
  }
}
