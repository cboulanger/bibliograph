<?php
/* ************************************************************************

   Bibliograph: Collaborative Online Reference Management

   http://www.bibliograph.org

   Copyright:
     2007-2014 Christian Boulanger

   License:
     LGPL: http://www.gnu.org/licenses/lgpl.html
     EPL: http://www.eclipse.org/org/documents/epl-v10.php
     See the LICENSE file in the project's top-level directory for details.

   Authors:
     * Chritian Boulanger (cboulanger)

************************************************************************ */

qcl_import( "qcl_data_controller_TreeController" );

class bibliograph_service_Folder
  extends qcl_data_controller_TreeController
{

  /*
  ---------------------------------------------------------------------------
     MODEL ACL
  ---------------------------------------------------------------------------
  */

  /**
   * Access control list. Determines what role has access to what kind
   * of information.
   * @var array
   */
  private $modelAcl = array(

    /*
     * The folder model of the given datasource
     */
    array(
      //'schema'      => "bibliograph",
      'datasource'  => "*",
      'modelType'   => array("folder","reference"),

      'rules'         => array(
        array(
          'roles'       => "*",
          'access'      => array( QCL_ACCESS_READ ),
          'properties'  => array( "allow" => "*" )
        ),
        array(
          'roles'       => array( BIBLIOGRAPH_ROLE_USER ),
          'access'      => "*",
          'properties'  => array( "allow" => "*" )
        ),
        array(
          'roles'       => array( BIBLIOGRAPH_ROLE_ADMIN, BIBLIOGRAPH_ROLE_MANAGER ),
          'access'      => "*",
          'properties'  => array( "allow" => "*" )
        )
      )
    )
  );

  /*
  ---------------------------------------------------------------------------
     CLASS PROPERTIES
  ---------------------------------------------------------------------------
  */

  /**
   * Icons for the folder nodes, depending on type
   * @var array
   */
  protected $icon = array(
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
   * The main model type of this controller
   */
  protected $modelType = "folder";

  /**
   * Whether datasource access should be restricted according
   * to the current user. The implementation of this behavior is
   * done by the getAccessibleDatasources() and checkDatasourceAccess()
   * methods.
   *
   * @var bool
   */
  protected $controlDatasourceAccess = true;


  /*
  ---------------------------------------------------------------------------
     INITIALIZATION
  ---------------------------------------------------------------------------
  */

  /**
   * Constructor, adds model acl
   */
  public function __construct()
  {
    $this->addModelAcl( $this->modelAcl );
  }

  /**
   * Returns singleton instance of this class
   * @return bibliograph_service_Folder
   */
  public static function getInstance()
  {
    return qcl_getInstance( __CLASS__ );
  }

  /*
  ---------------------------------------------------------------------------
     INTERFACE ITREEVIRTUALCONTROLLER
  ---------------------------------------------------------------------------
  */

  /**
   * Return the data of a node of the tree.
   * @param string $datasource Datasource name
   * @param int $nodeId
   * @param int $parentId Optional id of parent folder
   * @param mixed|null $options Optional data
   * @return array
   */
  function getNodeData( $datasource, $nodeId, $parentId=null, $options=null )
  {
    /*
     * folder model
     */
    $folderModel = $this->getTreeNodeModel( $datasource, $nodeId );

    /*
     * prepare individual information
     */
    $folderType     = $folderModel->getType();
    $owner          = $folderModel->getOwner();
    $label          = $folderModel->getLabel();
    $query          = $folderModel->getQuery();
    $searchFolder   = $folderModel->getSearchfolder();
    $description    = $folderModel->getDescription();
    $childCount     = $folderModel->getChildCount();
    $referenceCount = $this->getReferenceCount( $folderModel, $datasource );
    $markedDeleted  = $folderModel->get("markedDeleted");
    $public         = $folderModel->getPublic();
    $opened         = $folderModel->getOpened();
    $parentId       = is_null( $parentId ) ? $folderModel->get("parentId") : $parentId;

    /*
     * access
     */
    static $activeUser = null;
    static $isAnonymous = null;
    if ( $activeUser === null )
    {
      $activeUser = $this->getActiveUser();
      $isAnonymous = $activeUser->hasRole( QCL_ROLE_ANONYMOUS );
    }

    if ( $isAnonymous )
    {
      /*
       * do not show unpublished folders to anonymous roles
       */
      if ( ! $public )  return null;
    }

    /*
     * reference count is zero if folder executes a query
     */
    if ( $query )
    {
      $referenceCount = "";
    }

    /*
     * icon & type
     */
    if ( ( $folderType=="search" or !$folderType)
           and $query and $searchFolder != false )
    {
      $icon = $this->icon["search"];
      $type = "searchfolder";
    }
    elseif ( $folderType == "trash" )
    {
      $icon = $this->icon["trash"];
      $type = "trash";
    }
    elseif ( $folderType == "favorites" )
    {
      $icon = $this->icon["favorites"];
      $type = "favorites";
    }
    elseif ( $markedDeleted )
    {
      $icon = $this->icon["markedDeleted"];
      $type = "deleted";
    }
    elseif ( $public )
    {
      $icon = $this->icon["public"];
      $type = "folder";
    }
    else
    {
      $icon = $this->icon["closed"];
      $type = "folder";
    }

    /*
     * construct node data
     */
    $data = array(
      'isBranch'        => true,
      'label'           => $label,
      'bOpened'         => $opened,
      'icon'            => $icon,
      'iconSelected'    => $icon,
      'bHideOpenClose'  => ($childCount == 0),
      'data'            => array (
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
                      ),
       'columnData'   => array( null, $referenceCount )
    );

    /*
     * return node data
     */
    return $data;
  }

  /*
  ---------------------------------------------------------------------------
     PUBLIC METHODS
  ---------------------------------------------------------------------------
  */

  /**
   * Returns the icon for a given folder type
   * @param string $type
   * @throws InvalidArgumentException
   * @return unknown_type
   */
  public function getIcon( $type )
  {
    qcl_assert_valid_string( $type, "Invalid type." );
    if ( isset( $this->icon[$type] ) )
    {
      return $this->icon[$type];
    }
    else
    {
      throw new InvalidArgumentException("Icon for type '$type' does not exist.");
    }
  }

  /**
   * Returns the folder model
   * @param $datasource
   * @return bibliograph_model_FolderModel
   */
  public function getFolderModel( $datasource )
  {
    static $model = null;
    if( $model === null )
    {
      $model = $this->getModel( $datasource, "folder" );
      if ( ! $model->__listenersAdded ) // @todo check for listener
      {
        $model->addListener("changeBubble", $this, "_on_changeBubble" );
        $model->addListener("change", $this, "_on_change" );
        $model->__listenersAdded = true;
      }
    }
    return $model;
  }

  /**
   * Returns the reference controller as provided by the controller
   * @param $datasource
   * @return bibliograph_model_ReferenceModel
   */
  public function getReferenceModel( $datasource )
  {
    qcl_import("bibliograph_service_Reference");
    return bibliograph_service_Reference::getInstance()->getReferenceModel( $datasource );
  }


  /**
   * Event listener for "changeBubble" event
   * Since the client data structure does not match the server data structure,
   * we have to send the whole node if only one property changes.
   * @param qcl_event_type_DataEvent|null $e
   * @return void
   */
  public function _on_changeBubble( $e )
  {
    $data           = ($e instanceof qcl_event_type_DataEvent) ? $e->getData():null; // TODO: should a non-data event triggler "changeBubble"?
    $target         = $e->getTarget();
    $datasource     = $target->datasourceModel()->namedId();
    $nodeId         = $target->id();
    $transactionId  = $target->getTransactionId();

    switch( $data['name'] )
    {
      case "id":
        return;

      case "position":
        // this event has to be manually dispatched
        break;

      /** @noinspection PhpMissingBreakStatementInspection */
      case "parentId":
        $parentId = $data['value'];
        if ( ! $parentId ) break;
        $target->load( $parentId );
        $target->getChildCount(true);
        $target->save();
        $nodeData = $this->getNodeData( $datasource, $parentId );
        unset( $nodeData['bOpened'] );
        $this->broadcastClientMessage(
          "folder.node.update", array(
            'datasource'    => $datasource,
            'modelType'     => "folder",
            'nodeData'      => $nodeData,
            'transactionId' => $transactionId
          )
        );

        $oldParentId = $data['old'];
        $target->load( $oldParentId );
        $target->getChildCount(true);
        $target->save();
        $nodeData = $this->getNodeData( $datasource, $oldParentId );
        unset( $nodeData['bOpened'] );
        $this->broadcastClientMessage(
          "folder.node.update", array(
            'datasource'    => $datasource,
            'modelType'     => "folder",
            'nodeData'      => $nodeData,
            'transactionId' => $transactionId
          )
        );
        $this->broadcastClientMessage(
          "folder.node.move", array(
            'datasource'    => $datasource,
            'modelType'     => "folder",
            'nodeId'        => $nodeId,
            'parentId'      => $parentId,
            'transactionId' => $transactionId
          )
        );
        // no break since the node itself also needs updating

      default:
        $nodeData = $this->getNodeData( $datasource, $nodeId );
        unset( $nodeData['bOpened'] );
        $this->broadcastClientMessage(
          "folder.node.update", array(
            'datasource'    => $datasource,
            'modelType'     => "folder",
            'nodeData'      => $nodeData,
            'transactionId' => $transactionId
          )
        );
    }

  }

  /*
  ---------------------------------------------------------------------------
     INTERNAL METHODS
  ---------------------------------------------------------------------------
  */

  public function _on_change( $e )
  {
    $data           = ($e instanceof qcl_event_type_DataEvent) ? $e->getData():null; // TODO: should data event triggler "change"?
    $target = $e->getTarget();
    $datasource = $target->datasourceModel()->namedId();
    $nodeId = $target->id();

    /*
     * update parent node
     */
    $parentId = $target->getParentId();
    try
    {
      $target->load( $parentId );
    }
    catch( qcl_data_model_RecordNotFoundException $e )
    {
      return;
    }

    $transactionId = $target->getTransactionId();

    $target->getChildCount(true);
    $target->save();
    $nodeData = $this->getNodeData( $datasource, $parentId );
    unset( $nodeData['bOpened'] );

    $this->broadcastClientMessage(
      "folder.node.update", array(
        'datasource'    => $datasource,
        'modelType'     => "folder",
        'nodeData'      => $nodeData,
        'transactionId' => $transactionId
      )
    );

    switch( $data['type'] )
    {
      case "add":
        $this->broadcastClientMessage(
          "folder.node.add", array(
            'datasource'    => $datasource,
            'modelType'     => "folder",
            'nodeData'      => $this->getNodeData( $datasource, $nodeId ),
            'transactionId' => $target->getTransactionId()
          )
        );
        break;

      case "remove":
        $this->broadcastClientMessage(
          "folder.node.delete", array(
            'datasource'    => $datasource,
            'modelType'     => "folder",
            'nodeId'        => $nodeId,
            'transactionId' => $target->getTransactionId()
          )
        );
    }
  }

  /**
   * Returns the number of references linked to the folder
   * @param qcl_data_model_db_ActiveRecord $folderModel
   * @param string $datasource
   * @param bool $update If true, calculate the reference count again. Defaults to false
   * @return int
   */
  protected function getReferenceCount( $folderModel, $datasource, $update=false )
  {
    $referenceCount = $folderModel->get("referenceCount");
    if ( $update or $referenceCount === null )
    {
      $referenceModel = $this->getReferenceModel( $datasource );
      $referenceCount = count( $referenceModel->linkedModelIds( $folderModel ) );
      $folderModel->set( "referenceCount", $referenceCount );
      $folderModel->save();
    }
    return $referenceCount;
  }


  /*
  ---------------------------------------------------------------------------
     SERVICE METHODS
  ---------------------------------------------------------------------------
  */


  /**
   * Edit folder data
   * @param $datasource
   * @param $folderId
   * @return qcl_ui_dialog_Form
   */
  public function method_edit( $datasource, $folderId )
  {
    $this->requirePermission("folder.edit");
    $model = $this->getFolderModel( $datasource );
    $model->load( $folderId );
    $formData = $this->createFormData( $model );
    $label = $model->getLabel();
    $message = "<h3>$label</h3>";
    qcl_import("qcl_ui_dialog_Form");
    return new qcl_ui_dialog_Form(
      $message, $formData, true,
      $this->serviceName(), "saveFormData",
      array( $datasource, $folderId )
    );
  }

  /**
   * Saves the result of the edit() method
   * @param $data
   * @param $datasource
   * @param $folderId
   * @return string "OK"
   */
  public function method_saveFormData( $data, $datasource, $folderId )
  {
    if ( $data === null ) return "ABORTED";

    $this->requirePermission("folder.edit");

    $model = $this->getFolderModel( $datasource );
    $model->load( $folderId );
    $data = $this->parseFormData( $model, $data );
    $model->set( $data );
    $model->save();

    return "OK";
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
    /*
     * check arguments
     */
    qcl_assert_valid_string( $datasource );
    qcl_assert_integer( $folderId );

    /*
     * check access
     */
    $this->checkDatasourceAccess( $datasource );
    $this->requirePermission("folder.remove");

    /*
     * create dialog
     */
    $model = $this->getFolderModel( $datasource );
    $model->load( $folderId );
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
    $model = $this->getFolderModel( $datasource );
    $model->load( $folderId );
    $model->set("parentId", $this->getTrashfolderId( $datasource ) );
    $model->save();

    /*
     * mark deleted
     */
    $this->setFolderMarkedDeleted( $model, true);

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
      throw new JsonRpcException("Folder cannot be moved on itself.");
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
      $id = $model->get("parentId");
      if( $id == $folderId )
      {
        throw new JsonRpcException("Parent node cannot be moved on a child node");
      }
    }
    while ( $id !== 0 );

    /*
     * change folder parent
     */
    $model->load( $folderId );
    $model->set("parentId", $parentId );
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
   * @param $folderModel
   * @param $value
   * @return void
   */
  public function setFolderMarkedDeleted( bibliograph_model_FolderModel $folderModel, $value )
  {
    qcl_assert_boolean( $value );

    /*
     * mark folder (un)deleted
     */
    //$this->debug("Marking $folderModel deleted " . boolString($value),__CLASS__,__LINE__);
    $folderModel->set("markedDeleted", $value );

    /*
     * handle contained referenced
     */
    $referenceModel = $folderModel->getRelationBehavior()->getTargetModel("Folder_Reference");
    try
    {
      $referenceModel->findLinked( $folderModel );
      while ( $referenceModel->loadNext() )
      {
        if ( $value )
        {
          $linkCount = $folderModel->countLinksWithModel( $referenceModel );
          //$this->debug("$referenceModel has $linkCount links with folders... ",__CLASS__,__LINE__);
          if ( $linkCount > 1 )
          {
            /*
             * unlink reference and folder
             */
            //$this->debug("Unlinking $referenceModel from $folderModel",__CLASS__,__LINE__);
            $referenceModel->unlinkModel( $folderModel );

            /*
             * update reference count
             * @todo use event?
             */
            $referenceCount = count( $referenceModel->linkedModelIds( $folderModel ) );
            $folderModel->set( "referenceCount", $referenceCount );
            $folderModel->save();
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
      $query = $folderModel->findChildren();
      //$this->debug("$folderModel has {$query->rowCount} children",__CLASS__,__LINE__);
      if ( $query->getRowCount() ) while( $folderModel->loadNext( $query ) )
      {
        $this->setFolderMarkedDeleted( $folderModel, $value );
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
    $folderModel = $this->getFolderModel( $datasource );
    $folderModel->load( $folderId );
    $folderModel->changePosition( $position );

    /*
     * dispatch update event
     */
    $this->broadcastClientMessage(
      "folder.node.reorder", array(
        'datasource'    => $datasource,
        'modelType'     => "folder",
        'nodeId'        => $folderId,
        'parentNodeId'  => $folderModel->getParentId(),
        'position'      => $position,
        'transactionId' => $folderModel->getTransactionId()
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
?>