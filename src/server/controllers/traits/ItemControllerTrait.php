<?php

namespace app\controllers\traits;

use app\models\ExportFormat;
use app\models\Folder;
use app\models\Reference;
use app\models\User;
use lib\exceptions\UserErrorException;
use lib\Validate;
use Yii;
use yii\db\Exception;

trait ItemControllerTrait {

  abstract static function getFolderModel($datasource);
  abstract function getControlledModel($datasource);
  abstract function getActiveUser();
  abstract function dispatchClientMessage($name, $data);
  abstract function broadcastClientMessage($name, $data);
  abstract function requirePermission($name, $datasource);

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
   * Returns the requested or all accessible properties of a reference
   * @param string $datasourceId
   * @param mixed $arg2 if numeric, the id of the reference
   * @param mixed $arg3
   * @param mixed $arg4
   * @return string
   * @throws \InvalidArgumentException
   * @todo: this method is called with different signatures!
   */
  function actionItem($datasourceId, $arg2, $arg3 = null, $arg4 = null)
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

    if (!$datasourceId or !is_numeric($id)) {
      throw new \InvalidArgumentException("Invalid arguments.");
    }

    // load model record and get reference type
    $modelClass = $this->getControlledModel($datasourceId);
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
    if (Yii::$app->config->keyExists("datasource.$datasourceId.fields.exclude")) {
      $excludeFields = Yii::$app->config->getPreference("datasource.$datasourceId.fields.exclude");
      if (count($excludeFields)) {
        $fields = array_diff($fields, $excludeFields);
      }
    }

    // prepare record data for the form
    $item = array(
      'datasource' => $datasourceId,
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
   * Returns information on the record as a HTML table
   * @param $datasource
   * @param $referenceId
   * @return array
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
   * @param $datasourceId
   * @param $itemId
   * @return array
   */
  public function actionItemHtml($datasourceId, $itemId)
  {
    $modelClass = $this->getControlledModel($datasourceId);
    /** @var \app\schema\AbstractReferenceSchema $schema */
    $schema = $modelClass::getSchema();
    /** @var Reference $reference */
    $reference = $modelClass::findOne($itemId);
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
        '&datasource=' . $datasourceId .
        '&selector=' . $itemId;
      $links[] = "<a href=\"$url\" target=\"_blank\" data-id=\"{$format->namedId}\">{$format->name}</a>";
    }
    $html .= "<p>" . Yii::t('app','Export citation as ') . implode(" | ", $links ) . "</p>";

    return array(
      'html' => $html
    );
  }

//  /**
//   * Returns data on folders that contain the given reference
//   * @param $datasource
//   * @param $referenceId
//   */
//  public function actionContainers($datasource, $referenceId)
//  {
//    $reference = $this->getRecordById($datasource, $referenceId);
//    $folders = $reference->getFolders()->all();
//    $data = array();
//    foreach ($folders as $folder) {
//      $data[] = [
//        $folder->id,
//        $folder->getIcon("default"),
//        $folder->getLabelPath("/")
//      ];
//    }
//    return $data;
//  }
//
//
//  /**
//   * Returns potential duplicates in a simple data model format.
//   * @param string $datasource
//   * @param int $referenceId
//   *
//   */
//  function actionDuplicatesData($datasource, $referenceId)
//  {
//    // @todo
//    return [];
//
//    $reference = $this->getRecordById($referenceId);
//    $threshold = Yii::$app->config->getPreference("bibliograph.duplicates.threshold");
//    $scores = $reference->findPotentialDuplicates($threshold);
//    $data = array();
//    while ($referenceModel->loadNext()) {
//      $score = round(array_shift($scores));
//      if ($referenceModel->id() == $referenceId or
//        $referenceModel->get("markedDeleted")) {
//        continue;
//      }
//      $reftype = $referenceModel->getReftype();
//      $data[] = array(
//        $referenceModel->id(),
//        $reftype ? $referenceModel::getSchema()->getTypeLabel($reftype) : "",
//        $referenceModel->getAuthor(),
//        $referenceModel->getYear(),
//        $referenceModel->getTitle(),
//        $score
//      );
//    }
//    return $data;
//  }
}
