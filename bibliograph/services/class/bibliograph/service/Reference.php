<?php
/* ************************************************************************

   Bibliograph: Collaborative Online Reference Management

   http://www.bibliograph.org

   Copyright:
     2007-2010 Christian Boulanger

   License:
     LGPL: http://www.gnu.org/licenses/lgpl.html
     EPL: http://www.eclipse.org/org/documents/epl-v10.php
     See the LICENSE file in the project's top-level directory for details.

   Authors:
     * Chritian Boulanger (cboulanger)

************************************************************************ */

qcl_import( "qcl_data_controller_TableController" );
qcl_import("qcl_ui_dialog_Confirm");
qcl_import("bibliograph_service_Folder");

/**
 * Controller that supplies data for the references
 */
class bibliograph_service_Reference
  extends qcl_data_controller_TableController
{

  /**
   * Access control list. Determines what role has access to what kind
   * of information.
   * @var array
   */
  private $modelAcl = array(

    /*
     * The reference model of the given datasource
     */
    array(
      'datasource'  => "*",
      'modelType'   => "reference",

      'rules'         => array(
        array(
          'roles'       => "*",
          'access'      => array( QCL_ACCESS_READ ),
          'properties'  => array( "allow" =>  "*" )
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
    ),
    /*
     * The folder model of the given datasource
     */
    array(
      'datasource'  => "*",
      'modelType'   => "folder",

      'rules'         => array(
        array(
          'roles'       => "*",
          'access'      => array( QCL_ACCESS_READ ),
          'properties'  => array( "allow" =>  "*" )
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
    "favorites"       => "icon/16/actions/help-about.png"
  );

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
  function __construct()
  {
    $this->addModelAcl( $this->modelAcl );
  }

  /**
   * Returns singleton instance of this class
   * @return bibliograph_service_Reference
   */
  public static function getInstance()
  {
    return qcl_getInstance( __CLASS__ );
  }

  /*
  ---------------------------------------------------------------------------
     CONTROLLER-MODEL API
  ---------------------------------------------------------------------------
  */

  /**
   * Returns the default model type for which this controller is providing
   * data.
   * @return string
   */
  protected function getModelType()
  {
    return "reference";
  }

  /**
   * Returns the reference model
   * @param string $datasource
   * @param null $modelType
   * @return bibliograph_model_ReferenceModel
   */
  public function getControlledModel( $datasource, $modelType=null )
  {
    return $this->getModel( $datasource, either( $modelType, "reference") );
  }

  /*
  ---------------------------------------------------------------------------
     TABLE INTERFACE API
  ---------------------------------------------------------------------------
  */

  /**
   * Returns the layout of the columns of the table displaying
   * the records
   *
   * @param $datasource
   * @param null $modelType
   * @return unknown_type
   */
  public function method_getTableLayout( $datasource, $modelType=null )
  {
    return array(
      'columnLayout' => array(
        'id' => array(
          'header'  => "ID",
          'width'   => 50,
          'visible' => false
        ),
//        'markedDeleted'	=> array(	
//        	'header' 		=> " ",
//        	'width'	 		=> 16
//        ),
        'author' => array(
          'header'  => _("Author"),
          'width'   => "1*"
        ),
        'year' => array(
          'header'  => _("Year"),
          'width'   => 50
        ),
        'title' => array(
          'header'  => _("Title"),
          'width'   => "3*"
        )
      ),
      'queryData' => array(
        'link'    => array( 'relation' => "Folder_Reference" ),
        'orderBy' => "author,year,title",
      ),
      'addItems' => $this->getAddItemListData( $datasource, $modelType )
    );
  }

  /**
   * Overridden to create qcl-compliant 'where' struncture from a
   * pseudo- CQL query string.
   *
   * @see http://www.loc.gov/standards/sru/resources/cql-context-set-v1-2.html
   *
   * @param stdClass $query
   *    The query data object from the json-rpc request
   * @param qcl_data_db_Query $qclQuery
   *    The query object used by the query behavior
   * @param qcl_data_model_AbstractActiveRecord $model
   *    The model on which the query should be performed
   * @throws JsonRpcException
   * @return qcl_data_db_Query
   */
  public function addQueryConditions(
    stdClass $query,
    qcl_data_db_Query $qclQuery,
    qcl_data_model_AbstractActiveRecord $model )
  {
    if ( isset( $query->link ) )
    {
      $qclQuery->link = object2array( $query->link );
      return $qclQuery;
    }
    elseif ( isset ( $query->cql ) )
    {
      qcl_import( "bibliograph_schema_CQL" );
      $cql =  bibliograph_schema_CQL::getInstance();
      $q = $cql->addQueryConditions( $query, $qclQuery, $model );
      $q->where['markedDeleted'] = false; // todo: why is this?
      return $q;
    }
    else
    {
      throw new JsonRpcException( "No recognized query format in request." );
    }
  }

  
  /**
   * Returns the folder model, as provided by the folder controller
   * @param $datasource
   * @return bibliograph_model_FolderModel
   */
  public function getFolderModel( $datasource )
  {
    qcl_import("bibliograph_service_Folder");
    return bibliograph_service_Folder::getInstance()->getFolderModel($datasource);
  }
  
  public function getReferenceModel( $datasource )
  {
    return $this->getControlledModel( $datasource );
  }

  /**
   * Returns an array of ListItem data
   * @param $datasource
   * @param null $modelType
   * @return array
   */
  public function getAddItemListData( $datasource, $modelType=null )
  {
    $schemaModel = $this->getControlledModel( $datasource, $modelType )->getSchemaModel();
    $reftypes  = $schemaModel->types();

    $options = array();
    foreach ( $reftypes as $reftype )
    {
      $options[] = array(
        'value' => $reftype,
        'icon'  => null, //"icon/16/actions/document-new.png",
        'label' => $this->tr( $schemaModel->getTypeLabel( $reftype ) )
      );
    }
    return $options;
  }


  /*
  ---------------------------------------------------------------------------
     DATA ITEM API
  ---------------------------------------------------------------------------
  */

  /**
   * Returns the form layout for the given reference type and
   * datasource
   *
   * @param $datasource
   * @param $reftype
   * @internal param \Reference $refType type
   * @return array
   */
  function method_getFormLayout( $datasource, $reftype )
  {
    /*
     * check access
     */
    $this->checkDatasourceAccess( $datasource );

    $model  = $this->getControlledModel( $datasource );
    $schemaModel = $model->getSchemaModel();

    /*
     * get fields to display in the form
     */
    $fields = array_merge(
      $schemaModel->getDefaultFormFields(),
      $schemaModel->getTypeFields( $reftype )
    );

    /*
     * remove excluded fields
     * @todo improve this
     */
    $key = "datasource.$datasource.fields.exclude";
    $configModel = $this->getApplication()->getConfigModel();
    try
    {
      $excludeFields = $configModel->getKey($key);
      if( count( $excludeFields ) )
      {
        $fields = array_diff( $fields, $excludeFields );
      }
    }
    catch( qcl_config_Exception $e ){}


    /*
     * Create form
     */
    $formData = array();
    foreach ( $fields as $field )
    {
      $formData[$field] = $schemaModel->getFormData( $field );
      if ( ! $formData[$field] )
      {
        $formData[$field] = array(
          "type"  => "textfield",
        );
      }

      /*
       * replace placeholders
       */
      elseif ( isset( $formData[$field]['bindStore']['params'] ) )
      {
        foreach ( $formData[$field]['bindStore']['params'] as $i => $param )
        {
          switch ( $param  )
          {
            case '$datasource':
              $formData[$field]['bindStore']['params'][$i] = $datasource;
              break;
          }
        }
      }

      /*
       * setup autocomplete data
       */
      if ( isset( $formData[$field]['autocomplete'] ) )
      {
        $formData[$field]['autocomplete']['service'] = $this->serviceName();
        $formData[$field]['autocomplete']['method'] = "getAutoCompleteData";
        $formData[$field]['autocomplete']['params'] = array( $datasource, $field );
      }

      /*
       * Add label
       */
      if ( ! isset( $formData[$field]['label'] ) )
      {
        $formData[$field]['label'] = $schemaModel->getFieldLabel( $field, $reftype );
      }
    }
    return $formData;
  }

  /**
   * Returns data for the reference type select box
   * @param $datasource
   * @return array
   */
  public function method_getReferenceTypeListData( $datasource )
  {
    $this->checkDatasourceAccess( $datasource );

    $model  = $this->getControlledModel( $datasource );
    $schemaModel = $model->getSchemaModel();
    $result = array();
    foreach( $schemaModel->types() as $type )
    {
      $result[] = array(
        'label' => $schemaModel->getTypeLabel( $type ),
        'value' => $type,
        'icon'  => null
      );
    }
    return $result;
  }

  /**
   * Returns data for the store that populates reference type lists
   * @param $datasource
   * @return unknown_type
   */
  function method_getReferenceTypeData( $datasource )
  {
    $this->checkDatasourceAccess( $datasource );
    return $this->getAddItemListData( $datasource );
  }

  /**
   * Returns the requested or all accessible properties of a reference to the client
   * @param string $datasource
   * @param $arg2
   * @param null $arg3
   * @param null $arg4
   * @throws InvalidArgumentException
   * @return array
   * todo: this method is called with different signatures!
   */
  function method_getData( $datasource, $arg2, $arg3= null, $arg4=null)
  {
    if( is_numeric( $arg2) )
    {
      //$type   = "reference";
      $id     = $arg2;
      $fields = $arg3;
    }
    else 
    {
      //$type   = $arg2;
      $id     = $arg3;
      $fields = null;
    }
    $this->checkDatasourceAccess( $datasource );

    if ( ! $datasource or ! is_numeric( $id ) )
    {
      throw new InvalidArgumentException("Invalid arguments.");
    }

    /*
     * load model record and get reference type
     */
    $model = $this->getControlledModel( $datasource );
    $model->load( $id );

    $reftype     = $model->getReftype();
    $schemaModel = $model->getSchemaModel();

    /*
     * determine the fields to return the values for
     */
    if( is_array( $fields ) )
    {
      $this->checkAccess( QCL_ACCESS_READ, $datasource, $this->getModelType(), $fields );
    }
    else
    {
      /*
       * merge fields
       */
      $fields = array_merge(
        $schemaModel->getDefaultFieldsBefore(),
        $schemaModel->getTypeFields( $reftype ),
        $schemaModel->getDefaultFieldsAfter()
      );

      /*
       * exclude fields
       */
      $configModel = $this->getApplication()->getConfigModel();
      $key = "datasource.$datasource.fields.exclude"; // @todo create method?
      try
      {
        $excludeFields = $configModel->getKey($key);
        if( count( $excludeFields ) )
        {
          $fields = array_diff( $fields, $excludeFields );
        }
      }
      catch( qcl_config_Exception $e ){}
    }

    /*
     * prepare record data for the form
     */
    $reference = array(
      'datasource'  => $datasource,
      'referenceId' => $id, // todo: replace by "id"
      'titleLabel'  => $this->getTitleLabel( $model )
    );

    foreach ( $fields as $field )
    {
      try
      {
        $fieldData = $schemaModel->getFieldData( $field );
      }
      catch( InvalidArgumentException $e )
      {
        $this->warn("No field data for field '$field'");
        continue;
      }

      $value = $model->get( $field );

      /*
       * replace field separator with form separator if both exist
       */
      $dataSeparator = isset( $fieldData['separator'] )
        ? $fieldData['separator']
        : null;

      $formSeparator = isset( $fieldData['formData']['autocomplete']['separator'] )
        ? $fieldData['formData']['autocomplete']['separator']
        : null;

      if ( $dataSeparator and $formSeparator and $dataSeparator != $formSeparator )
      {
        $values = explode( $dataSeparator, $value );
        foreach( $values as $i => $v )
        {
          $values[$i] = trim( $v);
        }
        $value = implode( $formSeparator, $values );
      }

      /*
       * store value
       */
      $reference[$field] = $value;
    }

    return $reference;
  }

  protected function getTitleLabel( $model )
  {
    return $this->getModelType();
    /*
        "<b>" .
        ($model.author || data.editor || "No author" ).replace( /\n/, "/" ) +
        " (" + ( data.year || "No year" ) + "): " +
        ( data.title || "No title" ) +
        "</b>"
    );
     */
  }

  /**
   * Returns data for the qcl.data.controller.AutoComplete
   * @param $datasource
   * @param $field
   * @param $input
   * @return array
   */
  public function method_getAutoCompleteData( $datasource, $field, $input )
  {

    /*
     * check access
     */
    $this->checkDatasourceAccess( $datasource );
    $this->checkAccess( QCL_ACCESS_READ, $datasource, $this->getModelType(), array( $field ) );

    /*
     * get autocomplete data
     */
    $model = $this->getControlledModel( $datasource );
    $fieldData = $model->getSchemaModel()->getFieldData( $field );

    $separator = $fieldData['separator'];

    //$this->debug(array( $datasource, $field, $input, $separator ));

    if ( $separator )
    {
      $suggestionValues = $model->getQueryBehavior()->fetchValues($field,array(
        $field => array( "LIKE", "%$input%")
      ));

      $suggestions = array();
      foreach( $suggestionValues as $value )
      {
        foreach( explode( $separator, $value ) as $suggestion )
        {
          $suggestion = trim( $suggestion );
          if ( strtolower( $input ) == strtolower( substr( $suggestion, 0, strlen( $input) ) ) )
          {
            $suggestions[] = $suggestion;
          }
        }
      }
      $suggestionValues = array_unique( $suggestions );
      sort( $suggestionValues );
    }
    else
    {
      $suggestionValues = $model->getQueryBehavior()->fetchValues($field,array(
        $field => array( "LIKE", "$input%")
      ));
    }

    return array(
      'input'       => $input,
      'suggestions' => $suggestionValues
    );
  }

  /**
   * Saves a value in the model
   * @param $datasource
   * @param $referenceId
   * @param $data
   * @throws JsonRpcException
   * @return unknown_type
   */
  public function method_saveData( $datasource, $referenceId, $data )
  {
    $this->checkDatasourceAccess( $datasource );

    if ( ! is_object( $data ) )
    {
      throw new JsonRpcException( "Invalid data object");
    }
    
    $model = $this->getControlledModel( $datasource );    

    /*
     * save user-supplied data
     */
    foreach( object2array($data) as $property => $value )
    {
      // replace form separator with field separator
      $fieldData = $model->getSchemaModel()->getFieldData( $property );
      $fieldSeparator = isset( $fieldData['separator'] )
        ? $fieldData['separator']
        : null;
      $formSeparator = isset( $fieldData['formData']['autocomplete']['separator'] )
        ? $fieldData['formData']['autocomplete']['separator']
        : null; 
      if( $fieldSeparator && $formSeparator )
      {
        $value = str_replace( $formSeparator, $fieldSeparator, $value );
      }
      
      // set value
      $this->method_setValue( $datasource, $this->getModelType(), $referenceId, $property, $value );
    }

    /*
     * add metadata
     */
    $model = $this->getControlledModel( $datasource );
    if( $model->hasProperty('modifiedBy') )
    {
      $model->load( $referenceId );
      $model->set( array(
        'modifiedBy'  => $this->getActiveUser()->namedId()
      ) );
      $model->save();
    }

    $oldCitekey = $model->getCitekey();
    $newCitekey = $model->computeCiteKey();
    if( ! $oldCitekey and $model->getAuthor() and $model->getYear() and $model->getTitle() )
    {
      $data = array(
        'datasource' => $datasource,
        'modelType'  => "reference",
        'modelId'    => $referenceId,
        'data'       => array( "citekey" => $newCitekey )
      );
      $this->broadcastClientMessage("bibliograph/fieldeditor/update", $data );
    }
    return "OK";
  }

  /**
   * Returns a HTML table with the reference data
   * @param $datasource
   * @param $id
   * @return unknown_type
   */
  public function method_getHtml( $datasource, $id )
  {
    /*
     * check arguments
     */
    qcl_assert_valid_string( $datasource );
    qcl_assert_integer( $id );

    /*
     * check access
     */
    $this->checkDatasourceAccess( $datasource );

    /*
     * load model record and get reference type
     */
    $model = $this->getControlledModel( $datasource );
    $model->load( $id );
    $reftype = $model->getReftype();

    $fields = array_merge(
      array("reftype"),
      $model->getSchemaModel()->getTypeFields( $reftype ),
      array("keywords","abstract")
    );

    /*
     * create html table
     */
    $html = "<table>";
    //$reference = array();
    $schemaModel =  $model->getSchemaModel();
    foreach ( $fields as $field )
    {
      $value = $model->get( $field );
      if ( ! $value or ! $schemaModel->isPublicField( $field ) ) continue;
      $label = $model->getSchemaModel()->getFieldLabel( $field, $reftype );

      /*
       * special fields
       */
      switch( $field )
      {
        case "reftype":
          $value = $schemaModel->getTypeLabel( $value );
          break;
        case "url":
          //@todo multiple urls
          $value = "<a href='$value' target='_blank'>$value</a>";
          break;
      }

      $html .= "<tr><td><b>$label</b></td><td>$value</td></tr>";
    }
    $html .= "</table>";

    return array(
      'html' => $html
    );
  }

  /**
   * Returns data for a ComboBox widget.
   * @param $datasource
   * @param $field
   * @return unknown_type
   */
  public function method_getUniqueValueListData( $datasource, $field )
  {
    /*
     * check arguments
     */
    qcl_assert_valid_string( $datasource );
    qcl_assert_valid_string( $field );

    /*
     * check access
     */
    $this->checkDatasourceAccess( $datasource );
    $this->checkAccess( QCL_ACCESS_READ, $datasource, $this->getModelType(), array($field) );

    /*
     * get data
     */
    $model  = $this->getControlledModel( $datasource );
    $values = $model->getQueryBehavior()->fetchValues( $field, null, true );
    $result = array();
    foreach( $values as $value )
    {
      $value = trim($value);
      if( $value )
      {
        $result[] = array(
          'label' => $value,
          'value' => $value,
          'icon'  => null
        );
      }
    }
    return $result;
  }


  /**
   * Creates a new reference
   *
   * @param $datasource
   * @param $folderId
   * @param $reftype
   * @return string
   * @todo update of reference count should be done in the folderModel!
   */
  public function method_create( $datasource, $folderId, $reftype )
  {
    /*
     * check access
     */
    $this->checkDatasourceAccess( $datasource );
    $this->requirePermission( "reference.add" ); // todo: rename permission to make it more portable

    /*
     * create reference
     */
    $referenceModel = $this->getControlledModel( $datasource );
    $referenceModel->create( array(
      'reftype'     => $reftype,
      'createdBy'   => $this->getActiveUser()->namedId()
    ) );

    /*
     * link to folder
     */
    $folderModel    = $this->getFolderModel( $datasource );
    $folderModel->load( $folderId );
    $folderModel->linkModel( $referenceModel );

    /*
     * update reference count
     */
    $referenceCount = count( $referenceModel->linkedModelIds( $folderModel ) );
    $folderModel->set( "referenceCount", $referenceCount );
    $folderModel->save();

    /*
     * reload references and select the new reference
     */
    $this->dispatchClientMessage("folder.reload", array(
      'datasource'  => $datasource,
      'folderId'    => $folderId
    ) );

    $this->dispatchClientMessage("bibliograph.setModel", array(
      'datasource'    => $datasource,
      'modelType'     => $this->getModelType(),
      'modelId'       => $referenceModel->id()
    ) );


    return "OK";
  }


  /**
   * Remove references. If a folder id is given, remove from that folder
   * @param string|bool $first
   *    If boolean, the response to the confirmation dialog. Otherwise, the datasource name
   * @param string|int
   *    Optional. If string, the shelve id. Otherwise, the id of the folder from which to remove
   *    the reference
   * @param int $third
   *    Optional. Dummy parameter required because of generic signature of the (move|remove|copy)Reference
   *    methods.
   * @param array $ids
   *    If given, the ids of the references to remove
   * @return \qcl_ui_dialog_Confirm|string "OK"
   */
  public function method_removeReferences( $first, $second=null, $third=null, $ids=null )
  {
		
  	/*
  	 * check arguments and handle response to the confirmation dialog
  	 */

    // removal cancelled
  	if( $first === false )
  	{
  		return "CANCELLED";
  	}

    // removal confirmed
    elseif ( $first === true and is_string( $second) )
    {
      $confirmRemove = true;
      list( $datasource, $folderId, $ids ) = $this->unshelve( $second );
    }

    // API signature
  	elseif ( is_string($first) and is_array( $ids) )
    {
      $confirmRemove = false;
      $datasource = $first;
      $folderId   = $second;
    }

    // wrong parameters
    else
    {
      throw new JsonRpcException("Invalid arguments for bibliograph.reference.removeReferences");
    }

    // check access
    $this->checkDatasourceAccess( $datasource );
    $this->requirePermission("reference.remove");
    
    // go...
    $referenceModel = $this->getControlledModel( $datasource );
    $folderModel    = $this->getFolderModel( $datasource );

    //$this->debug( array($datasource, $folderId, $ids) );

    // use the first id
    $id = intval( $ids[0] );

    /*
     * load record and count the number of links to folders
     */
    $referenceModel->load( $id );
    $containedFolderIds = $folderModel->linkedModelIds( $referenceModel );
    $folderCount = count( $containedFolderIds );

    /*
     * if we have no folder id and more than one folders contain the reference,
     * we need to ask the user first
     */
    if ( ! $folderId )
    {
      if ( $folderCount > 1 and ! $confirmRemove )
      {
        return new qcl_ui_dialog_Confirm(
          $this->tr(
            "The selected record '%s' is contained in %s folders. Move to the trash anyways?",
            ( $referenceModel->getTitle() . " (" . $referenceModel->getYear() . ")" ),
            $folderCount
          ),
          null,
          $this->serviceName(),
          $this->serviceMethod(),
          array( $this->shelve( $datasource, $folderId, $ids ) )
        );
      }

      // confirmed
      else
      {
        $referenceModel->unlinkAll( $folderModel );
        $folderCount = 0;
      }
    }

    /*
     * unlink from folder if id is given.
     */
    else
    {
      $folderModel->load( $folderId );
      try
      {
        $referenceModel->unlinkModel( $folderModel );
      }
      catch( qcl_data_model_Exception $e)
      {
        $this->warn($e->getMessage());
      }
    }

    $foldersToUpdate = $containedFolderIds;

    /*
     * move to trash only if it was contained in one or less folders
     */
    if ( $folderCount < 2 )
    {
      // link with trash folder
      $trashFolderId  = bibliograph_service_Folder::getInstance()->getTrashFolderId( $datasource );
      $folderModel->load( $trashFolderId );
      $referenceModel->linkModel( $folderModel );
      $foldersToUpdate[] = $trashFolderId;

      // mark as deleted
      $referenceModel->set("markedDeleted", true);
      $referenceModel->save();
    }

    /*
     * update reference count in source and target folders
     */
    foreach( $foldersToUpdate as $fid )
    {
      $folderModel->load( $fid );
      $referenceCount = count( $referenceModel->linkedModelIds( $folderModel ) );
      $folderModel->set( "referenceCount", $referenceCount );
      $folderModel->save();
    }

    /*
     * display change on connected clients
     */
    foreach( $containedFolderIds as $fid )
    {
	    $this->broadcastClientMessage("reference.removeFromFolder", array(
	      'datasource' => $datasource,
	      'folderId'   => $fid,
	      'ids'        => array($id)
	    ) );
    }

    /*
     * if there are references left, repeat
     */
    if ( count($ids) > 1 )
    {
      array_shift($ids);
      return $this->method_removeReferences( $datasource, $folderId, null, $ids );
    }
    return "OK";
  }

  /**
   * Move reference from one folder to another folder
   *
   * @param $datasource
   * @param $folderId
   * @param $targetFolderId
   * @param $ids
   * @return "OK"
   */
//  public function method_moveReferences( $datasource, $folderId, $targetFolderId, $ids )
//  {
//    /*
//     * check arguments
//     */
//    qcl_assert_valid_string( $datasource );
//    qcl_assert_integer( $folderId );
//    qcl_assert_integer( $targetFolderId );
//    qcl_assert_array( $ids );
//
//    if( $folderId === 0 )
//    {
//      qcl_import("qcl_ui_dialog_Confirm");
//      return new qcl_ui_dialog_Confirm(
//        _("This will remove")
//
//      );
//    }
//    else
//    {
//      $this->method_doMoveReferences( true, $datasource, $folderId, $targetFolderId, $ids );
//    }
//  }


  /**
   * Move reference from one folder to another folder
   *
   * @param $datasource
   * @param $folderId
   * @param $targetFolderId
   * @param $ids
   * @return string "OK"
   */
  public function method_moveReferences( $datasource, $folderId, $targetFolderId, $ids )
  {

    /*
     * check arguments
     */
    qcl_assert_valid_string( $datasource );
    qcl_assert_integer( $folderId );
    qcl_assert_integer( $targetFolderId );
    qcl_assert_array( $ids );

    /*
     * check access
     */
    $this->checkDatasourceAccess( $datasource );
    $this->requirePermission("reference.move");

    /*
     * move reference ...
     */
    $referenceModel = $this->getControlledModel( $datasource );
    $folderModel    = $this->getFolderModel( $datasource );

    foreach( $ids as $id )
    {
      /*
       * load the reference record
       */
      $referenceModel->load( intval($id) );

      /*
       * if the folder id is zero, this means the reference is the
       * result of a search -> we remove it from all folders,
       * or is not linked to any folder.
       */
      if ( $folderId === 0 )
      {
        $referenceModel->unlinkAll( $folderModel );
      }

      /*
       * else, unlink from folder
       */
      else
      {
        $folderModel->load( $folderId );
        $referenceModel->unlinkModel( $folderModel );
      }

      /*
       * link with target folder
       */
      $folderModel->load( $targetFolderId );
      try
      {
        $referenceModel->linkModel( $folderModel );
      }
      catch( qcl_data_model_RecordExistsException $e)
      {
        /*
         * remove id
         */
        $ids = array_diff( $ids, array($id) );
      }
    }

    /*
     * update reference count in source and target folders
     */
    foreach( array($folderId, $targetFolderId) as $id )
    {
      if ( $id )
      {
        $folderModel->load( $id );
        $referenceCount = count( $referenceModel->linkedModelIds( $folderModel ) );
        $folderModel->set( "referenceCount", $referenceCount );
        $folderModel->save();
      }
    }

    /*
     * display change on connected clients
     */
    if( count( $ids ) )
    {
      $this->broadcastClientMessage("reference.removeFromFolder", array(
        'datasource' => $datasource,
        'folderId'   => $folderId,
        'ids'        => $ids
      ) );
    }


    return "OK";
  }

  /**
   * Copies a reference to a folder
   *
   * @param $datasource
   * @param $folderId
   * @param $targetFolderId
   * @param $ids
   * @return string "OK"
   */
  public function method_copyReferences( $datasource, $folderId, $targetFolderId, $ids )
  {

    /*
     * check arguments
     */
    qcl_assert_valid_string( $datasource );
    qcl_assert_integer( $folderId );
    qcl_assert_integer( $targetFolderId );
    qcl_assert_array( $ids );

    /*
     * check access
     */
    $this->checkDatasourceAccess( $datasource );
    $this->requirePermission("reference.move");

    /*
     * copy reference
     */
    $referenceModel = $this->getControlledModel( $datasource );
    $folderModel    = $this->getFolderModel( $datasource );
    $folderModel->load( $targetFolderId );

    foreach( $ids as $id )
    {
      /*
       * link with target folder
       */
      $referenceModel->load( intval($id) );
      try
      {
        $referenceModel->linkModel( $folderModel );
      }
      catch( qcl_data_model_RecordExistsException $e)
      {
        return "ABORTED";
      }
    }

    /*
     * update reference count
     */
    $referenceCount = count( $referenceModel->linkedModelIds( $folderModel ) );
    $folderModel->set( "referenceCount", $referenceCount );
    $folderModel->save();

    return "OK";
  }

  /**
   * Returns information on the record as a HTML table
   * @param $datasource
   * @param $modelId
   * @return unknown_type
   */
  public function method_getRecordInfoHtml( $datasource, $modelId )
  {
    qcl_assert_valid_string($datasource,"Invalid datasource");
    qcl_assert_integer($modelId,"Invalid model id");

    $userModel = $this->getApplication()->getAccessController()->getUserModel();
    $refModel = $this->getControlledModel( $datasource );

    $refModel->load($modelId);
    $createdBy = $refModel->get("createdBy");
    if ( $createdBy )
    {
      try
      {
        $userModel->load( $createdBy );
        $createdBy = $userModel->getName();
      }
      catch( qcl_data_model_RecordNotFoundException $e) {}
    }

    $modifiedBy = $refModel->get("modifiedBy");
    if ( $modifiedBy )
    {
      try
      {
        $userModel->load( $modifiedBy );
        $modifiedBy = $userModel->getName();
      }
      catch( qcl_data_model_RecordNotFoundException $e) {}
    }

    $status = $refModel->get('markedDeleted')
      ? _("Record is marked for deletion")
      : "";

    $html = "<table>";
    $html .= "<tr><td><b>" . _("Reference id") . ":</b></td><td>" . $refModel->id() . "</td></tr>";
    $html .= "<tr><td><b>" . _("Created") . ":</b></td><td>" . $refModel->getCreated() . "</td></tr>";

    $html .= "<tr><td><b>" . _("Created by") . ":</b></td><td>" . $createdBy . "</td></tr>";
    $html .= "<tr><td><b>" . _("Modified") . ":</b></td><td>" . $refModel->getModified() . "</td></tr>";
    $html .= "<tr><td><b>" . _("Modified by") . ":</b></td><td>" . $modifiedBy . "</td></tr>";
    $html .= "<tr><td><b>" . _("Status") . ":</b></td><td>" . $status . "</td></tr>";
    $html .= "</html>";

    return array(
      'html'  => $html
    );
  }

  /**
   * Returns data on folders that contain the given reference
   * @param $datasource
   * @param $modelId
   * @return array
   */
  public function method_getContainingFolderData( $datasource, $modelId )
  {
    qcl_assert_valid_string($datasource,"Invalid datasource");
    qcl_assert_integer($modelId,"Invalid model id");
    qcl_import("bibliograph_service_Folder");

    $refModel = $this->getControlledModel( $datasource );
    $refModel->load($modelId);
    $fldController = bibliograph_service_Folder::getInstance();
    $fldModel = $fldController->getFolderModel( $datasource );
    $data = array();
    try
    {
      $fldModel->findLinked( $refModel );
      while( $fldModel->loadNext() )
      {
        $data[] = array(
          $fldModel->id(),
          $fldController->getIcon("default"),
          $fldModel->getLabelPath("/")
        );
      }
    }
    catch( qcl_data_model_Exception $e ){
      //
    }
    return $data;
  }


  /**
   * Returns potential duplicates in a simple data model format.
   * @param string $datasource
   * @param int $modelId
   * @return array
   */
  function method_getDuplicatesData( $datasource, $modelId )
  {
    qcl_assert_valid_string($datasource,"Invalid datasource");
    qcl_assert_integer($modelId,"Invalid model id");
    
    $refModel = $this->getControlledModel( $datasource );
    $refModel->load( $modelId );
    $threshold = $this->getApplication()->getConfigModel()->getKey("bibliograph.duplicates.threshold");
    $scores = $refModel->findPotentialDuplicates($threshold);
    $data = array();
    while( $refModel->loadNext() )
    {
      $score = round(array_shift( $scores ));
      if ( $refModel->id() == $modelId or
        $refModel->get( "markedDeleted" ) )
      {
        continue;
      }

      $data[] = array(
        $refModel->id(),
        $refModel->getSchemaModel()->getTypeLabel( $refModel->getReftype() ),
        $refModel->getAuthor(),
        $refModel->getYear(),
        $refModel->getTitle(),
        $score
      );
    }
    return $data;
  }

  /**
   * Purges references that have been marked for deletion
   * @param $datasource
   * @return string "OK"
   */
  public function method_purge( $datasource )
  {
    $this->requirePermission("trash.empty");

    /*
     * delete marked references
     */
    $referenceModel = $this->getControlledModel( $datasource );
    $referenceModel->findWhere( array( 'markedDeleted' => true ) );
    while( $referenceModel->loadNext() )
    {
      $referenceModel->delete();
    }

    /*
     * update trash folder reference count
     * todo: there should be method to do that
     */
    qcl_import("bibliograph_service_Folder");
    $folderService = bibliograph_service_Folder::getInstance();
    $trashfolderId = $folderService->getTrashfolderId( $datasource );
    $folderModel = $folderService->getFolderModel( $datasource );
    $folderModel->load( $trashfolderId );
    $referenceCount = count( $referenceModel->linkedModelIds( $folderModel ) );
    $folderModel->set( "referenceCount", $referenceCount );
    $folderModel->save();

    $this->broadcastClientMessage("folder.reload",array(
      'datasource'  => $datasource,
      'folderId'    => $trashfolderId
    ));

    return "OK";
  }

  public function method_findReplaceDialog( $datasource, $folderId, $selectedIds )
  {
    qcl_assert_valid_string( $datasource, "Invalid datasource argument");

    /*
     * prepare field list
     */
    $schemaModel = $this->getControlledModel( $datasource )->getSchemaModel();
    $fields = $schemaModel->fields();
    $fieldOptions = array(
      array( 'value' => "all", 'label' => _("All fields") )
    );
    foreach( $fields as $field )
    {
      $fieldOptions[] = array( 'value' => $field, 'label' => $schemaModel->getFieldLabel($field) );
    }

    /*
     * form data
     */
    $formData = array(
      'scope'  => array(
        'label'   => _("Search in:"),
        'type'    => "selectbox",
        'options' => array(
          array( 'value' => 'all',      'label' => _("Whole database") ),
          array( 'value' => 'selected', 'label' => _("Selected records") ),
          array( 'value' => 'folder',   'label' => _("Selected folder") )
        ),
        'width'   => 200,
      ),
      'field'  => array(
        'label'   => _("Replace in:"),
        'type'    => "selectbox",
        'options' => $fieldOptions
      ),
      'find'  => array(
        'label'   => _("Search expression:"),
        'type'    => "textfield"
      ),
      'replace'  => array(
        'label'   => _("Replace with:"),
        'type'    => "textfield"
      ),
      'backup'  => array(
        'label'   => _("Create a backup?"),
        'type'    => "selectbox",
        'options' => array(
          array( 'value' => true, 'label' => _("Yes") ),
          array( 'value' => false, 'label' => _("No") )
        )
      ),
    );
    qcl_import("qcl_ui_dialog_Form");
    $args = func_get_args();
    return new qcl_ui_dialog_Form(
      _("You can do a 'find and replace' operation on all or selected records in the database. These changes cannot easily be undone, that is why it is recommended to create a backup."),
      $formData,
      true,
      $this->serviceName(), "confirmFindReplace",
      $args
    );
  }

  public function method_confirmFindReplace( $data, $datasource, $folderId, $selectedIds )
  {
    if ( $data === null )
    {
      return "ABORTED";
    }

    qcl_assert_valid_string($data->find, "Invalid 'find' argument");
    if ( $data->replace === null ) $data->replace ="";
    qcl_assert_string($data->replace, "Invalid 'replace' argument");

    $msg_map = array(
      'all'       => _("in all records"),
      'selected'  => _("in the selected records"),
      'folder'    => _("in the selected folder")
    );
    $schemaModel = $this->getControlledModel( $datasource )->getSchemaModel();
    qcl_import("qcl_ui_dialog_Confirm");
    $args =  func_get_args();
    return new qcl_ui_dialog_Confirm(
      $this->tr(
       "Are you sure you want to replace '%s' with '%s' in %s %s?",
       $data->find, $data->replace,
       $data->field == "all"
         ? _("all fields")
         : $this->tr( "field '%s'", $schemaModel->getFieldLabel( $data->field ) ),
       $msg_map[$data->scope]
      ),
      null,
      $this->serviceName(), "findReplace", $args
    );
  }

  public function method_findReplace( $go, $data, $datasource, $folderId, $selectedIds )
  {
    if ( ! $go )
    {
      return "ABORTED";
    }

    /*
     * backup?
     */
    if ( $data->backup )
    {
      qcl_import("bibliograph_service_Backup");
      $backupService = new bibliograph_service_Backup();
      $zipfile = $backupService->createBackup( $datasource );
    }

    $model = $this->getControlledModel($datasource);

    /*
     * action!
     */
    switch( $data->scope )
    {
      case "all":

        switch( $data->field )
        {
          case "all":
            throw new JsonRpcException("Find/Replace in all columns not yet implemented");

          default:
            //todo: check field
            $table = $model->getQueryBehavior()->getTable();
            $count = $table->replace( $data->field, $data->find, $data->replace );
            break;
        }

        break;

      case "selected":
        qcl_assert_array( $selectedIds );
        throw new JsonRpcException("Find/Replace in selected records not yet implemented");
        break;

      case "folder":
        qcl_assert_integer( $folderId );
        throw new JsonRpcException("Find/Replace in folder not yet implemented");
        break;

      default:
        throw new JsonRpcException("Invalid scope argument");
    }
    qcl_import("qcl_ui_dialog_Alert");
    return new qcl_ui_dialog_Alert(
      $this->tr(
        "%s replacements made. Please reload to see the changes. %s",
        $count,
        $data->backup
           ? $this->tr("In case you want to revert the changes, a backup file '%s' has been created.",$zipfile)
           : ""
      )
    );
  }


  public function method_getSearchHelpHtml( $datasource )
  {
    qcl_assert_valid_string($datasource, "No datasource given");

    $html = "<p>";
    $html .= _("You can use the search features of this application in two ways.");
    $html .= " ". _("Either you simply type in keywords like you would do in a google search.");
    $html .= " ". _("Or you compose complex queries using field names, comparison operators, and boolean connectors (for example: title contains constitution and year=1981).");
    $html .= " ". _("You can use wildcard characters: '?' for a single character and '*' for any amount of characters.");
    $html .= " ". _("When using more than one word, the phrase has to be quoted (For example, title startswith \"Recent developments\").");
    $html .= " ". _("You can click on any of the terms below to insert them into the query field.");
    $html .= "</p>";

    $model = $this->getControlledModel( $datasource );
    $schemaModel = $model->getSchemaModel();

    /*
     * field names
     */
    $html .= "<h4>" . _("Field names") . "</h4>";
    $html .= "<p style='line-height:2em'>";

    $style = "border: 1px solid grey; padding:2px";

    $indexes = array();
    $activeUser = $this->getActiveUser();
    foreach( $schemaModel->fields() as $field )
    {
      $data = $schemaModel->getFieldData( $field );

      if( isset( $data['index'] ) )
      {
        if ( isset( $data['public'] ) and $data['public'] === false )
        {
          if ( $activeUser->isAnonymous() ) continue;
        }
        foreach( (array) $data['index'] as $index )
        {
          $indexes[] = $index;
        }
      }
    }

    sort( $indexes );
    foreach( array_unique($indexes) as $index )
    {
      /*
       * don't show already translated field names
       */
      $html .= "<span style='$style' value='$index'>$index</span> ";
    }

    /*
     * modifiers
     */
    $html .= "</p><h4>" . _("Comparison modifiers") . "</h4>";
    $html .= "<p style='line-height:2em'>";

    $style = "border: 1px solid grey; padding:2px";

    qcl_import("bibliograph_schema_CQL");
    $qcl = bibliograph_schema_CQL::getInstance();
    $modifiers = $qcl->modifiers;

    sort( $modifiers );
    foreach( $modifiers as $modifier )
    {
      /*
       * don't show already translated field names
       */
      $html .= "<span style='$style' value='$modifier'>$modifier</span> ";
    }

      /*
     * modifiers
     */
    $html .= "</p><h4>" . _("Boolean operators") . "</h4>";
    $html .= "<p style='line-height:2em'>";

    $style = "border: 1px solid grey; padding:2px";

    $booleans = array( _("and") );

    sort( $booleans );
    foreach( $booleans as $boolean )
    {
      /*
       * don't show already translated field names
       */
      $html .= "<span style='$style' value='$boolean'>$boolean</span> ";
    }

    $html .= "</p>";

    return $html;
  }
}
?>