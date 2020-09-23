<?php

namespace app\controllers\traits;

use app\models\Datasource;
use app\schema\BibtexSchema;
use lib\cql\NaturalLanguageQuery;
use Yii;
use yii\db\ActiveQuery;

trait QueryActionsTrait {

  abstract function findIn($datasourceId, $type);

  /**
   * @param string $input
   * @param integer $inputPosition
   * @param array $tokens
   * @param string $datasourceName
   * @todo rename
   * return TokenFieldDto[]
   */
  public function actionTokenizeQuery( $input, $inputPosition, array $tokens, $datasourceName ){
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
          $query = $this->findIn($datasourceName, "reference")
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

  /**
   * Given a natural language query, return the Yii ActiveQuery instance that satisfies the query terms
   * @param string $modelClass
   * @param string $datasourceName
   * @param string $naturalLanguageQuery
   * @param string|array $columns
   * @return ActiveQuery
   * @todo rewrite this
   * @todo modelClass can probably be determined by the datasource and be removed from params
   */
  protected function createActiveQueryFromNaturalLanguageQuery(
    string $modelClass,
    string $datasourceName,
    string $naturalLanguageQuery,
    $columns="*",
    bool $debug = false,
    bool $verbose = false)
  {
    // use the language that works/yields most hits
    $languages=Yii::$app->utils->getLanguages();
    $hits = 0;
    $bestQuery = null;
    foreach ($languages as $language) {
      /** @var ActiveQuery $activeQuery */
      $activeQuery = $modelClass::find()
        ->select($columns)
        ->where(['markedDeleted' => 0]);
      $schema = Datasource::in($datasourceName,"reference")::getSchema();
      $nlq = new NaturalLanguageQuery([
        'query'     => $naturalLanguageQuery,
        'schema'    => $schema,
        'language'  => $language,
        'verbose'   => $verbose
      ]);
      if ($debug) Yii::debug(">>> Translating query '$naturalLanguageQuery' from '$language'...", __METHOD__);
      try {
        $nlq->injectIntoYiiQuery($activeQuery);
      } catch (\Exception $e) {
        // error parsing the query
        if ($debug) Yii::debug($e->getMessage(), __METHOD__);
        $activeQuery->where("TRUE = FALSE");
      }
      try{
        if ($debug) Yii::debug($activeQuery->createCommand()->rawSql, __METHOD__);
        $h = $activeQuery->count();
        if ($debug) Yii::debug("$h hits", __METHOD__);
        if ( $h > $hits) {
          $hits = $h;
          $bestQuery = $activeQuery;
        };
      } catch (\Exception $e){
        Yii::warning($e->getMessage());
      }
    }
    return $bestQuery ? $bestQuery : $activeQuery;
  }
}
