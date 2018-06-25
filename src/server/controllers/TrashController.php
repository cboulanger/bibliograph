<?php
/* ************************************************************************

   Bibliograph: Collaborative Online Reference Management

   http://www.bibliograph.org

   Copyright:
     2007-2018 Christian Boulanger

   License:
     LGPL: http://www.gnu.org/licenses/lgpl.html
     EPL: http://www.eclipse.org/org/documents/epl-v10.php
     See the LICENSE file in the project's top-level directory for details.

   Authors:
     * Chritian Boulanger (cboulanger)

************************************************************************ */

namespace app\controllers;

use app\models\Folder;
use lib\exceptions\UserErrorException;
use Yii;

use app\controllers\AppController;
use app\models\Datasource;
use yii\db\Exception;

class TrashController extends AppController
{

  /**
   * Returns the name of the folder model class
   *
   * @param string $datasource
   * @return string
   */
  static function getReferenceModel( $datasource )
  {
    return Datasource
      :: getInstanceFor( $datasource )
      -> getClassFor( "reference" );
  }
  
  /**
   * Returns the name of the folder model class
   *
   * @param string $datasource
   * @return string
   */
  static function getFolderModel( $datasource )
  {
    return Datasource
      :: getInstanceFor( $datasource )
      -> getClassFor( "folder" );
  }

  /**
   * Return the trash folder of the given datasource or null if it doesn't have one
   * @param string $datasource
   * @return \app\models\Folder|null
   */
  static function getTrashFolder( $datasource )
  {
    $trashFolder = Datasource :: in( $datasource, "folder" ) :: findOne(['type'=>'trash']);
    return $trashFolder;
  }

  /**
   * Empties the trash folder
   * @param string $datasource
   * @return string Diagnostic message
   * @throws \JsonRpc2\Exception
   */
  public function actionEmpty( string $datasource )
  {
    $this->requirePermission("trash.empty");
    // folder
    $trashfolder = static::getTrashfolder( $datasource );

    if(!$trashfolder) throw new UserErrorException(
      Yii::t('app', "Datasource '{datasource}' does not have a trash folder.", [
        'datasource' => $datasource
      ])
    );
    /** @var Folder $childFolders */
    $childFolders = $trashfolder->getChildren();
    foreach( $childFolders as $folder ){
      $folder->delete();
    }
    // references
    self::getReferenceModel($datasource)::deleteAll( ['markedDeleted' => true] );

    // update reference count
    try {
      $trashfolder->getReferenceCount(true);
    } catch (Exception $e) {
      Yii::error($e);
    }
    $this->broadcastClientMessage("folder.reload", array(
      'datasource' => $datasource,
      'folderId' => $trashfolder->id
    ));

    return "Trash emptied.";
  }
  
}