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

use lib\controllers\ITreeController;
use app\controllers\AppController;
use app\models\Datasource;
use app\models\Folder;

class FolderController extends AppController //implements ITreeController
{
  use traits\FormTrait;

  /*
  ---------------------------------------------------------------------------
     STATIC PROPERTIES & METHODS
  ---------------------------------------------------------------------------
  */

  /**
   * The main model type of this controller
   */
  static $modelType = "folder";

  /**
   * Icons for the folder nodes, depending on folder type
   * @var array
   */
  static $icon = array(
    "closed"          => null,
    "open"            => null,
    "default"         => "icon/16/places/folder.png",
    "search"          => "icon/16/apps/utilities-graphics-viewer.png",
    "trash"           => "icon/16/places/user-trash.png",
    "trash-full"      => "icon/16/places/user-trash-full.png",
    "marked-deleted"  => "icon/16/actions/folder-new.png",
    "public"          => "icon/16/places/folder-remote.png",
    "favorites"       => "icon/16/actions/help-about.png"
  );



  /**
   * Return the data of a node of the tree as expected by the qooxdoo tree data model
   * @param string $datasource Datasource name
   * @param \app\models\Folder|int $folder
   * @return array
   */
  static function getNodeData( $datasource, $folder )
  {
    // Accept both a folder model and a numeric id
    if ( ! ($folder instanceof Folder ) ){
      assert( \is_numeric( $folder) );
      $folderId = $folder;
      $folder = static::getControlledModel($datasource)::findOne($folder);
      if( ! $folder ){
        throw new \InvalidArgumentException("Folder #$folderId does not exist.");
      }
    }

    $folderType     = $folder->type;
    $owner          = $folder->owner;
    $label          = $folder->label;
    $query          = $folder->query;
    $searchFolder   = (bool) $folder->searchfolder;
    $description    = $folder->description;
    $parentId       = $folder->parentId;

    $childCount     = $folder->getChildCount();
    $referenceCount = $folder->getReferences()->count();
    $markedDeleted  = $folder->markedDeleted;
    $public         = $folder->public;
    $opened         = $folder->opened;
    
    // access
    static $activeUser = null;
    static $isAnonymous = null;
    if ( $activeUser === null )
    {
      $activeUser = Yii::$app->user->getIdentity();
      $isAnonymous = $activeUser->isAnonymous();
    }

    if ( $isAnonymous ) {
      // do not show unpublished folders to anonymous roles
      if ( ! $public )  return null;
    }

    // reference count is zero if folder executes a query
    if ( $query ) {
      $referenceCount = "";
    }

    // icon & type
    $icon = static::$icon["closed"];
    $type = "folder";
    if ( ( $folderType == "search" or !$folderType)
           and $query and $searchFolder != false ) {
      $icon = static::$icon["search"];
      $type = "searchfolder";
    } elseif ( $folderType == "trash" ) {
      $icon = static::$icon["trash"];
      $type = "trash";
    } elseif ( $folderType == "favorites" ) {
      $icon = static::$icon["favorites"];
      $type = "favorites";
    } elseif ( $markedDeleted ) {
      $icon = static::$icon["markedDeleted"];
      $type = "deleted";
    } elseif ( $public ) {
      $icon = static::$icon["public"];
      $type = "folder";
    }

    // return node data
    $data = [
      'isBranch'        => true,
      'label'           => $label,
      'bOpened'         => $opened,
      'icon'            => $icon,
      'iconSelected'    => $icon,
      'bHideOpenClose'  => ($childCount == 0),
      'columnData'      => [ null, $referenceCount ],
      'data'            => [
                          'type'            => $folderType,
                          'id'              => $folder->id,
                          'parentId'        => $folder->parentId,
                          'query'           => $query,
                          'public'          => $public,
                          'owner'           => $owner,
                          'description'     => $description,
                          'datasource'      => $datasource,
                          'childCount'      => $childCount,
                          'referenceCount'  => $referenceCount,
                          'markedDeleted'   => $markedDeleted
                        ]
      ];
    return $data;
  }

  /**
   * Returns the icon for a given folder type
   * @param string $type
   * @throws InvalidArgumentException
   * @return string
   */
  public static function getIcon( $type )
  {
    if ( isset( static::$icon[$type] ) ) {
      return static::$icon[$type];
    } else {
      throw new InvalidArgumentException("Icon for type '$type' does not exist.");
    }
  }  

  /**
   * Adds basic folders
   * @return void
   */
  static function addInitialFolders($datasource)
  {
    // top folder
    $class = static::getControlledModel($datasource);
    $top = new $class([
      "label"       => Yii::t("app","Default Folder"),
      "parentId"    => 0,
      "position"    => 0,
      "childCount"  => 0,
      "public"      => true
    ]);
    $top->save();

    // trash folder
    $trash = new $class([
      "type"        => "trash",
      "label"       => Yii::t("app","Trash Folder"),
      "parentId"    => 0,
      "position"    => 1,
      "childCount"  => 0,
      "public"      => false
    ]);
    $trash->save();
  }

  /*
  ---------------------------------------------------------------------------
     INTERFACE ITreeController
  ---------------------------------------------------------------------------
  */

  /**
   * Returns the number of nodes in a given datasource
   * @param string $datasource
   * @param mixed|null $options Optional data, for example, when nodes
   *   should be filtered by a certain criteria
   * @return array containing the keys 'nodeCount', 'transactionId'
   *   and (optionally) 'statusText'.
   */
  function actionNodeCount( $datasource, $options=null )
  {
    $this->checkDatasourceAccess( $datasource );
    $query = Folder::find();
    if( $this->getActiveUser()->isAnonymous() ){
      $query = $query->where( [ 'public' => true ] );
    }
    $nodeCount = $query->count();
    return array(
      'nodeCount'     => $nodeCount,
      'transactionId' => $model->getTransactionId(),
      'statusText'    => ""
    );
  }

  /**
   * Returns the number of children of a node with the given id
   * in the given datasource.
   *
   * @param $datasource
   * @param $nodeId
   * @param mixed|null $options Optional data, for example, when nodes
   *   should be filtered by a certain criteria
   * @return array
   */
  function actionChildCount( $datasource, $nodeId, $options=null )
  {
    not_implemented();
  }


  /**
   * Returns all nodes of a tree in a given datasource
   * @param string $datasource
   * @param mixed|null $options Optional data, for example, when nodes
   *   should be filtered by a certain criteria
   * //return { nodeData : [], statusText: [] }.
   */  
  function actionLoad( $datasource,  $options=null )
  {
    $query = static::getControlledModel($datasource)::find()->select("id");
    if( $this->getActiveUser()->isAnonymous() ){
      $query = $query->where( [ 'public' => true ] );
    }
    $nodeIds = $query->column();
    $nodeData = array_map( function($id) use ($datasource) {
      return $this->getNodeData($datasource, $id);
    }, $nodeIds );
    return [
      'nodeData'    => $nodeData,
      'statusText'  => count($nodeData) . " Folders loaded." 
    ];
  }

  /**
   * Edit folder data
   * @param $datasource
   * @param $folderId
   * @return string "OK"
   */
  public function actionEdit( $datasource, $folderId )
  {
    $this->requirePermission("folder.edit");
    $model = statid :: getRecordById (§datasource, $folderId);
    $formData = $this->createFormData( $model ); 
    $label = $model->label;
    $message = "<h3>$label</h3>";
    \lib\dialog\Form::create(
      $message, $formData, true,
      Yii::$app->controller->id, "saveFormData",
      array( $datasource, $folderId )
    );
    return "OK";
  }

  /**
   * Saves the result of the edit() method
   * @param $data
   * @param $datasource
   * @param $folderId
   * @return string "OK"/"ABORTED"
   */
  public function actionSave( $data, $datasource, $folderId )
  {
    if ( $data === null ) return "ABORTED";

    $this->requirePermission("folder.edit");

    $model = statid :: getRecordById (§datasource, $folderId);
    $data = $this->parseFormData( $model, $data );
    if( $model->load( $data ) && $model->save() ){
      
      // @todo return service result!
      return "OK";
    } 
    Yii::error($model->getErrors());
    return "ERROR";
  }

  /**
   * Change the public state - creates dialog event.
   * @param $datasource
   * @param $folderId
   * @return void
   */
  public function actionVisibilityDialog( $datasource, $folderId )
  {
    $this->requirePermission("folder.edit");
    $folder = static :: getRecordById( $datasource, $folderId );
    return \lib\dialog\Form::create(
      Yii::t('app', "Change the visibility of the folder"),
      array(
        'state' => array(
          'label'   => Yii::t('app', "State"),
          'type'    => "SelectBox",
          'options' => array(
            array( 'label' => Yii::t('app', "Folder is publically visible"), 'value' => true ),
            array( 'label' => Yii::t('app', "Folder is not publically visible"), 'value' => false )
          ),
          'value'   => $folder->public,
          'width'   => 300
        ),
        'recurse' => array(
          'label'   => Yii::t('app', "Depth"),
          'type'    => "SelectBox",
          'options' => array(
            array( 'label' => Yii::t('app', "Apply only to the selected folder"), 'value' => false ),
            array( 'label' => Yii::t('app', "Apply to the selected folder and its subfolders"), 'value' => true )
          ),
          'value'   => false
        )
      ), true,
      Yii::$app->controller->id, "visibility-change", array( $datasource, $folderId )
   );
  }

  /**
   * Change the public state
   *
   * @param string $data
   * @param string $datasource
   * @param int $folderId
   * @return string "OK"
   */
  public function actionVisibilityChange( $data, $datasource, $folderId )
  {
    if ( $data === null ) return "ABORTED";
    $this->requirePermission("folder.edit");
    $data = json_decode(json_encode($data), true); // convert to array

    $folderModel = static :: getControlledModel( $datasource );
    $ids = [$folderId];
    do
    {
      $id = array_shift( $ids );
      if ( ! $id ) break;
      //$this->debug("> $id ",__CLASS__,__LINE__);
      $folder = $folderModel::findOne( $id );
      $folder->public = $data['state'];
      $folder->save();
      if ( $data['recurse'] )
      {
        $ids = array_merge( $ids, $folder->getChildIds() );
      }
    }
    while( count( $ids ) );
    return "OK";
  }


  /**
   * Action to add a folder. Creates a dialog event
   *
   * @param string $datasource
   * @param int $folderId
   * @return void
   */ 
  public function actionAddDialog( $datasource, $folderId )
  {
    $this->requirePermission("folder.add");
    return \lib\dialog\Form::create(
      Yii::t('app',"Please enter the name and type of the new folder:"),
      array(
        'label' => array(
          'label'   => Yii::t('app', "Name"),
          'type'    => "textfield",
          'width'   => 200
        ),
        'searchfolder' => array(
          'label'   => Yii::t('app', "Type"),
          'type'    => "SelectBox",
          'options' => array(
            array( 'label' => Yii::t('app', "Normal folder"), 'value' => false ),
            array( 'label' => Yii::t('app', "Search folder"), 'value' => true )
          ),
          'value'   => false
        )
      ), true,
      Yii::$app->controller->id, "create", array( $datasource, $folderId )
    );
  }

  /**
   * Creates a new folder
   * @param $data
   * @param $datasource
   * @param $parentFolderId
   * @return string "OK"
   */
  public function actionCreate( $data, $datasource, $parentFolderId )
  {
    if ( $data === null or $data->label =="" ) return "ABORTED";
    $this->requirePermission("folder.add");
    $folderModel = static :: getControlledModel( $datasource );
    $folder = new $folderModel([
      'parentId'      => $parentFolderId,
      'label'         => $data->label,
      'searchfolder'  => $data->searchfolder,
      'createdBy'     => $this->getActiveUser()->getUsername()
    ]);
    return "OK";
  }

  /**
   * Creates a confimation dialog to remove a folder
   * @param $datasource
   * @param $folderId
   * @return void
   */
  public function actionRemoveDialog( $datasource, $folderId )
  {
    $this->requirePermission("folder.remove");
    $folder = static :: getRecordById( $datasource, $folderId );
    // root folder?
    if( $model->parentId == 0 ) {
      throw new \Exception(Yii::t('app', "Top folders cannot be deleted."));
    }

    // create dialog
    return \lib\dialog\Confirm::create(
      Yii::t('app', "Do you really want to move the folder '{name}' into the trash?", [
        'name' => $model->label
      ]),
      null,
      Yii::$app->controller->id, "remove", array( $datasource, $folderId )
    );
  }

  /**
   * Removes the given folder
   * @param $data
   * @param $datasource
   * @param $folderId
   * @return string "OK"
   */
  public function actionRemove( $data, $datasource, $folderId )
  {
    if ( ! $data ) return "ABORTED";
    $this->requirePermission("folder.remove");
    
    $folder = static :: getRecordById( $datasource, $folderId );
    if( $folder->parentId == 0 ) {
      throw new \Exception(Yii::t('app', "Top folders cannot be deleted."));
    }

    // move folder into trash
    $trashFolder = \app\controllers\TrashController::getTrashFolder();
    if( $trashFolder ){
      if ( $folder->parentId == $trashFolder->id ) {
        // it is already in the trash, delete right away
        $folder->delete();
      } else {
        $folder->parentId = $trashFolder->id;
      }
    }

    // mark references as deleted
    $this->setFolderMarkedDeleted( $folder, true);
    return "OK";
  }

  /**
   * Marks a folder and its content as deleted or undeleted, depending on the
   * second (boolean) value. If  this value is true, all references in the folder
   * will be removed, leaving only those which are not linked in any other
   * folders, and marking them as deleted. If the value is false, the folders
   * and contained references will be marked "not deleted"
   *
   * @param \app\models\Folder $folder
   * @param bool $value
   * @return void
   */
  public function setFolderMarkedDeleted( \app\models\Folder $folder, $value )
  {
    // mark folder (un)deleted
    $folder->markedDeleted = $value;
    $folder->save();

    // handle contained references
    $references = $folder -> getReferences() -> all();
    foreach( $references as $reference ){
      if ( $value ) {
        $folderCount = $reference->getFolders()->count();
        if ( $folderCount > 1 ) {
          // if it is contained in other folders, simply unlink reference and folder
          $folder->unlink( $reference );
          $folder->getReferenceCount(true);
        } else {
          // if it is contained in this folder only,  mark deleted 
          $folder->markedDeleted = true;
          $folder->save();
        }
      } else {
        $folder->markedDeleted = false;
        $folder->save();
      }
    }
    
    // child folders
    $childFolders = $folder->getChildren();
    foreach( $childFolders as $folder ){
      $this->setFolderMarkedDeleted( $folder, $value );
    }
  }  

  /**
   * Move a folder to a different parent
   * @param $datasource
   * @param $folderId
   * @param $parentId
   * @throws JsonRpcException
   * @return string "OK"
   */
  public function actionMove( $datasource, $folderId, $parentId )
  {
    if( $folderId == $parentId )
    {
      throw new \Exception(Yii::t('app', "Folder cannot be moved on itself."));
    }
    $this->requirePermission("folder.move");
    $id = $parentId;
    do {
      $folder = static :: getRecordById( $datasource, $id );
      if( $folder->id == $folderId )
      {
        throw new \Exception(Yii::t('app', "Parent node cannot be moved on a child node"));
      }
      $id = $folder->parentId;
    }
    while ( $id !== 0 );

    // change folder parent
    $folder = static :: getRecordById( $datasource, $folderId );

    // root folder?
    if( $folder->parentId == 0 ) {
      throw new \Exception(Yii::t('app', "Top folders cannot be moved."));
    }

    $model->parentId = $parentId;
    $model->save();

    // mark deleted if moved into trash folder
    $trashFolder = \app\controllers\TrashController::getTrashfolder( $datasource );
    if( $trashFolder ) {
      $this->setFolderMarkedDeleted( $folder, $parentId === $trashFolder->id );
    }
    return "OK";
  }

  /**
   * Changes the position of a folder within its siblings
   * @param $datasource
   * @param $folderId
   * @param $position
   * @return string "OK"
   */
  public function actionPositionChange( $datasource, $folderId, $position )
  {
    $this->requirePermission("folder.move");
    $folder = static :: getRecordById( $datasource, $folderId );
    $folder->changePosition( $position );
    // notify clients
    $this->broadcastClientMessage(
      "folder.node.reorder", array(
        'datasource'    => $datasource,
        'modelType'     => "folder",
        'nodeId'        => $folderId,
        'parentNodeId'  => $folder->parentId,
        'position'      => $position,
        'transactionId' => $folder->transactionId
      )
    );
    return "OK";
  }
}
