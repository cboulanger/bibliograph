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

use app\models\Datasource;
use function GuzzleHttp\debug_resource;
use lib\Exception;
use lib\exceptions\UserErrorException;
use lib\schema\cql\Query;
use lib\Validate;
use Yii;

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
  static $modelType = "reference";

  /**
   * Icons for the folder nodes, depending on type
   * @var array
   */
  static $icon = array(
    "favorites" => "icon/16/actions/help-about.png"
  );

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
   *   'datasource' : "<datasource name>",
   *   'modelType' : "reference",
   *   'query' : {
   *     'properties' : ["author","title","year" ...],
   *     'orderBy' : "author",
   *     'relation' : {
   *       'name' : "folder",
   *       'id' : 3
   *     }
   *     'cql' :
   *   }
   * }
   * @param stdClass $clientQueryData
   *    The query data object from the json-rpc request
   * @param \yii\db\ActiveQuery $serverQuery
   *    The ActiveQuery object used
   * @return \yii\db\ActiveQuery
   * @throws \InvalidArgumentException
   */
  public static function addQueryConditions( \stdClass $clientQueryData, \yii\db\ActiveQuery $serverQuery)
  {
    $clientQuery = $clientQueryData->query;
    if (!is_object($clientQuery) or
      !is_array($clientQuery->properties)) {
      throw new \InvalidArgumentException("Invalid query data");
    }

    // @todo should be 'columns' or 'fields'
    $columns = array_map(function ($column) {
      return $column == "id" ? "references.id" : $column;
    }, $clientQuery->properties);
    $serverQuery = $serverQuery->select($columns);

    // relation
    if (isset($clientQuery->relation)) {
      return $serverQuery
        ->alias('references')
        ->joinWith($clientQuery->relation->name,false)
        ->onCondition([$clientQuery->relation->foreignId => $clientQuery->relation->id]);
    }

    // cql
    if (false /* not implemented */) { // isset ( $query->cql ) ){

      $cql = new Query;
      try {
        $q = $cql->addQueryConditions($clientQuery, $serverQuery, $modelClass);
      } catch (\Exception $e) {
        throw new UserErrorException($e->getMessage());
      }
      $q->where['markedDeleted'] = false;
      return $q;
    }

    throw new \Exception(Yii::t('app', "No recognized query format in request."));
  }

  /**
   * Returns an array of ListItem data on the available reference types
   * @param $datasource
   * @return array
   */
  public function getReferenceTypeListData($datasource)
  {
    /** @var \app\schema\AbstractReferenceSchema $schema */
    $schema = $this->getControlledModel($datasource)::getSchema();
    $reftypes = $schema->types();
    $options = array();
    foreach ($reftypes as $reftype) {
      $options[] = array(
        'value' => $reftype,
        'icon' => null, //"icon/16/actions/document-new.png",
        'label' => Yii::t('app', $schema->getTypeLabel($reftype))
      );
    }
    return $options;
  }

  /**
   * Returns the title label for the reference editor form
   *
   * @param \app\models\Reference $reference
   * @return void
   */
  protected function getTitleLabel($reference)
  {
    $datasource = $reference::getDatasource();
    $ids = [$reference->id];
    $style = "apa"; // @todo
    return "TODO";
    //return CitationController :: process( $datasource, $ids, $style );
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
      'addItems' => $this->getReferenceTypeListData($datasource, $modelClassType)
    ];
  }

  /**
   * Returns count of rows that will be retrieved when executing the current
   * query.
   *
   * param array $queryData data to construct the query. Needs at least the
   * a string property "datasource" with the name of datasource and a property
   * "modelType" with the type of the model.
   * @throws \InvalidArgumentException
   */
  public function actionRowCount($clientQueryData)
  {
    // @todo why pass the model type? It's implied in the controller name
    $modelClass = $this->getModelClass($clientQueryData->datasource, $clientQueryData->modelType);
    $modelClass::setDatasource($clientQueryData->datasource);
    $query = $modelClass::find();

    // add additional conditions from the client query
    $query = $this->addQueryConditions( $clientQueryData, $query);

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
   * @throws InvalidArgumentException
   * return array Array containing the keys
   *                int     requestId   The request id identifying the request (mandatory)
   *                array   rowData     The actual row data (mandatory)
   *                string  statusText  Optional text to display in a status bar
   */
  function actionRowData($firstRow, $lastRow, $requestId, $clientQueryData)
  {
    $model = $this->getModelClass($clientQueryData->datasource, $clientQueryData->modelType);
    $query = $model:: find()
      ->orderBy($clientQueryData->query->orderBy)
      ->offset($firstRow)
      ->limit($lastRow - $firstRow + 1);
    $query = $this->addQueryConditions( $clientQueryData, $query);
    //return $query->createCommand()->getRawSql();
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
        $formData[$field]['autocomplete']['method'] = "getAutoCompleteData";
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
      $result[] = array(
        'label' => $schema->getTypeLabel($type),
        'value' => $type,
        'icon' => null
      );
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
    /** @var \app\models\Reference $item */
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
      'titleLabel' => $this->getTitleLabel($item)
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
    $separator = $fieldData['separator'];
    $suggestionValues = $modelClass:: select($field)
      ->where(["like", $field, $input])
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
    } else {
      $suggestionValues = $modelClass->getQueryBehavior()->fetchValues($field, array(
        $field => array("LIKE", "$input%")
      ));
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

    // add metadata
    $modelClass = $this->getControlledModel($datasource);
    $record = $modelClass::findOne($referenceId);

    // modified by
    if ($modelClass::hasAttribute('modifiedBy')) {
      $record->modifiedBy = $this->getActiveUser()->getUsername();
      $record->save();
    }

    // citation key
    if (!trim($record->citekey) and
      $record->creator and $record->year and $record->title) {
      $newCitekey = $record->computeCiteKey();
      $data = array(
        'datasource' => $datasource,
        'modelType' => "reference",
        'modelId' => $referenceId,
        'data' => array("citekey" => $newCitekey)
      );
      $this->broadcastClientMessage("bibliograph.fieldeditor.update", $data);
      $record->citekey = $newCitekey;
      $record->save();
    }
    return "OK";
  }

  /**
   * Returns data for a ComboBox widget.
   * @param $datasource
   * @param $field
   *
   */
  public function actionListField($datasource, $field)
  {
    $values = static
      :: getControlledModel($datasource)
      :: select($field)
      ->column();

    $result = array();
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
   * @throws \yii\db\Exception
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
      /** @var \app\models\Reference $reference */
      $reference = new $modelClass($data);
      $reference->save();
    } catch (\yii\db\Exception $e){
      throw new UserErrorException($e->getMessage(), null, $e);
    }

    // link with folder
    /** @var \app\models\Folder $folder */
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
   * @throws InvalidArgumentException
   */
  public function actionRemove($first, $second = null, $third = null, $ids = null)
  {
    // removal cancelled
    if ($first === false) {
      return "CANCELLED";
    } // removal confirmed
    elseif ($first === true and is_string($second)) {
      $confirmRemove = true;
      list($datasource, $folderId, $ids) = $this->unshelve($second);
    } // API signature
    elseif (is_string($first) and is_array($ids)) {
      $confirmRemove = false;
      $datasource = $first;
      $folderId = $second;
    } // wrong parameters
    else {
      throw new \InvalidArgumentException("Invalid arguments for bibliograph.reference.removeReferences");
    }

    // folderId vs query
    $query = null;
    if (!is_integer($folderId)) {
      $query = $folderId;
      $folderId = null;
    }

    $this->requirePermission("reference.remove");

    /** @var \app\models\Reference $referenceClass */
    $referenceClass = $this->getControlledModel($datasource);
    $folderClass = static::getFolderModel($datasource);

    /** @var \app\models\Folder $folder */
    $folder = $folderClass::findOne($folderId);

    //$this->debug( array($datasource, $folderId, $ids) );

    // use the first id
    $id = intval($ids[0]);

    // load record and count the number of links to folders
    $reference = $referenceClass::findOne($id);
    $containedFolderIds = $reference->getReferenceFolders()->select("id")->column();
    $folderCount = count($containedFolderIds);

    // if we have no folder id and more than one folders contain the reference,
    // we need to ask the user first
    if (!$folderId) {
      if ($folderCount > 1 and !$confirmRemove) {
        return \lib\dialog\Confirm::create(
          Yii::t('app',
            "The selected record '%s' is contained in %s folders. Move to the trash anyways?",
            ($reference->title . " (" . $reference->year . ")"),
            $folderCount
          ),
          null,
          "reference", "remove",
          array($this->shelve($datasource, $query, $ids))
        );
      } // confirmed
      else {
        $referenceClass->unlinkAll("folder");
        $folderCount = 0;
      }
    } // unlink from folder if id is given.
    else {
      $reference->unlink("folder", $folder);
    }

    $foldersToUpdate = $containedFolderIds;

    // move to trash only if it was contained in one or less folders
    if ($folderCount < 2) {
      // link with trash folder
      $trashFolder = TrashController::getTrashFolder();
      if ($trashFolder) $trashFolder->link("references", $reference);

      $foldersToUpdate[] = $trashFolder->id;

      // mark as deleted
      $reference->markedDeleted = 1;
      $reference->save();
    }

    // update reference count in source and target folders
    $foldersToUpdate = array_unique($foldersToUpdate);
    foreach ($foldersToUpdate as $fid) {
      $folder = $folderClass::findOne($fid);
      if ($folder) {
        $folder->getReferenceCount(true);
      } else {
        Yii::warning("Folder #$fid does not exist.");
      }
    }

    /*
     * display change on connected clients
     */
    foreach ($containedFolderIds as $fid) {
      $this->broadcastClientMessage("reference.removeRows", array(
        'datasource' => $datasource,
        'folderId' => $fid,
        'query' => null,
        'ids' => array($id)
      ));
    }
    if ($query) {
      $this->broadcastClientMessage("reference.removeRows", array(
        'datasource' => $datasource,
        'folderId' => null,
        'query' => $query,
        'ids' => array($id)
      ));
    }

    /*
     * if there are references left, repeat
     */
    if (count($ids) > 1) {
      array_shift($ids);
      return $this->actionRemove($datasource, $folderId, null, $ids);
    }
    return "OK";
  }

  /**
   * Removes all references from a folder
   *
   * @param strin $datasource
   * @param int $folderId
   */
  public function actionFolderRemove($datasource, $folderId)
  {
    $this->requirePermission("reference.batchedit");

    $referenceModel = $this->getControlledModel($datasource);
    $folderModel = static::getFolderModel($datasource);
    $folder = $folderModel:: findOne($folderId);
    $references = $folder->getReferences()->all();

    $foldersToUpdate = [$folderId];
    $referencesToTrash = [];
    foreach ($references as $reference) {
      $folderCount = $reference->getFolders()->count();
      $referenceModel->unlink("folder", $folder);
      if ($folderCount == 1) {
        $referencesToTrash[] = $reference;
      }
    }
    if (count($referencesToTrash)) {
      $trashFolder = TrashController::getTrashFolder();
      if ($trashFolder) {
        foreach ($referencesToTrash as $reference) {
          $trashFolder->link("references", $reference);
        }
      }
    }
    foreach ($foldersToUpdate as $fid) {
      $folder = $folderModel:: findOne($fid);
      if (!$folder) {
        Yii:: warning("Folder #$fid does not exist");
      }
      $folder->getReferenceCount(true);
      $this->broadcastClientMessage("folder.reload", array(
        'datasource' => $datasource,
        'folderId' => $fid
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
   * @throws \JsonRpc2\Exception
   */
  public function actionMove($datasource, $folderId, $targetFolderId, $ids)
  {
    $this->requirePermission("reference.move");

    if ($datasource === true) {
      list($confirmed, $datasource, $folderId, $targetFolderId, $ids) = func_get_args();
    } else {
      $confirmed = false;
    }

    $referenceClass = $this->getControlledModel($datasource);
    $folderClass = static:: getFolderModel($datasource);
    /** @var \app\models\Folder $sourceFolder */
    $sourceFolder = $folderClass::findOne($folderId);
    /** @var \app\models\Folder $targetFolder */
    $targetFolder = $folderClass::findOne($targetFolderId);

    try {
      Validate:: isNotNull($targetFolder, "Folder #$targetFolderId does not exist");
      Validate:: isNotNull($sourceFolder, "Folder #$folderId does not exist");
    } catch (\Exception $e) {
      throw new UserErrorException($e->getMessage());
    }

    if (!$confirmed) {
      return \lib\dialog\Confirm::create(
        Yii::t('app', "This will move {countReferences} from '{sourceFolder}' to '{targetFolder}'. Proceed?", [
          'countReferences' => count($ids),
          'sourceFolder' => $sourceFolder->label,
          'targetFolder' => $targetFolder->label
        ]),
        null,
        "reference", "move", func_get_args()
      );
    } else {
      $references = $referenceClass::find()->where(['in', 'id', $ids])->all();
      return $this->move($references, $datasource, $sourceFolder, $targetFolder);
    }
  }

  /**
   * Move reference from one folder to another folder
   *
   * @param \app\models\Reference[]|int[] $references either an array of reference
   *    objects or an array of the ids of that object
   *
   * @param \app\models\Folder $sourceFolder
   * @param \app\models\Folder $targetFolder
   * @return string "OK"
   */
  public function move(
    array $references,
    $datasource,
    \app\models\Folder $sourceFolder,
    \app\models\Folder $targetFolder
  )
  {
    $ids = [];
    foreach ($references as $reference) {
      if (is_numeric($reference)) {
        $reference = $this->getRecordById($datasource, $reference);
        $ids[] = $reference;
      } else {
        $ids[] = $reference->id;
      }
      if (!($reference instanceof \app\models\Reference)) {
        Yii::warning("Skipping invalid reference '$reference'");
      }
      $sourceFolder->unlink("references", $reference);
      $targetFolder->link("references", $reference);
    }

    // update reference count
    $sourceFolder->getReferenceCount(true);
    $targetFolder->getReferenceCount(true);

    // display change on connected clients

    if (count($ids)) {
      $this->broadcastClientMessage("reference.removeRows", [
        'datasource' => $datasource,
        'folderId' => $sourceFolder->id,
        'query' => null,
        'ids' => $ids
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
   * @throws \JsonRpc2\Exception
   */
  public function actionCopy($datasource, $folderId, $targetFolderId, $ids)
  {
    $this->requirePermission("reference.move");
    if ($datasource === true) {
      list($confirmed, $datasource, $folderId, $targetFolderId, $ids) = func_get_args();
    } else {
      $confirmed = false;
    }
    $folderModel = static:: getFolderModel($datasource);
    $sourceFolder = $folderModel:: findOne($folderId);
    $targetFolder = $folderModel:: findOne($targetFolderId);

    try {
      Validate:: isNotNull($sourceFolder, "Folder #$folderId does not exist");
      Validate:: isNotNull($targetFolder, "Folder #$targetFolderId does not exist");
    } catch (\Exception $e) {
      throw new UserErrorException($e->getMessage());
    }

    if (!$confirmed) {
      return \lib\dialog\Confirm::create(
        Yii::t('app', "This will copy {countReferences} from '{sourceFolder}' to '{targetFolder}'. Proceed?", [
          'countReferences' => count($ids),
          'sourceFolder' => $sourceFolder->label,
          'targetFolder' => $targetFolder->label
        ]),
        null,
        "reference", "copy", func_get_args()
      );
    } else {
      return $this->copy($ids, $sourceFolder, $targetFolder);
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
  )
  {
    foreach ($references as $reference) {
      if (is_numeric($reference)) {
        $reference = $this->getControlledModel()->findOne($reference);
      }
      if (!($reference instanceof \app\models\Reference)) {
        Yii::warning("Skipping invalid reference '$reference'");
      }
      $targetFolder->link("references", $reference);
    }

    // update reference count
    $targetFolder->getReferenceCount(true);
    return "OK";
  }

  /**
   * Returns information on the record as a HTML table
   * @param $datasource
   * @param $referenceId
   */
  public function actionTableHtml($datasource, $referenceId)
  {
    $referenceModel = $this->getControlledModel($datasource);
    $reference = $referenceModel:: findOne($referenceId);
    Validate:: isNotNull($reference, "Reference #$referenceId does not exist.");

    $createdBy = $reference->createdBy;
    if ($createdBy) {
      $user = User:: findOne(['namedId' => $createdBy]);
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
    /** @var \app\models\Reference $reference */
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
