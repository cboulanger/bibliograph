<?php
/**
 * Created by PhpStorm.
 * User: cboulanger
 * Date: 18.04.18
 * Time: 23:48
 */

namespace app\controllers\traits;

use app\models\Datasource;
use app\models\Reference;
use lib\cql\NaturalLanguageQuery;
use lib\exceptions\UserErrorException;
use Yii;
use yii\db\ActiveQuery;
use yii\db\Expression;

trait TableTrait
{
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
    /** @var Reference $modelClass */
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
    /** @var Reference $modelClass */
    $modelClass = $this->getModelClass($clientQueryData->datasource, $clientQueryData->modelType);
    /** @var ActiveQuery $query */
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
      $indexedQueries =[];
      foreach ($languages as $language) {
        //Yii::debug("Trying to translate query '$clientQuery->cql' from '$language'...");
        /** @var ActiveQuery $activeQuery */
        $activeQuery = $modelClass::find()->where(['markedDeleted' => 0]);
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
          if( $nlq->containsOperators() ){
            break;
          }
          if( $activeQuery->exists() ){
            $indexedQueries[] = $activeQuery;
          }
        } catch (\Exception $e){
          Yii::warning($e->getMessage());
        }
      }
      if( ! $nlq->containsOperators() ){
        if( count($indexedQueries) ) $activeQuery = $indexedQueries[0];
      }
      //Yii::debug($activeQuery->createCommand()->getRawSql());
      return $activeQuery;
    }
    throw new UserErrorException(Yii::t('app', "No recognized query format in request."));
  }
}