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

use Yii;

use app\controllers\AppController;
use app\models\Datasource;

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
   * Purges folders that have been marked for deletion
   * @param string $datasource
   * @return string "OK"
   */
  public function empty( $datasource )
  {
    $this->requirePermission("trash.empty");

    // folder
    $trashfolder = static :: getTrashfolder( $datasource );
    $childFolders = $trashfolder->getChildren();
    foreach( $childFolders as $folder ){
      $folder -> delete();
    }
    
    // references
    self::getReferenceModel()::deleteAll( ['markedDeleted' => true] );

    // update reference count
    $trashfolder->getReferenceCount(true);

    // notify clients
    $this->broadcastClientMessage("folder.reload",array(
      'datasource'  => $datasource,
      'folderId'    => $trashfolder->id
    ));
    
    return "OK";
  }
  
}