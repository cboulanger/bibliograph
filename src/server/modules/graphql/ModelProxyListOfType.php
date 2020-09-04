<?php

namespace app\modules\graphql;

use app\controllers\traits\AuthTrait;
use app\controllers\traits\DatasourceTrait;
use GraphQL\Server\RequestError;
use GraphQL\Type\Definition\ListOfType;
use GraphQL\Type\Definition\Type;
use lib\models\BaseModel;
use Yii;
use yii\db\ActiveRecord;

/**
 *
 */
class ModelProxyListOfType extends ListOfType {

  use DatasourceTrait;
  use AuthTrait;

  /**
   * Returns the args part of the query
   * @return array
   */
  public function getArgs() {
    return [
      'condition' => [
        'type' => Type::string(),
        'description' => "The condition for filtering records",
        'defaultValue' => ""
      ],
      'datasource' => [
        'type' => Type::string(),
        'description' => "The datasource in which the record is to be found",
        'defaultValue' => null
      ]
    ];
  }

  /**
   * @param $rootValue
   * @param $args
   * @return mixed
   * @throws RequestError
   */
  public function resolve($rootValue, $args) {
    //yii::debug("Requested {$this->name} with args " . json_encode($args));
    /** @var BaseModel $class */
    $class = $this->getWrappedType()->getModelClass();
    /** @var BaseModel $instance */
    $instance = null;
    $result = null;
    if (isset($args['datasource'])) {
      try {
        $datasource = $this->datasource($args['datasource']);
        $type = $datasource->getTypeFor($class);
        if (!$type) {
          throw new RequestError("Invalid class {$class} for datasource {$args['datasource']}/type $type");
        }
        $class = $datasource->getClassFor($type);
      } catch (\Throwable $e) {
        Yii::error($e);
        throw new RequestError($e->getMessage());
      }
    }
    try {
      $query = $class::find()->where($args['condition'])->asArray();
//      if ($args['noFieldNames']) {
//        $result = $query->createCommand()->queryAll(\PDO::FETCH_NUM);
//      } else {
        $result = $query->all();
      //}
    } catch (\Throwable $e) {
      Yii::error($e);
      throw new RequestError($e->getMessage());
    }
    return $result;
  }
}
