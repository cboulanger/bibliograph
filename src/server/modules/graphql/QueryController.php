<?php

namespace app\modules\graphql;

use app\controllers\AppController;
use app\models\Datasource;
use app\models\User;
use GraphQL\GraphQL;
use GraphQL\Type\Schema;
use Yii;

class QueryController extends AppController
{
  /**
   * @return mixed[]
   * @throws \ReflectionException
   */
  function actionIndex() {
    Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;
    $schema = new Schema([
      'query' => new ModelProxyQuery([
        "user" => ModelProxyType::getInstance(User::class),
        "datasource" => ModelProxyType::getInstance(Datasource::class)
      ])
    ]);
    $request = json_decode(Yii::$app->request->rawBody);
    $query = $request->query;
    $result = GraphQL::executeQuery($schema, $query);
    return $result->toArray();
  }
}
