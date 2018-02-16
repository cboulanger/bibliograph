<?php

namespace app\tests\unit\models;

// for whatever reason, this is not loaded early enough
require_once __DIR__ . "/../../_bootstrap.php";

use Yii;
use app\tests\unit\Base;
use app\models\Datasource;

class DatasourceTest extends Base
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

  public function testDatasourceInstance()
  {
    $datasource = Datasource::getInstanceFor('database1');
    $this->assertEquals('app\models\BibliographicDatasource',\get_class($datasource));
    $this->assertEquals('mysql:host=localhost;port=3306;dbname=tests', $datasource->getConnection()->dsn);
  }

  public function testDatasourceModels()
  {
    $datasource = Datasource::getInstanceFor('database1');
    $folderClass = $datasource->getClassFor('folder');
    $this->assertEquals( 'app\models\Folder', $folderClass );
    $this->assertEquals( 'database1', $folderClass::getDatasource() );
    $this->assertSame( $folderClass::getDb(), $datasource->getConnection(), "Model does not inherit connection from datasource." );
    $folder = $folderClass::findOne(['label'=>'Hauptordner']);
    $this->assertFalse( is_null($folder), "Folder model not found." );
    $this->assertEquals( Datasource::in('database1','folder'), $datasource->getClassFor('folder') );
    $this->assertEquals( Datasource::in('database1.reference'), $datasource->getClassFor('reference') );
    $numEnglishRefs = Datasource::in('database1.reference')::find()->where(['language'=>'English'])->count();
    $this->assertEquals( 15, $numEnglishRefs );    
  }

  public function testCreateDatasource()
  {
    $datasource = Datasource::create("test2");
    $datasource->title = "Test Datasource 2";
    $datasource->save();
    // get specialized subclass
    $datasource = Datasource::getInstanceFor("test2");
    $this->assertEquals('app\models\BibliographicDatasource',\get_class($datasource));
    $datasource->createModelTables();
    $tableName = "test2_migration";
    $this->assertFalse(is_null(Yii::$app->db->schema->getTableSchema($tableName)), "$tableName has not been created!" );
    foreach($datasource->modelTypes() as $type){
      $tableName = "test2_data_" . ucfirst($type);
      $this->assertFalse(is_null(Yii::$app->db->schema->getTableSchema($tableName)), "$tableName has not been created!" );
    }
  }
}
