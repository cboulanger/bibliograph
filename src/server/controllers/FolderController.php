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
      $folder = static::controlledModel($datasource)::findOne($folder);
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
    $this->checkDatasourceAccess( $datasource );
    $query = static::controlledModel($datasource)::find()->select("id");
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
   * NOT PORTED TO YII2 YET.
   * 
   * Returns the node data of the children of a given array of
   * parent node ids. If the "recurse" parameter is true,
   * also return the data of the whole branch. The number of
   * nodes returned can be limited by the "max" argument.
   *
   * Returns an associative array with at least the keys "nodeData" and
   * "queue". The "nodeData" value is an array of node data, each of which
   * contains information on the parent id in the data.parentId property.
   * The "queue" value is an array of ids that could not be retrieved
   * because of the "max" limitation.
   *
   * If you supply a 'storeId' parameter, the requesting tree will be
   * synchronized with all other trees that are connected to this store.
   *
   * @param string $datasource The name of the datasource
   * @param int|array $ids A node id or array of node ids
   * @param int $max The maximum number of queues to retrieve. If null, no limit
   * @param bool $recurse Whether recurse into the tree branch
   * @param string $storeId The id of the connected datastore
   * @param string|null $options Optional data, for example, when nodes
   *   should be filtered by a certain criteria
   * @throws LogicException
   * @return array
   */
  function method_getChildNodeData(
    $datasource, $ids, $max=null, $recurse=false, $storeId=null, $options=null )
  {    
    not_implemented();
    $this->checkDatasourceAccess( $datasource );
    $counter = 0;

    // create node array with root node
    $nodeArr = array();

    // check array of nodes of which the children should be retrieved
    if ( ! is_array( $ids ) )
    {
      if ( ! is_numeric( $ids ) )
      {
        throw new LogicException("Invalid argument.");
      }
    }
    $queue = (array) $ids;
    $queueLater = array();

    /*
     * retrieve the whole tree
     */
    while( count( $queue ) )
    {

      /*
       * get child nodes
       */
      $parentId = (int) array_shift( $queue );
      $childIds = (array) $this->getChildIds( $datasource, $parentId, "position" );
      $queueLater = array();
      while( count ($childIds ) )
      {
        $childId = array_shift( $childIds );

        /*
         * get child data
         */
        $childData = $this->getNodeData( $datasource, $childId, $parentId );

        /*
         * ingnore inaccessible nodes
         */
        if ( $childData === null )
        {
          //$this->debug("Node #$childId is not accessible");
          continue;
        }

        qcl_assert_array( $childData ); // todo assert keys

        /*
         * if the child has children itself, load those
         */
        if ( $recurse and $childData['data']['childCount'] )
        {
          if ( $max and $counter > $max )
          {
            $queueLater[]= (int) $childId;
          }
          else
          {
            array_push( $queue, (int) $childId );
          }
        }

        /*
         * add child data to result
         */
        $nodeArr[] = $childData;
        $counter++;
      }

      // if a node limit is set, check for maximum number of nodes per request
      if ( $max and $counter > $max ) break;
    }

   /*
    * return the node data
    */
    $queue = array_merge( $queue, $queueLater );
    $queueCount = count($queue);
    //$nodeCount  = count( $nodeArr );
    //$this->debug("Returning $nodeCount nodes, remaining nodes $queueCount");

    return array(
      'nodeData'    => $nodeArr,
      'queue'       => $queue,
      'statusText'  => $queueCount ? "Loading..." : ""
    );
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
