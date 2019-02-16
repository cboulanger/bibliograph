<?php

namespace app\tests\unit\models;

// for whatever reason, this is not loaded early enough
require_once __DIR__ . "/../../_bootstrap.php";

use app\tests\unit\Base;
use app\models\Datasource;

class BiblioModelsTest extends Base
{
  /**
   * @var \UnitTester
   */
  protected $tester;

  public function _fixtures()
  {
    return require __DIR__ . '/../../fixtures/_combined_models.php';
  }

  public function testFolderChildren()
  {
    $folder = Datasource::in('database1.folder')::findOne(['label'=>'Hauptordner']);
    $this->assertFalse(is_null($folder), "Main folder not found");
    $this->assertEquals([3,4], $folder->getChildIds());
  }

  public function testFolderContents()
  {
    $folder = Datasource::in('database1.folder')::findOne(['label'=>'Hauptordner']);
    $this->assertFalse(is_null($folder), "Main folder not found");
    $query = $folder->getReferences();
    $this->assertEquals(10,$query->count());
    $this->assertEquals("Digital library economics Elektronische Ressource: An academic perspective",$query->one()->title);
  }

  public function testChildrenData()
  {
    $folder = Datasource::in('database1.folder')::findOne(['label'=>'Hauptordner']);
    $this->assertFalse(is_null($folder), "Main folder not found");
    $data = $folder->getChildrenData();
    $this->assertEquals( 'Reference Management Software', $data[0]['label'] );
  }

  public function testChangeFolderPosition()
  {
    $folderClass = Datasource::in('database1.folder');
    $this->assertEquals( 0, $folderClass::findOne(['id'=>3])->position );
    $this->assertEquals( 1, $folderClass::findOne(['id'=>4])->position );
    
    $folderClass::findOne(['id'=>3])->changePosition("+1");
    $this->assertEquals( 1, $folderClass::findOne(['id'=>3])->position );
    $this->assertEquals( 0, $folderClass::findOne(['id'=>4])->position );

    $this->expectException( \InvalidArgumentException::class);
    $folderClass::findOne(['id'=>3])->changePosition("+2");
  }

  public function testFolderLabelPath()
  {
    $folderClass = Datasource::in('database1.folder');
    $this->assertEquals(
      'Hauptordner/Reference Management Software/Foo', 
      $folderClass::findOne(['id'=>5])->labelPath() 
    );
  }

  public function testSetParentFolder()
  {
    $folderClass = Datasource::in('database1.folder');
    $folderClass::findOne(5)->setParent($folderClass::findOne(4));
    $this -> assertEquals(
      'Hauptordner/Zotero/Foo', 
      $folderClass::findOne(5)->labelPath()
    );      
  }
}
