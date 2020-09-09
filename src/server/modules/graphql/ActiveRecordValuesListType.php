<?php

namespace app\modules\graphql;

use app\controllers\traits\AuthTrait;
use app\controllers\traits\DatasourceTrait;
use GraphQL\Server\RequestError;
use GraphQL\Type\Definition\ListOfType;
use GraphQL\Type\Definition\ObjectType;
use GraphQL\Type\Definition\Type;
use lib\models\BaseModel;
use Yii;

/**
 *
 */
class ActiveRecordValuesListType extends ActiveRecordListType {

  /**
   * @param $rootValue
   * @param $args
   * @return array
   * @throws RequestError
   */
  public function resolve($rootValue, $args) {
    //yii::debug("Requested {$this->name} with args " . json_encode($args));
    /** @var BaseModel $class */
    $class = $this->getClass($args);
    try {
      $query = $class::find()->where($args['condition'])->asArray();
      return  $query->createCommand()->queryAll(\PDO::FETCH_NUM);
    } catch (\Throwable $e) {
      Yii::error($e);
      throw new RequestError($e->getMessage());
    }
  }
}
