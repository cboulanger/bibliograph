<?php
/* ************************************************************************

   Bibliograph: Collaborative Online Reference Management

   http://www.bibliograph.org

   Copyright:
   2004-2017 Christian Boulanger

   License:
   LGPL: http://www.gnu.org/licenses/lgpl.html
   EPL: http://www.eclipse.org/org/documents/epl-v10.php
   See the LICENSE file in the project's top-level directory for details.

   Authors:
   *  Christian Boulanger (cboulanger)

************************************************************************ */

namespace app\models;
use Yii;

/**
 * model for bibliograph datasources based on an sql database
 */
class BibliographicDatasource
  extends Datasource
  implements \lib\schema\ISchema
{

  /**
   * The named id of the datasource schema
   */
  const SCHEMA_ID = "bibliograph_datasource";

  /**
   * @var string
   */
  static $name = "Bibliograph";

  /**
   * @var string
   */
  static $description = "Standard Bibliograph Datasource";

  /**
   * @return string
   */
  public function getTableModelType()
  {
    return 'reference';
  }

  /**
   * Initialize the datasource, registers the models
   * @throws \InvalidArgumentException
   */
  public function init()
  {
    parent::init();
    $this->addModel( 'reference',   Reference::class,   'reference');
    $this->addModel( 'folder',      Folder::class,      'folder');
    $this->addModel( 'transaction', Transaction::class);
  }

  /**
   * Creates the default folders for the datasource
   *
   * @return void
   * @throws \yii\db\Exception
   */
  public function addDefaultFolders()
  {
    $folderData = [
      [
        'parentId' => 0,
        'position' => 1,
        'label' => Yii::t('app', 'Main folder'),
        'description' => Yii::t('app', 'This is the main folder of the database'),
        'searchable' => 1,
        'searchfolder' => 0,
        'type' => "folder",
        'public' => 1,
        'opened' => 1,
        'locked' => 0,
        'hidden' => 0,
        'markedDeleted' => 0,
      ],
      [
        'parentId' => 0,
        'position' => 2,
        'label' => Yii::t('app', 'Trash'),
        'description' => Yii::t('app', 'This folder contains deleted items'),
        'type' => "trash",
        'searchable' => 0,
        'searchfolder' => 0,
        'public' => 0,
        'opened' => 0,
        'locked' => 0,
        'hidden' => 0,
        'markedDeleted' => 0,
      ],
    ];
    $folderClass = $this->getClassFor('folder');
    foreach( $folderData as $data ){
      /** @var \app\models\Folder $folder */
      $folder = new $folderClass();
      $folder->setAttributes( $data );
      $folder->save();
    }
    Yii::info("Created default folders for datasource '{$this->namedId}' ");
  }
}
