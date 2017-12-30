<?php

namespace app\tests\unit\models;

// for whatever reason, this is not loaded early enough
require_once __DIR__ . "/../../_bootstrap.php";

use app\tests\unit\models\Base;
use app\models\Folder;
use app\models\Reference;


class BiblioModelsTest extends Base
{
  /**
   * @var \UnitTester
   */
  protected $tester;

  public function _fixtures()
  {
      return include __DIR__ . '/../../fixtures/_biblio_models.php';
  }

  public function testFolderChildren()
  {
    $folder = Folder::findOne(['label'=>'Hauptordner']);
    $this->assertEquals(false, is_null($folder), "Main folder not found");
    $this->assertEquals([3,4], $folder->getChildIds());
  }

  public function testFolderContents()
  {
    $folder = Folder::findOne(['label'=>'Hauptordner']);
    $query = $folder->getReferences();
    $this->assertEquals(22,$query->count());
    $this->assertEquals("Digital library economics Elektronische Ressource: An academic perspective",$query->one()->title);
  }

  public function testChildrenData()
  {
    $folder = Folder::findOne(['label'=>'Hauptordner']);
    $data = $folder->getChildrenData();
    $this->assertEquals( 'Reference Management Software', $data[0]['label'] );
  }

  public function testChangeFolderPosition()
  {
    $this->assertEquals( 0, Folder::findOne(['id'=>3])->position );
    $this->assertEquals( 1, Folder::findOne(['id'=>4])->position );
    
    Folder::findOne(['id'=>3])->changePosition("+1");
    $this->assertEquals( 1, Folder::findOne(['id'=>3])->position );
    $this->assertEquals( 0, Folder::findOne(['id'=>4])->position );

    $this->expectException( \InvalidArgumentException::class);
    Folder::findOne(['id'=>3])->changePosition("+2");
  }

  public function testFolderLabelPath()
  {
    $this->assertEquals(
      'Hauptordner/Reference Management Software/Foo', 
      Folder::findOne(['id'=>5])->labelPath() 
    );
  }

  public function testSetParentFolder()
  {
    Folder::findOne(['id'=>5])
      ->setParent(Folder::findOne(['id'=>4]));
    $this->assertEquals(
      'Hauptordner/Zotero/Foo', 
      Folder::findOne(['id'=>5])->labelPath() 
    );      
  }
}
