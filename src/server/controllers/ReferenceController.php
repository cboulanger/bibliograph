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
use app\models\ExportFormat;
use app\models\Folder;
use app\models\Reference;
use app\models\User;
use app\schema\BibtexSchema;
use lib\cql\NaturalLanguageQuery;
use lib\dialog\Confirm;
use lib\exceptions\RecordExistsException;
use lib\exceptions\UserErrorException;
use lib\Validate;
use Yii;
use yii\db\ActiveQuery;
use yii\db\Exception;

class ReferenceController extends AppController
{
  use traits\FolderDataTrait;
  use traits\TableTrait;

  /**
   * Used by confirmation actions
   * @var bool
   */
  protected $confirmed = false;

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
        'creator' => [
          'header' => Yii::t('app', "Creator"),
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
       * @todo implement differently
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
        $value = str_replace($formSeparator, $fieldSeparator, trim($value));
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
   * @throws \lib\exceptions\Exception
   * @throws Exception
   */
  public function actionCreate($datasource, $folderId, $data )
  {
    $this->requirePermission("reference.add", $datasource);
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
   * @throws \lib\exceptions\Exception
   */
  public function actionRemove(string $datasource, int $folderId, string $ids )
  {
    $this->requirePermission("reference.remove", $datasource);

    if( $folderId === 0 and $this->confirmed !== "all" ){
      Confirm::create(
        Yii::t(self::CATEGORY, "Do you really want to move all copies of the reference(s) to the trash?"),
        null,
        "reference", "confirm-move-to-trash",
        [$datasource, $ids]
      );
      return "Created confirmation dialog.";
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
    $foldersToUpdate = $containedFolderIds;
    $folderCount = count($containedFolderIds);

    /** @var Folder $folder */
    if( $folderId === 0 and $this->confirmed==="all" ){
      // unlink all folders
      foreach ($containedFolderIds as $fid) {
        $folder = $folderClass::findOne(intval($fid));
        $reference->unlink("folders", $folder, true);
        $folderCount--;
      }
    } else {
      // unlink only the current one
      $folder = $folderClass::findOne(intval($folderId));
      $reference->unlink("folders", $folder, true);
    }

    // move to trash if it was contained in one or less folders
    if ( $trashFolder and $folderCount < 2) {
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
        'folderId' => intval($fid),
        'query' => null,
        'ids' => [intval($id)]
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
   * @throws \lib\exceptions\Exception
   */
  public function actionConfirmMoveToTrash($confirmed, string $datasource, $ids )
  {
    if( ! $confirmed ) return "Remove action was cancelled.";
    $this->confirmed = "all";
    return $this->actionRemove($datasource, 0, $ids );
  }

  /**
   * Move references from one folder to another folder
   *
   * @param string $datasource If true, it is the result of the confirmation
   * @param int $folderId The folder to move from
   * @param int $targetFolderId The folder to move to
   * @param string $ids The ids of the references to move, joined by  a comma
   * @return string Diagnostic message
   * @throws \lib\exceptions\Exception
   */
  public function actionMove(string $datasource, int $folderId, int $targetFolderId, string $ids)
  {
    $this->requirePermission("reference.move", $datasource);

    $referenceClass = $this->getControlledModel($datasource);
    $folderClass = static:: getFolderModel($datasource);
    /** @var Folder $sourceFolder */
    $sourceFolder = $folderClass::findOne($folderId);
    /** @var Folder $targetFolder */
    $targetFolder = $folderClass::findOne($targetFolderId);

    $trashFolder = TrashController::getTrashFolder($datasource);
    if( $trashFolder and $targetFolder->id === $trashFolder->id){
      return $this->actionRemove( $datasource, 0, $ids );
    }

    try {
      Validate::isNotNull($targetFolder, "Folder #$targetFolderId does not exist");
      Validate::isNotNull($sourceFolder, "Folder #$folderId does not exist");
    } catch (\Exception $e) {
      throw new UserErrorException($e->getMessage());
    }
    /** @var ActiveQuery $query */
    $query = $referenceClass::find()->where(['in', 'id', explode(",",$ids)]);
    $references = $query->all();

    try {
      return $this->move($references, $datasource, $sourceFolder, $targetFolder);
    } catch (RecordExistsException $e) {
      throw new UserErrorException($e->getMessage());
    }
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
   * @throws RecordExistsException
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

      if( $sourceFolder->id === $targetFolder->id ){
        throw new RecordExistsException(
          Yii::t('app',"At least one record already exists in folder {name}.",[
            'name' => $targetFolder->label
          ])
        );
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
   * @throws \lib\exceptions\Exception
   */
  public function actionCopy(string $datasource, int $targetFolderId, string $ids)
  {
    $this->requirePermission("reference.move", $datasource);
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
   * @throws \lib\exceptions\Exception
   */
  public function actionEmptyFolder($datasource, $folderId)
  {
    $this->requirePermission("reference.batchedit", $datasource);

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
   *
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

    // shcow export formats
    $formats = ExportFormat::find()
      ->select('namedId,name')
      ->where(['type'=>'export'])
      ->orderBy('name')
      ->all();
    $links = [];
    /** @var ExportFormat $format */
    foreach ($formats as $format) {
      $url = Yii::$app->homeUrl .
        'converters/download' .
        '?access-token=' . Yii::$app->user->getIdentity()->getAuthKey() .
        '&format=' . $format->namedId .
        '&datasource=' . $datasource .
        '&selector=' . $id;
      $links[] = "<a href=\"$url\" target=\"_blank\" data-id=\"{$format->name}\">{$format->name}</a>";
    }
    $html .= "<p>" . Yii::t('app','Export citation as ') . implode(" | ", $links ) . "</p>";

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

  /**
   * @param $input
   * @param $inputPosition
   * @param string[] $tokens
   * @param $datasourceName
   * @todo rename
   * return TokenFieldDto[]
   */
  public function actionTokenizeQuery( $input, $inputPosition, $tokens, $datasourceName ){
    //Yii::debug(func_get_args());
    $debug = false;
    $input = trim($input);
    $modelClass = Datasource::in($datasourceName,"reference");
    $tokens[] = $input;
    $query = implode(" ", $tokens);
    /** @var BibtexSchema $schema */
    $schema = $modelClass::getSchema();
    $nlq = new NaturalLanguageQuery([
      'query'     => $query,
      'schema'    => $schema,
      'language'  => Yii::$app->language,
      'verbose'   => false
    ]);
    $translatedQuery = $nlq->translate();
    if ($debug) Yii::debug("User search query '$query' was translated to '$translatedQuery'.", __METHOD__);
    $matches=[];
    switch ($inputPosition){
      case 0:
        // the first token is either a field name (or a search expression)
        $matches = array_filter($schema->getIndexNames(), function($index) use($input,$schema) {
          foreach ($schema->getIndexFields($index) as $indexField) {
            if( Yii::$app->user->getIdentity()->isAnonymous()  && ! $schema->isPublicField($indexField) ) return false;
          }
          return $input === "?" or str_contains( mb_strtolower($index, 'UTF-8'),  mb_strtolower($input, 'UTF-8') );
        });
        break;
      case 1:
        // the second token is an operator (or a search expression)
        $translatedOpterators = array_map(function ($item) use ($nlq) {
          return trim(str_replace( ['{field}','{value}','{leftOperand}','{rightOperand}'],'', $nlq->getOperatorData($item)['translation']));
        },$nlq->getOperators());
        sort($translatedOpterators);
        $matches = array_filter($translatedOpterators, function($item) use($input) {
          return $input === "?" or str_contains( mb_strtolower($item, 'UTF-8'),  mb_strtolower($input, 'UTF-8' ) );
        });
        break;
      case 2:
        if( count($nlq->parsedTokens) ===  0) break;
        // if the first token is field, return index entries
        $field = array_first($schema->fields(), function($field) use($nlq) {
          return $field === $nlq->parsedTokens[0];
        });
        $operator = array_first($nlq->getOperators(), function($operator) use($nlq) {
          return isset($nlq->parsedTokens[1]) && $operator === $nlq->parsedTokens[1];
        });
        if( $field ){
          $separator=false;
          $fieldData = $schema->getFieldData($field);
          if (isset($fieldData['separator'])) {
            $separator = $fieldData['separator'];
          }
          $query = $this->findIn($datasourceName,"reference")
            ->select($field)
            ->distinct()
            ->orderBy($field)
            ->limit(50);
          if( ! $input or $input !== "?" ){
            $query = $query->where( "$field like '%$input%'");
          }
          $matches = $query->column();
          if ($separator) {
            $m = [];
            foreach ($matches as $value) {
              foreach (explode($separator, $value) as $suggestion) {
                $suggestion = trim($suggestion);
                if (strtolower($input) == strtolower(substr($suggestion, 0, strlen($input)))) {
                  $m[] = $suggestion;
                }
              }
            }
            $matches = $m;
          }
          // enclose in quotes
          $matches = array_map(function($item){
            // trim the item
            $item= trim(substr($item,0,50));
            return str_contains($item, " ")
              ? "\"$item\""
              : $item;
          }, $matches);
        }
        break;
    }
    if( count($matches)===0){
      $matches = [$input];
    }
    $matches = array_filter($matches, function ($item){ return !empty($item);});
    $matches = array_unique($matches);
    sort($matches);
    $list = [];
    foreach ($matches as $match) {
      $list[] = [ 'token' => $match ];
    }
    return $list;
  }
}

class TokenFieldDto
{
  /**
   * @var string
   */
  public $token = "";
}
