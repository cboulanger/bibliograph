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
use app\models\Reference;
use lib\cql\NaturalLanguageQuery;
use Sse\Data;
use Yii;
use app\models\Datasource;
use app\schema\BibtexSchema;
use lib\exceptions\UserErrorException;
use lib\Validate;
use yii\db\ActiveQuery;
use yii\db\Exception;
use yii\db\Expression;
use yii\db\StaleObjectException;

class ReferenceController extends AppController
{
  use traits\FolderDataTrait;

  /**
   * Used by confirmation actions
   * @var bool
   */
  protected $confirm = false;

  /*
  ---------------------------------------------------------------------------
     STATIC PROPERTIES & METHODS
  ---------------------------------------------------------------------------
  */

  /**
   * The main model type of this controller
   */
  static $modelType = "reference";


  /**
   * Returns the name of the folder model class
   *
   * @param string $datasource
   * @return string
   * @todo Rename to getFolderModelClass()
   */
  static function getFolderModel($datasource)
  {
    return Datasource:: in($datasource, "folder");
  }

  /*
  ---------------------------------------------------------------------------
     HELPER METHODS
  ---------------------------------------------------------------------------
  */

  /**
   * Modifies the query with conditions supplied by the client
   *
   * The client query looks like this:
   * {
   *   'datasource" : "<datasource name>",
   *   "modelType" : "reference",
   *   "query" : {...}
   *   }
   * }
   * 
   * The query is either:
   * 
   *    {
   *     "properties" : ["author","title","year" ...],
   *     "orderBy" : "author",
   *     "relation" : {
   *       "name" : "folder",
   *       "id" : 3
   *     }
   *
   * or 
   *    {
   *     "properties" : ["author","title","year" ...],
   *     "orderBy" : "author",
   *     "cql" : "..."
   *    }
   *
   * The cql string can be localized, i.e. the indexes and operators can be in the
   * current application locale
   *
   * @param \stdClass $clientQueryData
   *    The query data object from the json-rpc request
   * @param string $modelClass
   *    The model class from which to create the query
   * @return \yii\db\ActiveQuery
   * @throws \InvalidArgumentException
   * @todo 'properties'should be 'columns' or 'fields', 'cql' should be 'input'/'search' or similar
   */
  protected function transformClientQuery(\stdClass $clientQueryData, string $modelClass)
  {
    $clientQuery = $clientQueryData->query;
    $datasourceName = $clientQueryData->datasource;

    // params validation
    if( ! class_exists($modelClass) ){
      throw new \InvalidArgumentException("Class '$modelClass' does not exist.");
    }
    // @todo doesn't work! use interface instead of base class
    if( ! is_subclass_of($modelClass,  Reference::class)){
      //throw new \InvalidArgumentException("Class '$modelClass' must be an subclass of " . Reference::class);
    }
    if (!is_object($clientQuery) or
      !is_array($clientQuery->properties)) {
      throw new \InvalidArgumentException("Invalid query data");
    }

    // it's a relational query
    if (isset($clientQuery->relation)) {

      // FIXME hack to support virtual folders
      if( $clientQuery->relation->id > 9007199254740991 - 10000 ){
        return $modelClass::find()->where(new Expression("TRUE = FALSE"));
      }

      // select columns, disambiguate id column
      $columns = array_map(function ($column) {
        return $column == "id" ? "references.id" : $column;
      }, $clientQuery->properties);

      /** @var ActiveQuery $activeQuery */
      $activeQuery = $modelClass::find()
        ->select($columns)
        ->alias('references')
        ->joinWith($clientQuery->relation->name,false)
        ->onCondition([$clientQuery->relation->foreignId => $clientQuery->relation->id]);

      //Yii::debug($activeQuery->createCommand()->getRawSql());

      return $activeQuery;
    }

    // it's a freeform search query
    if ( isset ( $clientQuery->cql ) ){

      // FIXME hack to support virtual folders
      if( str_contains( $clientQuery->cql, "virtsub:") ){
        return $modelClass::find()->where(new Expression("TRUE = FALSE"));
      }

      // use the language that works/yields most hits
      $languages=Yii::$app->utils->getLanguages();
      $useQuery=null;

      foreach ($languages as $language) {
        //Yii::debug("Trying to translate query '$clientQuery->cql' from '$language'...");
        /** @var ActiveQuery $activeQuery */
        $activeQuery = $modelClass::find();
        $schema = Datasource::in($datasourceName,"reference")::getSchema();

        $nlq = new NaturalLanguageQuery([
          'query'     => $clientQuery->cql,
          'schema'    => $schema,
          'language'  => $language
        ]);
        try {
          $nlq->injectIntoYiiQuery($activeQuery);
        } catch (\Exception $e) {
          throw new UserErrorException($e->getMessage());
        }
        try{
          if( $activeQuery->exists() ){
            $useQuery=$activeQuery;
            break;
          }
        } catch (\Exception $e){
          continue;
        }
      }
      if(!$useQuery){
        throw new UserErrorException(
          Yii::t('app',"The database could not parse the query '{query}'.",[
            'query' => $clientQuery->cql
          ])
        );
      }
      $activeQuery = $useQuery->andWhere(['markedDeleted' => 0]);
      //Yii::debug($activeQuery->createCommand()->getRawSql());
      return $activeQuery;
    }

    throw new UserErrorException(Yii::t('app', "No recognized query format in request."));
  }

  /**
   * Returns an array of ListItem data on the available reference types
   * @param $datasource
   * @return array
   * @throws UserErrorException
   */
  public function getReferenceTypeListData($datasource)
  {
    /** @var \app\schema\AbstractReferenceSchema $schema */
    $schema = $this->getControlledModel($datasource)::getSchema();
    $reftypes = $schema->types();
    $options = array();
    foreach ($reftypes as $reftype) {
      try {
        $options[] = array(
          'value' => $reftype,
          'icon' => null, //"icon/16/actions/document-new.png",
          'label' => Yii::t('app', $schema->getTypeLabel($reftype))
        );
      } catch (\Exception $e) {
        throw new UserErrorException($e->getMessage(), null, $e);
      }
    }
    return $options;
  }

  /**
   * Returns the title label for the reference editor form
   *
   * @param Reference $reference
   * @return void
   */
//  protected function getTitleLabel($reference)
//  {
//    $datasource = $reference::getDatasource();
//    $ids = [$reference->id];
//    $style = "apa"; // @todo
//    return "TODO";
//    //return CitationController :: process( $datasource, $ids, $style );
//  }

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
   * @param null|string $modelClassType
   */
  public function actionTableLayout($datasource, $modelClassType = null)
  {
    return [
      'columnLayout' => [
        'id' => [
          'header' => "ID",
          'width' => 50,
          'visible' => false
        ],
//        'markedDeleted'	=> array(	
//        	'header' 		=> " ",
//        	'width'	 		=> 16
//        ),
        'author' => [
          'header' => Yii::t('app', "Author"),
          'width' => "1*"
        ],
        'year' => [
          'header' => Yii::t('app', "Year"),
          'width' => 50
        ],
        'title' => [
          'header' => Yii::t('app', "Title"),
          'width' => "3*"
        ]
      ],
      /**
       * This will feed back into addQueryConditions()
       */
      'queryData' => [
        'relation' => [
          'name' => "folders",
          'foreignId' => 'FolderId'
        ],
        'orderBy' => "author,year,title",
      ],
      'addItems' => $this->getReferenceTypeListData($datasource)
    ];
  }

  /**
   * Returns count of rows that will be retrieved when executing the current
   * query.
   *
   * param object $queryData data to construct the query. Needs at least the
   * a string property "datasource" with the name of datasource and a property
   * "modelType" with the type of the model.
   * @throws \InvalidArgumentException
   */
  public function actionRowCount(\stdClass $clientQueryData)
  {
    $modelClass = $this->getModelClass($clientQueryData->datasource, $clientQueryData->modelType);
    $modelClass::setDatasource($clientQueryData->datasource);

    // add additional conditions from the client query
    $query = $this->transformClientQuery( $clientQueryData, $modelClass);

    //Yii::info($query->createCommand()->getRawSql());

    // count number of rows
    $rowCount = $query->count();
    return [
      "rowCount" => (int)$rowCount,
      'statusText' => Yii::t('app', "{numberOfRecords} records", ['numberOfRecords' => $rowCount])
    ];
  }

  /**
   * Returns row data executing a constructed query
   *
   * @param int $firstRow First row of queried data
   * @param int $lastRow Last row of queried data
   * @param int $requestId Request id
   * param object $queryData Data to construct the query
   * @throws \InvalidArgumentException
   * return array Array containing the keys
   *                int     requestId   The request id identifying the request (mandatory)
   *                array   rowData     The actual row data (mandatory)
   *                string  statusText  Optional text to display in a status bar
   */
  function actionRowData(int $firstRow, int $lastRow, int $requestId, \stdClass $clientQueryData)
  {
    $modelClass = $this->getModelClass($clientQueryData->datasource, $clientQueryData->modelType);
    $query = $this->transformClientQuery( $clientQueryData, $modelClass)
      ->orderBy($clientQueryData->query->orderBy)
      ->offset($firstRow)
      ->limit($lastRow - $firstRow + 1);
    //Yii::info($query->createCommand()->getRawSql());
    $rowData = $query->asArray()->all();
    return array(
      'requestId' => $requestId,
      'rowData' => $rowData,
      'statusText' => Yii::t('app', "Loaded records {firstRow} - {lastRow} ...", ['firstRow' => $firstRow, 'lastRow' => $lastRow])
    );
  }

  /**
   * Returns the form layout for the given reference type and
   * datasource
   *
   * @param $datasource
   * @param $reftype
   * @internal param \Reference $refType type
   *
   */
  function actionFormLayout($datasource, $reftype)
  {
    $modelClass = $this->getControlledModel($datasource);
    /** @var \app\schema\AbstractReferenceSchema $schema */
    $schema = $modelClass::getSchema();

    // get fields to display in the form
    $fields = array_merge(
      $schema->getDefaultFormFields(),
      $schema->getTypeFields($reftype)
    );

    // remove excluded fields
    if (Yii::$app->config->keyExists("datasource.$datasource.fields.exclude")) {
      $excludeFields = Yii::$app->config->getPreference("datasource.$datasource.fields.exclude");
      if (count($excludeFields)) {
        Yii::debug("Removing fields: " . implode(", ", $excludeFields));
        $fields = array_diff($fields, $excludeFields);
      }
    }

    // Create form
    $formData = array();
    foreach ($fields as $field) {
      $formData[$field] = $schema->getFormData($field);
      if (!$formData[$field]) {
        $formData[$field] = [
          "type" => "textfield",
        ];
      } // replace placeholders
      elseif (isset($formData[$field]['bindStore']['params'])) {
        foreach ($formData[$field]['bindStore']['params'] as $i => $param) {
          switch ($param) {
            case '$datasource':
              $formData[$field]['bindStore']['params'][$i] = $datasource;
              break;
          }
        }
      }

      // setup autocomplete data
      if (isset($formData[$field]['autocomplete'])) {
        $formData[$field]['autocomplete']['service'] = Yii::$app->controller->id;
        $formData[$field]['autocomplete']['method'] = "autocomplete";
        $formData[$field]['autocomplete']['params'] = array($datasource, $field);
      }

      // Add label
      if (!isset($formData[$field]['label'])) {
        $formData[$field]['label'] = $schema->getFieldLabel($field, $reftype);
      }
    }
    return $formData;
  }

  /**
   * Returns data for the reference type select box
   * @param $datasource
   *
   */
  public function actionReferenceTypeList($datasource)
  {
    $modelClass = $this->getControlledModel($datasource);
    /** @var \app\schema\AbstractReferenceSchema $schema */
    $schema = $modelClass::getSchema();
    $result = array();
    foreach ($schema->types() as $type) {
      try {
        $result[] = array(
          'label' => $schema->getTypeLabel($type),
          'value' => $type,
          'icon' => null
        );
      } catch (\Exception $e) {
        throw new UserErrorException($e->getMessage(),null, $e);
      }
    }
    return $result;
  }

  /**
   * Returns data for the store that populates reference type lists
   * @param $datasource
   *
   */
  function actionTypes($datasource)
  {
    return $this->getReferenceTypeListData($datasource);
  }

  /**
   * Returns the requested or all accessible properties of a reference
   * @param string $datasource
   * @param $arg2
   * @param null $arg3
   * @param null $arg4
   * @throws \InvalidArgumentException
   *
   * @todo: this method is called with different signatures!
   */
  function actionItem($datasource, $arg2, $arg3 = null, $arg4 = null)
  {
    if (is_numeric($arg2)) {
      //$type   = "reference";
      $id = $arg2;
      $fields = $arg3;
    } else {
      //$type   = $arg2;
      $id = $arg3;
      $fields = null;
    }

    if (!$datasource or !is_numeric($id)) {
      throw new \InvalidArgumentException("Invalid arguments.");
    }

    // load model record and get reference type
    $modelClass = $this->getControlledModel($datasource);
    /** @var Reference $item */
    $model = $modelClass::findOne($id);
    /** @var \app\schema\AbstractReferenceSchema $schema */
    $schema = $modelClass::getSchema();
    $reftype = $model->reftype;

    // determine the fields to return the values for
    $fields = array_merge(
      $schema->getDefaultFieldsBefore(),
      $schema->getTypeFields($reftype),
      $schema->getDefaultFieldsAfter()
    );

    // exclude fields
    // @todo create UI for this
    if (Yii::$app->config->keyExists("datasource.$datasource.fields.exclude")) {
      $excludeFields = Yii::$app->config->getPreference("datasource.$datasource.fields.exclude");
      if (count($excludeFields)) {
        $fields = array_diff($fields, $excludeFields);
      }
    }

    // prepare record data for the form
    $item = array(
      'datasource' => $datasource,
      'referenceId' => $id, // todo: replace by "id"
      //'titleLabel' => $this->getTitleLabel($model)
    );

    foreach ($fields as $field) {
      try {
        $fieldData = $schema->getFieldData($field);
      } catch (\InvalidArgumentException $e) {
        Yii::warning("No field data for field '$field'");
        continue;
      }

      // get value from model
      $value = $model->$field;

      // replace field separator with form separator if both exist
      $dataSeparator = isset($fieldData['separator'])
        ? $fieldData['separator']
        : null;
      $formSeparator = isset($fieldData['formData']['autocomplete']['separator'])
        ? $fieldData['formData']['autocomplete']['separator']
        : null;
      if ($dataSeparator and $formSeparator and $dataSeparator != $formSeparator) {
        $values = explode($dataSeparator, $value);
        foreach ($values as $i => $v) {
          $values[$i] = trim($v);
        }
        $value = implode($formSeparator, $values);
      }

      // store value
      $item[$field] = $value;
    }

    return $item;
  }

  /**
   * Returns data for the qcl.data.controller.AutoComplete
   * @param $datasource
   * @param $field
   * @param $input
   *
   */
  public function actionAutocomplete($datasource, $field, $input)
  {
    $modelClass = $this->getControlledModel($datasource);
    $fieldData = $modelClass::getSchema()->getFieldData($field);
    $separator=false;
    if (isset($fieldData['separator'])) {
      $separator = $fieldData['separator'];
    }
    $suggestionValues = $modelClass::find()
      ->select($field)
      ->distinct()
      ->where(["like", $field, $input])
      ->orderBy($field)
      ->column();

    if ($separator) {
      $suggestions = array();
      foreach ($suggestionValues as $value) {
        foreach (explode($separator, $value) as $suggestion) {
          $suggestion = trim($suggestion);
          if (strtolower($input) == strtolower(substr($suggestion, 0, strlen($input)))) {
            $suggestions[] = $suggestion;
          }
        }
      }
      $suggestionValues = array_unique($suggestions);
      sort($suggestionValues);
    }

    return array(
      'input' => $input,
      'suggestions' => $suggestionValues
    );
  }

  /**
   * Saves a value in the model
   * @param $datasource
   * @param $referenceId
   * @param $data
   * @throws \lib\exceptions\UserErrorException
   *
   */
  public function actionSave($datasource, $referenceId, $data)
  {
    // transform data into array
    $data = json_decode(json_encode($data), true);
    $modelClass = $this->getControlledModel($datasource);
    /** @var Reference $model */
    $model = $modelClass::findOne($referenceId);
    $schema = $modelClass::getSchema();

    // save user-supplied data
    foreach ($data as $property => $value) {
      // replace form separator with field separator
      $fieldData = $schema->getFieldData($property);
      $fieldSeparator = isset($fieldData['separator'])
        ? $fieldData['separator']
        : null;
      $formSeparator = isset($fieldData['formData']['autocomplete']['separator'])
        ? $fieldData['formData']['autocomplete']['separator']
        : null;
      if ($fieldSeparator && $formSeparator) {
        $value = str_replace($formSeparator, $fieldSeparator, $value);
      }
      $model->$property = $value;
    }
    // modified by
    if ($model->hasAttribute('modifiedBy')) {
      $model->modifiedBy = $this->getActiveUser()->getUsername();
    }

    // citation key
    if ( $model->hasAttribute('citekey') and !trim($model->citekey) and
      $model->creator and $model->year and $model->title) {
      $newCitekey = $model->computeCiteKey();
      $data = [
        'datasource' => $datasource,
        'modelType' => "reference",
        'modelId' => $referenceId,
        'data' => ["citekey" => $newCitekey]
      ];
      $this->broadcastClientMessage("bibliograph.fieldeditor.update", $data);
      $model->citekey = $newCitekey;
    }
    try {
      $model->save();
    } catch (Exception $e) {
      throw new UserErrorException($e->getMessage(),null, $e);
    }
    return "Updated reference #$referenceId";
  }

  /**
   * Returns distinct values for a field, sorted alphatbetically, in a format suitable
   * for a ComboBox widget.
   * @param $datasource
   * @param $field
   */
  public function actionListField($datasource, $field)
  {
    $modelClass = $this->getControlledModel($datasource);
    $values = $modelClass::find()
      ->select($field)
      ->distinct()
      ->orderBy($field)
      ->column();
    $result = [];
    foreach ($values as $value) {
      $value = trim($value);
      if ($value) {
        $result[] = array(
          'label' => $value,
          'value' => $value,
          'icon' => null
        );
      }
    }
    return $result;
  }

  /**
   * Creates a new reference
   *
   * @param string $datasource
   * @param int|string $folderId
   * @param $data
   * @return string Diagnostic message
   *
   * @throws \InvalidArgumentException
   * @throws \JsonRpc2\Exception
   * @throws Exception
   */
  public function actionCreate($datasource, $folderId, $data )
  {
    $this->requirePermission("reference.add");
    $modelClass = $this->getControlledModel($datasource);

    switch( gettype( $data )){
      case "string":
        $data = ['reftype' => $data ];
        break;
      case "object":
        $data = (array) $data;
        break;
      default:
        throw new UserErrorException("Cannot create reference: invalid data.");
    }
    $data['createdBy'] = $this->getActiveUser()->getUsername();

    // validate reference type
    if( ! isset($data['reftype'])){
      throw new UserErrorException("Missing field 'reftype'");
    }
    /** @var \app\schema\AbstractReferenceSchema $schema */
    $schema = $modelClass::getSchema();
    if( ! in_array( $data['reftype'], $schema->types()) ){
      throw new UserErrorException("Cannot create reference: invalid reference type.");
    }

    // save data
    try{
      /** @var Reference $reference */
      $reference = new $modelClass($data);
      $reference->save();
    } catch (Exception $e){
      throw new UserErrorException($e->getMessage(), null, $e);
    }

    // link with folder
    /** @var Folder $folder */
    $folder = static::getFolderModel($datasource)::findOne($folderId);
    if (!$folder) {
      throw new \InvalidArgumentException("Folder #$folderId does not exist.");
    }
    $folder->link("references", $reference);
    $folder->referenceCount = $folder->getReferences()->count();
    $folder->save();

    // reload references
    $this->dispatchClientMessage("folder.reload", array(
      'datasource' => $datasource,
      'folderId' => $folderId
    ));

    // select the new reference
    $this->dispatchClientMessage("bibliograph.setModel", array(
      'datasource' => $datasource,
      'modelType' => static::$modelType,
      'modelId' => $reference->id
    ));

    // reload model
    $reference = $modelClass::findOne($reference->id);
    return "Reference #$reference->id has been created.";
  }

  /**
   * Removes references from a folder. If the reference is not contained in any other folder,
   * move it to the trash
   * @param string $datasource The name of the datasource
   * @param int $folderId The numeric id of the folder. If zero, remove from all folders
   * @param string $ids A string of the numeric ids of the references, joined by a comma
   * @return string Diagnostic message
   * @throws \JsonRpc2\Exception
   */
  public function actionRemove(string $datasource, int $folderId, string $ids )
  {
    $this->requirePermission("reference.remove");

    if( $folderId === 0){
      throw new UserErrorException("Removing from all folders not impemented yet.");
    }

    /** @var Reference $referenceClass */
    $referenceClass = $this->getControlledModel($datasource);
    $folderClass = static::getFolderModel($datasource);
    $trashFolder = TrashController::getTrashFolder($datasource);

    // use the first id
    $ids = explode(",",$ids);
    $id = intval($ids[0]);

    // load record and count the number of links to folders
    $reference = $referenceClass::findOne($id);
    if( !$reference ){
      throw new UserErrorException("Reference #$id does not exist.");
    }
    $containedFolderIds = $reference->getReferenceFolders()->select("FolderId")->column();
    $folderCount = count($containedFolderIds);

    // unlink
    /** @var Folder $folder */
    $folder = $folderClass::findOne(intval($folderId));
    $reference->unlink("folders", $folder, true);
    // update folders
    $foldersToUpdate = $containedFolderIds;

    // move to trash if it was contained in one or less folders
    if ($folderCount < 2) {
      if ($trashFolder) {
        if( $folder->id === $trashFolder->id ){
          // reference is already in the trash, delete
          try {
            $reference->delete();
          } catch (\Throwable $e) {
            Yii::error($e);
          }
        } else {
          // link with trash folder
          try{
            $trashFolder->link("references", $reference);
          } catch (Exception $e) {
            Yii::error($e);
          }
          // mark as deleted
          $reference->markedDeleted = 1;
          try {
            $reference->save();
          } catch (Exception $e) {
            Yii::error($e->getMessage());
          }
          $foldersToUpdate[] = $trashFolder->id;
        }
      }
    }

    // update reference count in source and target folders
    $foldersToUpdate = array_unique($foldersToUpdate);
    foreach ($foldersToUpdate as $fid) {
      /** @var Folder $folder */
      $folder = $folderClass::findOne($fid);
      if ($folder) {
        try {
          $folder->getReferenceCount(true);
        } catch (Exception $e) {
          Yii::error($e);
        }
      } else {
        Yii::warning("Folder #$fid does not exist.");
      }
    }

    // display change on connected clients
    foreach ($containedFolderIds as $fid) {
      $this->broadcastClientMessage("reference.removeRows", array(
        'datasource' => $datasource,
        'folderId' => $fid,
        'query' => null,
        'ids' => array($id)
      ));
    }
    // if there are references left, repeat
    if (count($ids) > 1) {
      array_shift($ids);
      return $this->actionRemove($datasource, $folderId, implode(",",$ids) );
    }
    return "Removed references.";
  }

  /**
   * Confirm that a reference should be moved to the trash folder
   * @param string $datasource
   * @param int $folderId
   * @param string $ids
   * @return string Diagnostic message
   * @throws \JsonRpc2\Exception
   */
  public function actionConfirmMoveToTrash($confirmed, string $datasource, int $folderId, $ids )
  {
    if( ! $confirmed ) return "Remove action was cancelled.";
    $this->confirm = true;
    return $this->actionRemove($datasource, $folderId, $ids );
  }

  /**
   * Move references from one folder to another folder
   *
   * @param string $datasource If true, it is the result of the confirmation
   * @param int $folderId The folder to move from
   * @param int $targetFolderId The folder to move to
   * @param string $ids The ids of the references to move, joined by  a comma
   * @return string Diagnostic message
   * @throws \JsonRpc2\Exception
   */
  public function actionMove(string $datasource, int $folderId, int $targetFolderId, string $ids)
  {
    $this->requirePermission("reference.move");

    $referenceClass = $this->getControlledModel($datasource);
    $folderClass = static:: getFolderModel($datasource);
    /** @var Folder $sourceFolder */
    $sourceFolder = $folderClass::findOne($folderId);
    /** @var Folder $targetFolder */
    $targetFolder = $folderClass::findOne($targetFolderId);

    try {
      Validate::isNotNull($targetFolder, "Folder #$targetFolderId does not exist");
      Validate::isNotNull($sourceFolder, "Folder #$folderId does not exist");
    } catch (\Exception $e) {
      throw new UserErrorException($e->getMessage());
    }
    /** @var ActiveQuery $query */
    $query = $referenceClass::find()->where(['in', 'id', explode(",",$ids)]);
    Yii::info($query->createCommand()->getRawSql());
    $references = $query->all();
    return $this->move($references, $datasource, $sourceFolder, $targetFolder);
  }

  /**
   * Move reference from one folder to another folder
   *
   * @param Reference[]|int[] $references either an array of reference
   *    objects or an array of the ids of that object
   * @param string $datasource
   * @param Folder $sourceFolder
   * @param Folder $targetFolder
   * @return string "OK"
   */
  public function move(
    array $references,
    string $datasource,
    Folder $sourceFolder,
    Folder $targetFolder )
  {
    $ids = [];
    foreach ($references as $reference) {
      if (is_numeric($reference)) {
        $ids[] = $reference;
        $reference = $this->getRecordById($datasource, $reference);
      } elseif($reference instanceof Reference)  {
        $ids[] = $reference->id;
      } else {
        throw new \InvalidArgumentException("Invalid reference '$reference'");
      }
      // unlink source folder
      try{
        $sourceFolder->unlink("references", $reference, true);
      } catch (Exception $e){
        Yii::error($e);
      }
      // link target folder
      try{
        $targetFolder->link("references", $reference);
      } catch (Exception $e){
        Yii::error($e);
      }
    }

    // update reference count
    try {
      $sourceFolder->getReferenceCount(true);
      $targetFolder->getReferenceCount(true);
    } catch (Exception $e) {
      Yii::error($e);
    }

    // display change on connected clients

    if (count($ids)) {
      $this->broadcastClientMessage("reference.removeRows", [
        'datasource' => $datasource,
        'folderId' => $sourceFolder->id,
        'query' => null,
        'ids' => $ids
      ]);
    }
    $count = count($ids);
    return "Moved $count references from '{$sourceFolder->label}' to '{$targetFolder->label}'.";
  }

  /**
   * Copies a reference to a folder
   *
   * @param string $datasource
   * @param int $targetFolderId
   * @param string $ids Numeric ids joined by comma
   * @return string "OK"
   * @throws \JsonRpc2\Exception
   */
  public function actionCopy(string $datasource, int $targetFolderId, string $ids)
  {
    $this->requirePermission("reference.move");
    $folderModel = static::getFolderModel($datasource);
    $targetFolder = $folderModel::findOne($targetFolderId);

    try {
      Validate:: isNotNull($targetFolder, "Folder #$targetFolderId does not exist");
    } catch (\Exception $e) {
      throw new UserErrorException($e->getMessage());
    }
    return $this->copy($datasource, $targetFolder, explode(",",$ids));
  }

  /**
   * Copy reference from one folder to another folder
   * @param string $datasource
   * @param Folder $targetFolder
   * @param Reference[]|int[] $references either an array of reference
   *    objects or an array of the ids of that object
   * @return string Diagnostic message
   */
  public function copy( string $datasource, Folder $targetFolder, array $references)
  {
    foreach ($references as $reference) {
      if (is_numeric($reference)) {
        $reference = $this->getControlledModel($datasource)::findOne($reference);
      }
      if (!($reference instanceof Reference)) {
        Yii::warning("Skipping invalid reference '$reference'");
      }
      try{
        $targetFolder->link("references", $reference);
      } catch (yii\db\IntegrityException $e ){
        throw new UserErrorException(
          Yii::t('app', "The reference is already contained in that folder.")
        );
      }

    }

    // update reference count
    try {
      $targetFolder->getReferenceCount(true);
    } catch (Exception $e) {
      Yii::error($e);
    }
    $count = count($references);
    return "Copied $count references to '{$targetFolder->label}'.";
  }

  /**
   * Removes all references from a folder
   *
   * @param string $datasource
   * @param int $folderId
   * @throws \JsonRpc2\Exception
   */
  public function actionEmptyFolder($datasource, $folderId)
  {
    $this->requirePermission("reference.batchedit");

    $folderModel = static::getFolderModel($datasource);
    /** @var Folder $folder */
    $folder = $folderModel::findOne($folderId);
    /** @var Reference[] $references */
    $references = $folder->getReferences()->all();

    $foldersToUpdate = [$folderId];
    $referencesToTrash = [];

    foreach ($references as $reference) {
      $folderCount = $reference->getFolders()->count();
      $reference->unlink("folders", $folder, true);
      if ($folderCount == 1) {
        $referencesToTrash[] = $reference;
      }
    }
    if (count($referencesToTrash)) {
      $trashFolder = TrashController::getTrashFolder($datasource);
      if ($trashFolder) {
        foreach ($referencesToTrash as $reference) {
          $trashFolder->link("references", $reference);
        }
      }
    }
    foreach ($foldersToUpdate as $fid) {
      /** @var Folder $folder */
      $folder = $folderModel::findOne($fid);
      if (!$folder) {
        Yii:: warning("Folder #$fid does not exist");
      }
      try {
        $folder->getReferenceCount(true);
      } catch (Exception $e) {
        Yii::error($e);
      }
      $this->broadcastClientMessage("folder.reload", array(
        'datasource' => $datasource,
        'folderId' => $fid
      ));
    }
    return "Removed all references from folder #$folderId in $datasource";
  }

  /**
   * Returns information on the record as a HTML table
   * @param $datasource
   * @param $referenceId
   */
  public function actionTableHtml($datasource, $referenceId)
  {
    $referenceModel = $this->getControlledModel($datasource);
    $reference = $referenceModel::findOne($referenceId);
    Validate:: isNotNull($reference, "Reference #$referenceId does not exist.");

    $createdBy = $reference->createdBy;
    if ($createdBy) {
      $user = User::findOne(['namedId' => $createdBy]);
      if ($user) $createdBy = $user->name;
    }
    $modifiedBy = $reference->modifiedBy;
    if ($modifiedBy) {
      $user = User:: findOne(['namedId' => $createdBy]);
      if ($user) $modifiedBy = $user->name;
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
      'html' => $html
    );
  }

  /**
   * Returns a HTML table with the reference data
   * @param $datasource
   * @param $id
   */
  public function actionItemHtml($datasource, $id)
  {
    $modelClass = $this->getControlledModel($datasource);
    /** @var \app\schema\AbstractReferenceSchema $schema */
    $schema = $modelClass::getSchema();
    /** @var Reference $reference */
    $reference = $modelClass::findOne($id);
    $reftype = $reference->reftype;

    $fields = array_merge(
      array("reftype"),
      $schema->getTypeFields($reftype),
      array("keywords", "abstract")
    );

    // create html table
    $html = "<table>";
    foreach ($fields as $field) {
      $value = $reference->$field;
      if (!$value or !$schema->isPublicField($field)) continue;
      $label = $modelClass::getSchema()->getFieldLabel($field, $reftype);

      // special fields
      switch ($field) {
        case "reftype":
          $value = $schema->getTypeLabel($value);
          break;
        case "url":
          $urls = explode(";", $value);
          $value = implode("<br/>", array_map(function ($url) {
            return "<a href='$url' target='_blank'>$url</a>";
          }, $urls));
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
   */
  public function actionContainers($datasource, $referenceId)
  {
    $reference = $this->getRecordById($datasource, $referenceId);
    $folders = $reference->getFolders()->all();
    $data = array();
    foreach ($folders as $folder) {
      $data[] = [
        $folder->id,
        $folder->getIcon("default"),
        $folder->getLabelPath("/")
      ];
    }
    return $data;
  }


  /**
   * Returns potential duplicates in a simple data model format.
   * @param string $datasource
   * @param int $referenceId
   *
   */
  function actionDuplicatesData($datasource, $referenceId)
  {
    // @todo
    return [];

    $reference = $this->getRecordById($referenceId);
    $threshold = Yii::$app->config->getPreference("bibliograph.duplicates.threshold");
    $scores = $reference->findPotentialDuplicates($threshold);
    $data = array();
    while ($referenceModel->loadNext()) {
      $score = round(array_shift($scores));
      if ($referenceModel->id() == $referenceId or
        $referenceModel->get("markedDeleted")) {
        continue;
      }
      $reftype = $referenceModel->getReftype();
      $data[] = array(
        $referenceModel->id(),
        $reftype ? $referenceModel::getSchema()->getTypeLabel($reftype) : "",
        $referenceModel->getAuthor(),
        $referenceModel->getYear(),
        $referenceModel->getTitle(),
        $score
      );
    }
    return $data;
  }
}
