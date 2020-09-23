<?php

namespace app\controllers\traits;

use app\models\Datasource;
use app\models\Reference;
use lib\cql\NaturalLanguageQuery;
use lib\exceptions\UserErrorException;
use Yii;
use yii\db\ActiveQuery;
use yii\db\Expression;

trait TableControllerTrait
{

  /**
   * Returns the layout of the columns of the table displaying
   * the records
   *
   * @param $datasourceName
   * @param null|string $modelClassType
   */
  public function actionTableLayout($datasourceName, $modelClassType = null)
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
      'addItems' => $this->getReferenceTypeListData($datasourceName)
    ];
  }

  /**
   * Returns count of rows that will be retrieved when executing the current
   * query.
   *
   * @param object $queryData data to construct the query. Needs at least the
   * a string property "datasource" with the name of datasource and a property
   * "modelType" with the type of the model.
   * @throws \InvalidArgumentException
   */
  public function actionRowCount(\stdClass $queryData)
  {
    /** @var Reference $modelClass */
    $modelClass = $this->getModelClass($queryData->datasource, $queryData->modelType);
    $modelClass::setDatasource($queryData->datasource);

    // add additional conditions from the client query
    $query = $this->transformClientQuery($queryData, $modelClass);

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
   * @param object $queryData Data to construct the query
   * @throws \InvalidArgumentException
   * return array Array containing the keys
   *                int     requestId   The request id identifying the request
   *   (mandatory) array   rowData     The actual row data (mandatory) string
   *   statusText  Optional text to display in a status bar
   */
  function actionRowData(int $firstRow, int $lastRow, int $requestId, \stdClass $queryData)
  {
    /** @var Reference $modelClass */
    $modelClass = $this->getModelClass($queryData->datasource, $queryData->modelType);
    /** @var ActiveQuery $query */
    $query = $this->transformClientQuery($queryData, $modelClass)
      ->orderBy($queryData->query->orderBy)
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
   *       "name" : "folders",
   *       "id": 3,
   *       "foreignId" : "FolderId"
   *     }
   *
   * or
   *    {
   *     "properties" : ["author","title","year" ...],
   *     "orderBy" : "author",
   *     "cql" : "..."
   *    }
   *
   * The cql string can be localized, i.e. the indexes and operators can be in
   * the current application locale
   *
   * @param \stdClass $clientQueryData
   *    The query data object from the json-rpc request
   * @param string $modelClass
   *    The model class from which to create the query
   * @return \yii\db\ActiveQuery
   * @throws \InvalidArgumentException
   * @todo completely rewrite this
   * @todo 'properties' should be 'columns' or 'fields', 'cql' should be
   *   'input'/'search' or similar
   */
  protected function transformClientQuery(\stdClass $clientQueryData, string $modelClass)
  {
    $clientQuery = $clientQueryData->query;
    $datasourceName = $clientQueryData->datasource;

    // params validation
    if (!class_exists($modelClass)) {
      throw new \InvalidArgumentException("Class '$modelClass' does not exist.");
    }
    // @todo doesn't work! use interface instead of base class
    if (!is_subclass_of($modelClass, Reference::class)) {
      //throw new \InvalidArgumentException("Class '$modelClass' must be an subclass of " . Reference::class);
    }

    if (!is_object($clientQuery)) {
      throw new \InvalidArgumentException("Invalid query data: must be object");
    }
    if (!is_array($clientQuery->properties)) {
      throw new \InvalidArgumentException("Invalid property information: must be array");
    }
    // "Creator" column coalesces author and editor data
    $hasCreatorProperty = array_search("creator", $clientQuery->properties) !== false;
    if ($hasCreatorProperty) {
      $clientQuery->properties = array_merge(
        ['author', 'editor', new Expression('coalesce(`author`,`editor`) as creator')],
        array_diff($clientQuery->properties, ['creator'])
      );
    }

    // it's a relational query
    if (isset($clientQuery->relation)) {

      // FIXME hack to support virtual folders
      if ($clientQuery->relation->id > 9007199254740991 - 10000) {
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
        ->joinWith($clientQuery->relation->name, false)
        ->onCondition([$clientQuery->relation->foreignId => $clientQuery->relation->id]);

      //Yii::debug($activeQuery->createCommand()->getRawSql());

      return $activeQuery;
    }

    // it's a freeform search query
    if (isset ($clientQuery->cql)) {
      // FIXME hack to support virtual folders
      if ($clientQuery->cql && str_contains($clientQuery->cql, "virtsub:")) {
        return $modelClass::find()->where(new Expression("TRUE = FALSE"));
      }
      $activeQuery = $this->createActiveQueryFromNaturalLanguageQuery(
        $modelClass,
        $datasourceName,
        $clientQuery->cql,
        $clientQuery->properties
      );
      //Yii::debug($activeQuery->createCommand()->getRawSql());
      return $activeQuery;
    }
    throw new UserErrorException(Yii::t('app', "No recognized query format in request."));
  }
}
