<?php

namespace tests\unit\models;

use app\models\BibliographicDatasource;
use Yii;
use tests\unit\Base;
use app\models\Datasource;

class DatasourceTest extends Base
{
  /**
   * @var \UnitTester
   */
  protected $tester;

  public function _fixtures()
  {
    return require APP_TESTS_DIR . '/tests/fixtures/_combined_models.php';
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
    $this->assertEquals(BibliographicDatasource::class, \get_class($datasource));
    $this->assertEquals('mysql:host=host.docker.internal;port=3306;dbname=tests', $datasource->getConnection()->dsn);
  }

  public function testDatasourceModels()
  {
    $datasource = Datasource::getInstanceFor('database1');
    $folderClass = $datasource->getClassFor('folder');
    $this->assertEquals( 'app\models\Folder', $folderClass );
    $this->assertEquals( 'database1', $folderClass::getDatasource()->namedId );
    $this->assertSame( $folderClass::getDb(), $datasource->getConnection(), "Model does not inherit connection from datasource." );
    $folder = $folderClass::findOne(['label'=>'Hauptordner']);
    $this->assertFalse( is_null($folder), "Folder model not found." );
    $this->assertEquals( Datasource::in('database1','folder'), $datasource->getClassFor('folder') );
    $this->assertEquals( Datasource::in('database1.reference'), $datasource->getClassFor('reference') );
    $numEnglishRefs = Datasource::in('database1.reference')::find()->where(['language'=>'English'])->count();
    $this->assertEquals( 12, $numEnglishRefs );
  }

  public function testCreateDatasource()
  {
    Yii::$app->datasourceManager->create("test2");
    $datasource = Datasource::getInstanceFor("test2");
    $this->assertEquals(\app\models\BibliographicDatasource::class,\get_class($datasource));
    $tableName = "test2_migration";
    $this->assertFalse(is_null(Yii::$app->db->schema->getTableSchema($tableName)), "$tableName has not been created!" );
    foreach($datasource->modelTypes() as $type){
      $tableName = "test2_data_" . ucfirst($type);
      $this->assertFalse(is_null(Yii::$app->db->schema->getTableSchema($tableName)), "$tableName has not been created!" );
    }
  }
}
