<?php

namespace bibliograph\controllers;

use \JsonRpc2\Controller;

class SiteController extends Controller
{
    public function actionError()
    {
        $exception = Yii::$app->errorHandler->exception;
        return [ "message" => $exception ];
    }
}