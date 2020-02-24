<?php

namespace app\controllers;

use \georgique\yii2\jsonrpc\Controller;
use yii\filters\ContentNegotiator;
use yii\web\Response;
use Yii;


class JsonRpcController extends Controller
{
  // Disable CSRF validation for JSON-RPC POST requests
  public $enableCsrfValidation = false;

  // JSON object are converted to PHP Objects, not associative arrays
  public $requestParseAsArray = false;

  public function __construct($id, $module, $config = [])
  {
    //Yii::debug(\Yii::$app->request->getRawBody());
    //Yii::debug(\Yii::$app->request->post() );
    parent::__construct($id, $module, $config);
  }

  /**
   * @inheritdoc
   */
  public function behaviors()
  {
    return [
      'contentNegotiator' => [
        'class' => ContentNegotiator::class,
        'formats' => [
          'application/json' => Response::FORMAT_JSON,
        ],
      ]
    ];
  }

}
