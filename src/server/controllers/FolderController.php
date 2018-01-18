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
   * @param \app\models\Folder|int $nodee
   * @return array
   */
  static function getNodeData( $datasource, $node )
  {
    if ( $node instanceof Folder ){
      $folder = $node;
    } else {
      assert( \is_numeric( $node) );
      $folder = static::controlledModel($datasource)::findOne($node);
      if( ! $folder ){
        throw new \InvalidArgumentException("Folder #$nodeId does not exist.");
      }      
    }

    $folderType     = $folder->type;
    $owner          = $folder->owner;
    $label          = $folder->label;
    $query          = $folder->query;
    $searchFolder   = (bool) $folder->searchfolder;
    $description    = $folder->description;
    $parentId       = is_null( $parentId ) ? $folder->parentId : $parentId;

    $childCount     = $folder->getChildCount();
    $referenceCount = static::getReferenceCount( $folder, $datasource );
    $markedDeleted  = $folder->markedDeleted;
    $public         = $folder->public;
    $opened         = $folder->opened;
    
    // access
    static $activeUser = null;
    static $isAnonymous = null;
    if ( $activeUser === null )
    {
      $activeUser = Yii::$app->user->identity();
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
    } else {
      $icon = static::$icon["closed"];
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
                          'type'            => $type,
                          'id'              => $nodeId,
                          'parentId'        => $parentId,
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
    $class = static::controlledModel($datasource);
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
   * Edit folder data
   * @param $datasource
   * @param $folderId
   * @return string "OK"
   */
  public function actionEdit( $datasource, $folderId )
  {
    $this->requirePermission("folder.edit");
    $model = statid :: getModelbyId (§datasource, $folderId);
    $formData = $this->createFormData( $model ); 
    $label = $model->label;
    $message = "<h3>$label</h3>";
    \lib\dialog\Form::create(
      $message, $formData, true,
      $this->serviceName(), "saveFormData",
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
  public function actionSaveFormData( $data, $datasource, $folderId )
  {
    if ( $data === null ) return "ABORTED";

    $this->requirePermission("folder.edit");

    $model = statid :: getModelbyId (§datasource, $folderId);
    $data = $this->parseFormData( $model, $data );
    if( $model->load( $data ) && $model->save() ){
      
      // @todo return service result!
      return "OK";
    } 
    Yii::error($model->getErrors());
    return "ERROR";

    
  }

  /**
   * Change the public state - returns a menu.
   * @param $datasource
   * @param $folderId
   * @return qcl_ui_dialog_Form
   */
  public function method_changePublicStateDialog( $datasource, $folderId )
  {
    $this->requirePermission("folder.edit");
    $model = $this->getFolderModel( $datasource );
    $model->load( $folderId );

    qcl_import("qcl_ui_dialog_Form");
    return new qcl_ui_dialog_Form(
      _("Change the visibility of the folder"),
      array(
        'state' => array(
          'label'   => _("State"),
          'type'    => "SelectBox",
          'options' => array(
            array( 'label' => _("Folder is publically visible"), 'value' => true ),
            array( 'label' => _("Folder is not publically visible"), 'value' => false )
          ),
          'value'   => $model->getPublic(),
          'width'   => 300
        ),
        'recurse' => array(
          'label'   => _("Depth"),
          'type'    => "SelectBox",
          'options' => array(
            array( 'label' => _("Apply only to the selected folder"), 'value' => false ),
            array( 'label' => _("Apply to the selected folder and its subfolders"), 'value' => true )
          ),
          'value'   => false
        )
      ), true,
      $this->serviceName(), "changePublicState", array( $datasource, $folderId )
   );
  }

  /**
   * Change the public state
   *
   * @param $data
   * @param $datasource
   * @param $folderId
   * @return string "OK"
   */
  public function method_changePublicState( $data, $datasource, $folderId )
  {
    if ( $data === null ) return "ABORTED";
    $this->requirePermission("folder.edit");
    $data = object2array( $data );
    qcl_assert_array_keys( $data, array( "recurse","state" ) );

    $model = $this->getFolderModel( $datasource );
    $ids = array( $folderId );
    do
    {
      $id = array_shift( $ids );
      if ( ! $id ) break;
      //$this->debug("> $id ",__CLASS__,__LINE__);
      $model->load( $id );
      $model->set( "public", $data['state'] );
      $model->save();
      if ( $data['recurse'] )
      {
        $ids = array_merge( $ids, $model->getChildIds() );
      }
    }
    while( count( $ids ) );
    return "OK";
  }


  public function method_addFolderDialog( $datasource, $folderId )
  {
    $this->requirePermission("folder.add");
    qcl_import("qcl_ui_dialog_Form");

    return new qcl_ui_dialog_Form(
      $this->tr("Please enter the name and type of the new folder:"),
      array(
        'label' => array(
          'label'   => _("Name"),
          'type'    => "textfield",
          'width'   => 200
        ),
        'searchfolder' => array(
          'label'   => _("Type"),
          'type'    => "SelectBox",
          'options' => array(
            array( 'label' => _("Normal folder"), 'value' => false ),
            array( 'label' => _("Search folder"), 'value' => true )
          ),
          'value'   => false
        )
      ), true,
      $this->serviceName(), "addFolder", array( $datasource, $folderId )
    );
  }

  /**
   * Creates a new folder
   * @param $data
   * @param $datasource
   * @param $folderId
   * @return string "OK"
   */
  public function method_addFolder( $data, $datasource, $folderId )
  {
    /*
     * check arguments
     */
    if ( $data === null or $data->label =="" ) return "ABORTED";
    qcl_assert_valid_string( $datasource );
    qcl_assert_integer( $folderId );

    /*
     * check access
     */
    $this->requirePermission("folder.add");
    $this->checkDatasourceAccess( $datasource );

    /*
     * create folder
     */
    $model = $this->getFolderModel( $datasource );
    $model->create(array(
      'parentId'      => $folderId,
      'label'         => $data->label,
      'searchfolder'  => $data->searchfolder,
      'createdBy'     => $this->getActiveUser()->namedId()
    ));
    return "OK";
  }

  /**
   * Dialog to remove a folder
   * @param $datasource
   * @param $folderId
   * @return qcl_ui_dialog_Confirm
   */
  public function method_removeFolderDialog( $datasource, $folderId )
  {
    // check arguments
    qcl_assert_valid_string( $datasource );
    qcl_assert_integer( $folderId );

    // check access
    $this->checkDatasourceAccess( $datasource );
    $this->requirePermission("folder.remove");

    // model
    $model = $this->getFolderModel( $datasource );
    $model->load( $folderId );

    // root folder?
    if( $model->getParentId() == 0 )
    {
      throw new qcl_server_ServiceException(_("Top folders cannot be deleted."));
    }

    // create dialog
    $label = $model->getLabel();
    qcl_import("qcl_ui_dialog_Confirm");
    return new qcl_ui_dialog_Confirm(
      sprintf( _( "Do you really want to move the folder '%s' into the trash?"), $label),
      null,
      $this->serviceName(), "removeFolder", array( $datasource, $folderId )
    );
  }

  /**
   * Removes the given folder
   * @param $data
   * @param $datasource
   * @param $folderId
   * @return string "OK"
   */
  public function method_removeFolder( $data, $datasource, $folderId )
  {
    if ( ! $data ) return "ABORTED";

    /*
     * check arguments
     */
    qcl_assert_valid_string( $datasource );
    qcl_assert_integer( $folderId );

    /*
     * check access
     */
    $this->requirePermission("folder.remove");
    $this->checkDatasourceAccess( $datasource );

    /*
     * move folder into trash
     */
    $trashFolderId = $this->getTrashfolderId( $datasource );
    $model = $this->getFolderModel( $datasource );
    $model->load( $folderId );

    // root folder?
    if( $model->getParentId() == 0 )
    {
      throw new qcl_server_ServiceException(_("Top folders cannot be deleted."));
    }

    if ( $model->getParentId() ==  $trashFolderId )
    {
      // it is already in the trash, delete right away
      $model->delete();
    }
    else
    {
      // move to trash and mark as deleted
      $model->setParentId( $trashFolderId )->save();
      $this->setFolderMarkedDeleted( $model, true);
    }
    return "OK";
  }

  /**
   * Move a folder to a different parent
   * @param $datasource
   * @param $folderId
   * @param $parentId
   * @throws JsonRpcException
   * @return string "OK"
   */
  public function method_moveFolder( $datasource, $folderId, $parentId )
  {
    /*
     * check arguments
     */
    qcl_assert_valid_string( $datasource );
    qcl_assert_integer( $folderId );
    qcl_assert_integer( $parentId );
    if( $folderId == $parentId )
    {
      throw new qcl_server_ServiceException(_("Folder cannot be moved on itself."));
    }

    /*
     * check access
     */
    $this->checkDatasourceAccess( $datasource );
    $this->requirePermission("folder.move");

    /*
     * move node
     */
    $model = $this->getFolderModel( $datasource );
    $id = $parentId;
    do
    {
      try
      {
        $model->load( $id );
      }
      catch( qcl_data_model_RecordNotFoundException $e)
      {
        break;
      }
      $id = $model->getParentId();
      if( $id == $folderId )
      {
        throw new qcl_server_ServiceException(_("Parent node cannot be moved on a child node"));
      }
    }
    while ( $id !== 0 );

    /*
     * change folder parent
     */
    $model->load( $folderId );

    // root folder?
    if( $model->getParentId() == 0 )
    {
      throw new qcl_server_ServiceException(_("Top folders cannot be moved."));
    }

    $model->setParentId( $parentId );
    $model->save();

    /*
     * mark deleted if moved into trash folder
     */
    $this->setFolderMarkedDeleted( $model, $parentId === $this->getTrashfolderId( $datasource ) );

    return "OK";
  }

  /**
   * Return the id of the trash folder of the given datasource
   * @param string $datasource
   * @throws JsonRpcException
   * @return int
   */
  public function getTrashfolderId( $datasource )
  {
    qcl_assert_valid_string( $datasource );
    if ( ! $this->__trashFolderIds ) $this->__trashFolderIds = array();
    if ( ! $this->__trashFolderIds[$datasource] )
    {
      $model = $this->getFolderModel( $datasource );
      $ids =  $model->getQueryBehavior()->fetchValues("id",array(
        'type' => "trash"
      ));
      if ( ! count( $ids ) )
      {
        throw new JsonRpcException("Datasource '$datasource' does not have a trash folder!");
      }
      $trashfolderId = (int) $ids[0];
      if( $trashfolderId )
      {
        $this->__trashFolderIds[$datasource] = $trashfolderId;
      }
      else
      {
        throw new JsonRpcException("Datasource '$datasource' does not contain a valid trash folder");
      }
    }
    return $this->__trashFolderIds[$datasource];
  }

  /**
   * Marks a folder and its content as deleted or undeleted, depending on the
   * second (boolean) value. If  this value is true, all references in the folder
   * will be removed, leaving only those which are not linked in any other
   * folders, and marking them as deleted. If the value is false, the folders
   * and contained references will be marked "not deleted"
   *
   * @param $folder
   * @param $value
   * @return void
   */
  public function setFolderMarkedDeleted( bibliograph_model_FolderModel $folder, $value )
  {
    qcl_assert_boolean( $value );

    /*
     * mark folder (un)deleted
     */
    //$this->debug("Marking $folder deleted " . boolString($value),__CLASS__,__LINE__);
    $folder->set("markedDeleted", $value )->save();

    /*
     * handle contained referenced
     */
    $referenceModel = $folder->getRelationBehavior()->getTargetModel("Folder_Reference");
    try
    {
      $referenceModel->findLinked( $folder );
      while ( $referenceModel->loadNext() )
      {
        if ( $value )
        {
          $linkCount = $folder->countLinksWithModel( $referenceModel );
          //$this->debug("$referenceModel has $linkCount links with folders... ",__CLASS__,__LINE__);
          if ( $linkCount > 1 )
          {
            /*
             * unlink reference and folder
             */
            //$this->debug("Unlinking $referenceModel from $folder",__CLASS__,__LINE__);
            $referenceModel->unlinkModel( $folder );

            /*
             * update reference count
             * @todo use event?
             */
            $referenceCount = count( $referenceModel->linkedModelIds( $folder ) );
            $folder->set( "referenceCount", $referenceCount );
            $folder->save();
          }
          else
          {
            //$this->debug("Marking $referenceModel deleted",__CLASS__,__LINE__);
            $referenceModel->set( "markedDeleted", true );
            $referenceModel->save();
          }
        }
        else
        {
          //$this->debug("Marking $referenceModel as not deleted",__CLASS__,__LINE__);
          $referenceModel->set( "markedDeleted", false );
          $referenceModel->save();
        }
      }
    }
    catch(qcl_data_model_RecordNotFoundException $e){}

    /*
     * child folders
     */
    try
    {
      $query = $folder->findChildren();
      //$this->debug("$folder has {$query->rowCount} children",__CLASS__,__LINE__);
      if ( $query->getRowCount() ) while( $folder->loadNext( $query ) )
      {
        $this->setFolderMarkedDeleted( $folder, $value );
      }
    }
    catch ( qcl_data_model_RecordNotFoundException $e){}
  }

  /**
   * Changes the position of a folder within its siblings
   * @param $datasource
   * @param $folderId
   * @param $position
   * @return string "OK"
   */
  public function method_changeFolderPosition( $datasource, $folderId, $position )
  {
    /*
     * check arguments
     */
    qcl_assert_valid_string( $datasource );
    qcl_assert_integer( $folderId );
    qcl_assert_integer( $position );

    /*
     * check access
     */
    $this->checkDatasourceAccess( $datasource );
    $this->requirePermission("folder.move");

    /*
     * change position
     */
    $folder = $this->getFolderModel( $datasource );
    $folder->load( $folderId );
    $folder->changePosition( $position );

    /*
     * dispatch update event
     */
    $this->broadcastClientMessage(
      "folder.node.reorder", array(
        'datasource'    => $datasource,
        'modelType'     => "folder",
        'nodeId'        => $folderId,
        'parentNodeId'  => $folder->getParentId(),
        'position'      => $position,
        'transactionId' => $folder->getTransactionId()
      )
    );

    return "OK";
  }

  /**
   * Purges folders that have been marked for deletion
   * @param string $datasource
   * @return string "OK"
   */
  public function method_purge( $datasource )
  {
    $this->requirePermission("trash.empty");
    $fldModel = $this->getFolderModel( $datasource );

    /*
     * clean up trash folder
     */
    $trashfolderId = $this->getTrashfolderId( $datasource );
    $fldModel->load( $trashfolderId );
    try
    {
      $fldModel->findChildren();
      while( $fldModel->loadNext() )
      {
        $this->setFolderMarkedDeleted( $fldModel, true );
      }
    }
    catch ( qcl_data_model_RecordNotFoundException $e){}

    /*
     * delete folders
     */
    $fldModel->findWhere(array( 'markedDeleted' => true ) );
    while( $fldModel->loadNext() )
    {
      $fldModel->delete();
    }
    return "OK";
  }

}
