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
use app\models\Reference;
use lib\Validate;

class ReferenceController extends AppController
{

  /*
  ---------------------------------------------------------------------------
     STATIC PROPERTIES & METHODS
  ---------------------------------------------------------------------------
  */

  /**
   * The main model type of this controller
   */
  static $modelClassType = "reference";

  /**
   * Icons for the folder nodes, depending on type
   * @var array
   */
  static $icon = array(
    "favorites"       => "icon/16/actions/help-about.png"
  );

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

  /*
  ---------------------------------------------------------------------------
     HELPER METHODS
  ---------------------------------------------------------------------------
  */  

  /**
   * Overridden to create qcl-compliant 'where' structure from a
   * pseudo- CQL query string.
   *
   * @see http://www.loc.gov/standards/sru/resources/cql-context-set-v1-2.html
   *
   * @param stdClass $query
   *    The query data object from the json-rpc request
   * @param qcl_data_db_Query $qclQuery
   *    The query object used by the query behavior
   * @param qcl_data_model_AbstractActiveRecord $modelClass
   *    The model on which the query should be performed
   * @throws JsonRpcException
   * @return qcl_data_db_Query
   */
  public function addQueryConditions(
    stdClass $query,
    qcl_data_db_Query $qclQuery,
    qcl_data_model_AbstractActiveRecord $modelClass )
  {
    if ( isset( $query->link ) )
    {
      $qclQuery->link = object2array( $query->link );
      return $qclQuery;
    }
    elseif ( isset ( $query->cql ) )
    {
      
      $cql =  bibliograph_schema_CQL::getInstance();
      try
      {
        $q = $cql->addQueryConditions( $query, $qclQuery, $modelClass );
      }
      catch( bibliograph_schema_Exception $e)
      {
        throw new \Exception($e->getMessage());
      }
      $q->where['markedDeleted'] = false;
      return $q;
    }
    else
    {
      throw new \Exception( Yii::t('app', "No recognized query format in request.") );
    }
  }

  /**
   * Returns an array of ListItem data
   * @param $datasource
   * @return array
   */
  public function getAddItemListData( $datasource )
  {
    $schema = static::getControlledModel( $datasource )::getSchema();
    $reftypes  = $schema->types();
    $options = array();
    foreach ( $reftypes as $reftype )
    {
      $options[] = array(
        'value' => $reftype,
        'icon'  => null, //"icon/16/actions/document-new.png",
        'label' => Yii::t('app', $schema->getTypeLabel( $reftype ) )
      );
    }
    return $options;
  }

  /*
  ---------------------------------------------------------------------------
     ACTIONS
  ---------------------------------------------------------------------------
  */

  /**
   * Returns the layout of the columns of the table displaying
   * the records
   *
   * @param $datasource
   * @param null $modelClassType
   * @return unknown_type
   */
  public function actionTableLayout( $datasource, $modelClassType=null )
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
          'header'  => Yii::t('app', "Author"),
          'width'   => "1*"
        ),
        'year' => array(
          'header'  => Yii::t('app', "Year"),
          'width'   => 50
        ),
        'title' => array(
          'header'  => Yii::t('app', "Title"),
          'width'   => "3*"
        )
      ),
      'queryData' => array(
        'link'    => array( 'relation' => "Folder_Reference" ),
        'orderBy' => "author,year,title",
      ),
      'addItems' => $this->getAddItemListData( $datasource, $modelClassType )
    );
  }


  /**
   * Returns the form layout for the given reference type and
   * datasource
   *
   * @param $datasource
   * @param $reftype
   * @internal param \Reference $refType type
   * @return array
   */
  function actionFormLayout( $datasource, $reftype )
  {
    $modelClass  = static::getControlledModel( $datasource );
    $schema = $modelClass::getSchema();

    // get fields to display in the form
    $fields = array_merge(
      $schema->getDefaultFormFields(),
      $schema->getTypeFields( $reftype )
    );

    // remove excluded fields
    $excludeFields = Yii::$app->utils->getPreference("datasource.$datasource.fields.exclude");
    if( count( $excludeFields ) ) {
      $fields = array_diff( $fields, $excludeFields );
    }

    // Create form
    $formData = array();
    foreach ( $fields as $field )
    {
      $formData[$field] = $schema->getFormData( $field );
      if ( ! $formData[$field] )
      {
        $formData[$field] = array(
          "type"  => "textfield",
        );
      }

      // replace placeholders
      elseif ( isset( $formData[$field]['bindStore']['params'] ) ) {
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

      // setup autocomplete data
      if ( isset( $formData[$field]['autocomplete'] ) )
      {
        $formData[$field]['autocomplete']['service'] = $this->serviceName();
        $formData[$field]['autocomplete']['method'] = "getAutoCompleteData";
        $formData[$field]['autocomplete']['params'] = array( $datasource, $field );
      }

      // Add label
      if ( ! isset( $formData[$field]['label'] ) ) {
        $formData[$field]['label'] = $schema->getFieldLabel( $field, $reftype );
      }
    }
    return $formData;
  }

  /**
   * Returns data for the reference type select box
   * @param $datasource
   * @return array
   */
  public function actionReferenceTypeList( $datasource )
  {
    $modelClass  = static::getControlledModel( $datasource );
    $schema = $modelClass::getSchema();
    $result = array();
    foreach( $schema->types() as $type )
    {
      $result[] = array(
        'label' => $schema->getTypeLabel( $type ),
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
  function actionReferenceTypeData( $datasource )
  {
    return $this->getAddItemListData( $datasource );
  }

  /**
   * Returns the requested or all accessible properties of a reference 
   * @param string $datasource
   * @param $arg2
   * @param null $arg3
   * @param null $arg4
   * @throws InvalidArgumentException
   * @return array
   * @todo: this method is called with different signatures!
   */
  function actionItem( $datasource, $arg2, $arg3= null, $arg4=null)
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

    if ( ! $datasource or ! is_numeric( $id ) )
    {
      throw new \InvalidArgumentException("Invalid arguments.");
    }

    // load model record and get reference type
    $modelClass    = static::getControlledModel( $datasource )::findOne($id);
    $reftype  = $modelClass->reftype;
    $schema   = $modelClass->schema;

    // determine the fields to return the values for
    $fields = array_merge(
      $schema->getDefaultFieldsBefore(),
      $schema->getTypeFields( $reftype ),
      $schema->getDefaultFieldsAfter()
    );
    
    // exclude fields
    $excludeFields = Yii::$app->utils->getPreference("datasource.$datasource.fields.exclude");
    if( count( $excludeFields ) )
    {
      $fields = array_diff( $fields, $excludeFields );
    }

    // prepare record data for the form
    $reference = array(
      'datasource'  => $datasource,
      'referenceId' => $id, // todo: replace by "id"
      'titleLabel'  => $this->getTitleLabel( $modelClass )
    );

    foreach ( $fields as $field )
    {
      try {
        $fieldData = $schema->getFieldData( $field );
      } catch( \InvalidArgumentException $e ) {
        $this->warn("No field data for field '$field'");
        continue;
      }
      $value = $modelClass->$field;

      // replace field separator with form separator if both exist
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

      // store value
      $reference[$field] = $value;
    }

    return $reference;
  }

  /**
   * ???
   *
   * @param [type] $modelClass
   * @return void
   */
  protected function getTitleLabel( $modelClass )
  {
    return static::$modelType;
    /*
        "<b>" .
        ($modelClass.author || data.editor || "No author" ).replace( /\n/, "/" ) +
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
  public function actionAutocomplete( $datasource, $field, $input )
  {
    $modelClass = static::getControlledModel( $datasource );
    $fieldData = $modelClass::getSchema()->getFieldData( $field );
    $separator = $fieldData['separator'];
    $suggestionValues = $modelClass :: select($field)
      ->where(["like", $field, $input] )
      ->column();

    if ( $separator )
    {
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
      $suggestionValues = $modelClass->getQueryBehavior()->fetchValues($field,array(
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
  public function actionSave( $datasource, $referenceId, $data )
  {
    // transform data into array
    $data = json_decode(json_encode($data), true);
    $modelClass = static::getControlledModel( $datasource );    

    // save user-supplied data
    foreach( $data as $property => $value )
    {
      // replace form separator with field separator
      $fieldData = $modelClass::getSchema()->getFieldData( $property );
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
      $this->actionUpdateItem( $datasource, static::$modelType, $referenceId, $property, $value );
    }

    // add metadata
    $modelClass = static::getControlledModel( $datasource );
    $record = $modelClass::findOne( $referenceId );

    // modified by
    if( $modelClass::hasAttribute('modifiedBy') ) {
      $record->modifiedBy = $this->getActiveUser()->getUsername();
      $record->save();
    }

    // citation key
    if( ! trim($record->citekey) and 
      $record->creator and $record->year and $record->title )
    {
      $newCitekey = $record->computeCiteKey();
      $data = array(
        'datasource' => $datasource,
        'modelType'  => "reference",
        'modelId'    => $referenceId,
        'data'       => array( "citekey" => $newCitekey )
      );
      $this->broadcastClientMessage("bibliograph.fieldeditor.update", $data );
      $record->citekey = $newCitekey;
      $record->save();
    }
    return "OK";
  }

  /**
   * Returns data for a ComboBox widget.
   * @param $datasource
   * @param $field
   * @return unknown_type
   */
  public function actionListField( $datasource, $field )
  {
    $values = static 
      :: getControlledModel( $datasource ) 
      :: select($field)
      -> column();

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
   * @throws \InvalidArgumentException
   */
  public function actionCreate( $datasource, $folderId, $reftype )
  {
    $this->requirePermission( "reference.add" ); 
    $modelClass = static::getControlledModel( $datasource );
    $reference = new $modelClass( [
      'reftype'     => $reftype,
      'createdBy'   => $this->getActiveUser()->getUsername()
     ]);
     $reference->save();
    
    $folderClass = 
    $folder = static :: getFolderModel() :: findOne( $folderId );

    if( ! $folder ){
      throw new \InvalidArgumentException("Folder #$folderId does not exist.");
    } 
    $folder -> link($reference);
    $folder -> referenceCount = $folder -> getFolderReferences()->count();
    $folder -> save();

    // reload references
    $this->dispatchClientMessage("folder.reload", array(
      'datasource'  => $datasource,
      'folderId'    => $folderId
    ) );

    // select the new reference
    $this->dispatchClientMessage("bibliograph.setModel", array(
      'datasource'    => $datasource,
      'modelType'     => static::$modelType,
      'modelId'       => $reference->id
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
   * @throws InvalidArgumentException
   */
  public function actionRemove( $first, $second=null, $third=null, $ids=null )
  {
    // removal cancelled
  	if( $first === false ) {
  		return "CANCELLED";
  	}

    // removal confirmed
    elseif ( $first === true and is_string( $second) ) {
      $confirmRemove = true;
      list( $datasource, $folderId, $ids ) = $this->unshelve( $second );
    }

    // API signature
  	elseif ( is_string($first) and is_array( $ids) ) {
      $confirmRemove = false;
      $datasource = $first;
      $folderId   = $second;
    }

    // wrong parameters
    else {
      throw new \InvalidArgumentException("Invalid arguments for bibliograph.reference.removeReferences");
    }

    // folderId vs query
    $query = null;
    if( ! is_integer($folderId) ) {
      $query = $folderId;
      $folderId = null;
    }

    $this->requirePermission("reference.remove");
    $referenceModel = static::getControlledModel( $datasource );
    $folderModel    = static::getFolderModel( $datasource );

    //$this->debug( array($datasource, $folderId, $ids) );

    // use the first id
    $id = intval( $ids[0] );

    // load record and count the number of links to folders
    $reference = $referenceModel::findOne( $id );
    $containedFolderIds = $reference->getReferenceFolders()->select("id")->column(); 
    $folderCount = count( $containedFolderIds );

    // if we have no folder id and more than one folders contain the reference,
    // we need to ask the user first
    if ( ! $folderId ) {
      if ( $folderCount > 1 and ! $confirmRemove ) {
        return \lib\dialog\Confirm::create(
          Yii::t('app',
            "The selected record '%s' is contained in %s folders. Move to the trash anyways?",
            ( $reference->title . " (" . $reference->year . ")" ),
            $folderCount
          ),
          null,
          "reference", "remove",
          array( $this->shelve( $datasource, $query, $ids ) )
        );
      }

      // confirmed
      else {
        $referenceModel->unlinkAll( "folder" );
        $folderCount = 0;
      }
    }

    // unlink from folder if id is given.
    else {
      $reference->unlink($folder);
    }

    $foldersToUpdate = $containedFolderIds;

    // move to trash only if it was contained in one or less folders
    if ( $folderCount < 2 )
    {
      // link with trash folder
      $trashFolder  = \app\controllers\FolderController::getTrashFolder(); 
      $trashFolder  -> link( $reference );

      $foldersToUpdate[] = $trashFolderId;

      // mark as deleted
      $reference -> markedDeleted = 1;
      $reference -> save();
    }

    // update reference count in source and target folders
    $foldersToUpdate= array_unique($foldersToUpdate);
    foreach( $foldersToUpdate as $fid )
    {
      $folder = $folderModel::findOne( $fid );
      if( $folder ) {
        $folder->getReferenceCount(true);
      } else {
        Yii::warning("Folder #$fid does not exist.");
      }
    }

    /*
     * display change on connected clients
     */
    foreach( $containedFolderIds as $fid )
    {
	    $this->broadcastClientMessage("reference.removeRows", array(
	      'datasource' => $datasource,
	      'folderId'   => $fid,
        'query'      => null,
	      'ids'        => array($id)
	    ) );
    }
    if( $query )
    {
      $this->broadcastClientMessage("reference.removeRows", array(
        'datasource' => $datasource,
        'folderId'   => null,
        'query'      => $query,
        'ids'        => array($id)
      ) );
    }

    /*
     * if there are references left, repeat
     */
    if ( count($ids) > 1 )
    {
      array_shift($ids);
      return $this->actionRemove( $datasource, $folderId, null, $ids );
    }
    return "OK";
  }
  
  /**
   * Removes all references from a folder
   *
   * @param strin $datasource
   * @param int $folderId
   * @return void
   */
  public function actionFolderRemove( $datasource, $folderId )
  {
    $this->requirePermission("reference.batchedit");
    
    $referenceModel = static::getControlledModel( $datasource );
    $folderModel    = static::getFolderModel( $datasource );
    $folder         = $folderModel :: findOne($folderId);
    $references     = $folder -> getReferences() -> all();

    $foldersToUpdate = [$folderId];
    $referencesToTrash = [];
    foreach( $references as $reference )
    {
      $folderCount = $reference -> getFolders() -> count();
      $referenceModel -> unlink( $folder );
      if ( $folderCount == 1 )
      {
        $referencesToTrash[] = $reference;
      }
    }
    if ( count( $referencesToTrash ) )
    {
      $trashFolder  = \app\controllers\FolderController::getTrashFolder(); 
      foreach($referencesToTrash as $reference)
      {
        $trashFolder -> link($reference);
      }
    }    
    foreach( $foldersToUpdate as $fid )
    {
      $folder = $folderModel :: findOne( $fid );
      if( ! $folder ){
        Yii :: warning("Folder #$fid does not exist");
      }
      $folder -> getReferenceCount(true);
      $this->broadcastClientMessage("folder.reload",array(
        'datasource'  => $datasource,
        'folderId'    => $fid
      ));      
    }
    return "OK";
  }

  /**
   * Move references from one folder to another folder
   *
   * @param string|true $datasource If true, it is the result of the confirmation
   * @param int $folderId The folder to move from
   * @param int $targetFolderId The folder to move to
   * @param int[] $ids The ids of the references to move
   * @return "OK"
   */
  public function actionMove( $datasource, $folderId, $targetFolderId, $ids)
  {
    $this->requirePermission("reference.move");

    if( $datasource === true ){
      list( $confirmed, $datasource, $folderId, $targetFolderId, $ids ) = func_get_args();
    } else {
      $confirmed = false;
    }
    $folderModel  = static :: getFolderModel($datasource);
    $sourceFolder = $folderModel :: findOne($folderId);
    $targetFolder = $folderModel :: findOne($targetFolderId);

    Validate :: isNotNull( $sourceFolder, "Folder #$folderId does not exist" );
    Validate :: isNotNull( $targetFolder, "Folder #$targetFolderId does not exist" );

    if( ! $confirmed ) {
      return \lib\dialog\Confirm::create(
        Yii::t('app', "This will move {countReferences} from '{sourceFolder}' to '{targetFolder}'. Proceed?",[
          'countReferences' => count($ids),
          'sourceFolder'    => $sourceFolder -> label,
          'targetFolder'    => $targetFolder -> label
        ]),
        "reference","move", func_get_args()
      );
    } else {
      return $this->move( $references, $sourceFolder, $targetFolder);
    }
  }


  /**
   * Move reference from one folder to another folder
   *
   * @param \app\models\Reference[]|int[] $references either an array of reference
   *    objects or an array of the ids of that object
   * @param \app\models\Folder $sourceFolder
   * @param \app\models\Folder $targetFolder
   * @return string "OK"
   */
  public function move( 
    array $references, 
    \app\models\Folder $sourceFolder, 
    \app\models\Folder $targetFolder 
  ) {
    foreach( $references as $reference ) {
      if (is_numeric($reference) ) {
        $reference = static :: getControlledModel() -> findOne($reference);
      }
      if( ! ( $reference instanceof \app\models\Reference ) ){
        Yii::warning("Skipping invalid reference '$reference'");
      }
      $sourceFolder -> unlink( $reference );
      $targetFolder -> link( $reference );
    }

    // update reference count
    $sourceFolder -> getReferenceCount(true);
    $targetFolder -> getReferenceCount(true);

    // display change on connected clients
    if( count( $ids ) )
    {
      $this->broadcastClientMessage("reference.removeRows", [
        'datasource' => $datasource,
        'folderId'   => $sourceFolder->id,
        'query'      => null,
        'ids'        => $ids
      ]);
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
  public function actionCopy( $datasource, $folderId, $targetFolderId, $ids )
  {
    $this->requirePermission("reference.move");
    if( $datasource === true ){
      list( $confirmed, $datasource, $folderId, $targetFolderId, $ids ) = func_get_args();
    } else {
      $confirmed = false;
    }
    $folderModel  = static :: getFolderModel($datasource);
    $sourceFolder = $folderModel :: findOne($folderId);
    $targetFolder = $folderModel :: findOne($targetFolderId);

    Validate :: isNotNull( $sourceFolder, "Folder #$folderId does not exist" );
    Validate :: isNotNull( $targetFolder, "Folder #$targetFolderId does not exist" );

    if( ! $confirmed ) {
      return \lib\dialog\Confirm::create(
        Yii::t('app', "This will copy {countReferences} from '{sourceFolder}' to '{targetFolder}'. Proceed?",[
          'countReferences' => count($ids),
          'sourceFolder'    => $sourceFolder -> label,
          'targetFolder'    => $targetFolder -> label
        ]),
        "reference","copy", func_get_args()
      );
    } else {
      return $this->copy( $references, $sourceFolder, $targetFolder);
    }
  }

  /**
   * Copy reference from one folder to another folder
   *
   * @param \app\models\Reference[]|int[] $references either an array of reference
   *    objects or an array of the ids of that object
   * @param \app\models\Folder $sourceFolder
   * @param \app\models\Folder $targetFolder
   * @return string "OK"
   */
  public function copy( 
    array $references, 
    \app\models\Folder $sourceFolder, 
    \app\models\Folder $targetFolder 
  ) {
    foreach( $references as $reference ) {
      if (is_numeric($reference) ) {
        $reference = static :: getControlledModel() -> findOne($reference);
      }
      if( ! ( $reference instanceof \app\models\Reference ) ){
        Yii::warning("Skipping invalid reference '$reference'");
      }
      $targetFolder -> link( $reference );
    }

    // update reference count
    $targetFolder -> getReferenceCount(true);
    return "OK";
  }  

  /**
   * Returns information on the record as a HTML table
   * @param $datasource
   * @param $referenceId
   * @return unknown_type
   */
  public function actionTableHtml( $datasource, $referenceId )
  {
    $referenceModel = static :: getControlledModel( $datasource );
    $reference = $referenceModel :: findOne( $referenceId );
    Validate :: isNotNull( $reference, "Reference #$referenceId does not exist.");
    
    $createdBy = $reference->createdBy;
    if ( $createdBy ) {
      $user = User :: findOne( ['namedId' => $createdBy ] );
      if ( $user ) $createdBy = $user->name;
    }
    $modifiedBy = $reference->modifiedBy;
    if ( $modifiedBy ){
      $user = User :: findOne( ['namedId' => $createdBy ] );
      if ( $user ) $modifiedBy = $user->name;
    }

    $status = 
      $reference->markedDeleted ?
      Yii::t('app', "Record is marked for deletion") : "";

    $html = "<table>";
    $html .= "<tr><td><b>" . Yii::t('app', "Reference id") . ":</b></td><td>" . $reference->id . "</td></tr>";
    $html .= "<tr><td><b>" . Yii::t('app', "Created") . ":</b></td><td>" . $reference->created . "</td></tr>";

    $html .= "<tr><td><b>" . Yii::t('app', "Created by") . ":</b></td><td>" . $createdBy . "</td></tr>";
    $html .= "<tr><td><b>" . Yii::t('app', "Modified") . ":</b></td><td>" . $reference->modified . "</td></tr>";
    $html .= "<tr><td><b>" . Yii::t('app', "Modified by") . ":</b></td><td>" . $modifiedBy . "</td></tr>";
    $html .= "<tr><td><b>" . Yii::t('app', "Status") . ":</b></td><td>" . $status . "</td></tr>";
    $html .= "</html>";

    return array(
      'html'  => $html
    );
  }

  /**
   * Returns a HTML table with the reference data
   * @param $datasource
   * @param $id
   * @return unknown_type
   */
  public function actionItemHtml( $datasource, $id )
  {
    $modelClass = static::getControlledModel( $datasource );
    $schema     = $modelClass::getSchema();
    $reference  = $modelClass::findOne( $id );
    $reftype    = $reference->reftype;
    
    $fields = array_merge(
      array("reftype"),
      $schema->getTypeFields( $reftype ),
      array("keywords","abstract")
    );

    // create html table
    $html = "<table>";
    //$reference = array();
    
    foreach ( $fields as $field )
    {
      $value = $modelClass->get( $field );
      if ( ! $value or ! $schema->isPublicField( $field ) ) continue;
      $label = $modelClass::getSchema()->getFieldLabel( $field, $reftype );

      // special fields
      switch( $field )
      {
        case "reftype":
          $value = $schema->getTypeLabel( $value );
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
   * Returns data on folders that contain the given reference
   * @param $datasource
   * @param $referenceId
   * @return array
   */
  public function actionContainers( $datasource, $referenceId )
  {
    $reference = static :: getRecordById( $datasource, $referenceId );
    $folders = $reference -> getFolders() -> all();
    $data = array();
    foreach( $folders as $folder ){
      $data[] = [
        $folder -> id,
        $folder -> getIcon("default"),
        $folder -> getLabelPath("/")
      ];
    }
    return $data;
  }


  /**
   * Returns potential duplicates in a simple data model format.
   * @param string $datasource
   * @param int $referenceId
   * @return array
   */
  function method_getDuplicatesData( $datasource, $referenceId )
  {
    notImplemented();
    
    $referenceModel = static::getControlledModel( $datasource );
    $referenceModel->load( $referenceId );
    $threshold = $this->getApplication()->getConfigModel()->getKey("bibliograph.duplicates.threshold");
    $scores = $referenceModel->findPotentialDuplicates($threshold);
    $data = array();
    while( $referenceModel->loadNext() )
    {
      $score = round(array_shift( $scores ));
      if ( $referenceModel->id() == $referenceId or
        $referenceModel->get( "markedDeleted" ) )
      {
        continue;
      }
      $reftype = $referenceModel->getReftype();
      $data[] = array(
        $referenceModel->id(),
        $reftype ? $referenceModel::getSchema()->getTypeLabel( $reftype ) : "",
        $referenceModel->getAuthor(),
        $referenceModel->getYear(),
        $referenceModel->getTitle(),
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
    $referenceModel = static::getControlledModel( $datasource );
    $referenceModel->findWhere( array( 'markedDeleted' => true ) );
    while( $referenceModel->loadNext() )
    {
      $referenceModel->delete();
    }

    /*
     * update trash folder reference count
     * todo: there should be method to do that
     */
    $trashFolder  = \app\controllers\FolderController::getTrashFolder(); 
    $folderModel = $folderService->getFolderModel( $datasource );
    $folderModel->load( $trashfolderId );
    $referenceCount = count( $referenceModel->linkedModelIds( $folderModel ) );
    $folderModel->set( "referenceCount", $referenceCount );
    $folderModel->save();

    $this->broadcastClientMessage("folder.reload",array(
      'datasource'  => $datasource,
      'folderId'    => $trashfolder->id
    ));

    return "OK";
  }



  public function method_getSearchHelpHtml( $datasource )
  {
    qcl_assert_valid_string($datasource, "No datasource given");

    $html = "<p>";
    $html .= Yii::t('app', "You can use the search features of this application in two ways.");
    $html .= " ". Yii::t('app', "Either you simply type in keywords like you would do in a google search.");
    $html .= " ". Yii::t('app', "Or you compose complex queries using field names, comparison operators, and boolean connectors (for example: title contains constitution and year=1981).");
    $html .= " ". Yii::t('app', "You can use wildcard characters: '?' for a single character and '*' for any amount of characters.");
    $html .= " ". Yii::t('app', "When using more than one word, the phrase has to be quoted (For example, title startswith \"Recent developments\").");
    $html .= " ". Yii::t('app', "You can click on any of the terms below to insert them into the query field.");
    $html .= "</p>";

    $modelClass = static::getControlledModel( $datasource );
    $schema = $modelClass::getSchema();

    /*
     * field names
     */
    $html .= "<h4>" . Yii::t('app', "Field names") . "</h4>";
    $html .= "<p style='line-height:2em'>";

    $style = "border: 1px solid grey; padding:2px";

    $indexes = array();
    $activeUser = $this->getActiveUser();
    foreach( $schema->fields() as $field )
    {
      $data = $schema->getFieldData( $field );

      if( isset( $data['index'] ) )
      {
        if ( isset( $data['public'] ) and $data['public'] === false )
        {
          if ( $activeUser->isAnonymous() ) continue;
        }
        foreach( (array) $data['index'] as $index )
        {
          $indexes[] = Yii::t('app', $index);
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
    $html .= "</p><h4>" . Yii::t('app', "Comparison modifiers") . "</h4>";
    $html .= "<p style='line-height:2em'>";

    $style = "border: 1px solid grey; padding:2px";

    
    $qcl = bibliograph_schema_CQL::getInstance();
    $modifiers = $qcl->modifiers;

    sort( $modifiers );
    foreach( $modifiers as $modifier )
    {
      $modifier = Yii::t('app', $modifier);
      $html .= "<span style='$style' value='$modifier'>$modifier</span> ";
    }

    /*
     * booleans
     */
    $html .= "</p><h4>" . Yii::t('app', "Boolean operators") . "</h4>";
    $html .= "<p style='line-height:2em'>";

    $style = "border: 1px solid grey; padding:2px";

    $booleans = array( Yii::t('app', "and") );

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
