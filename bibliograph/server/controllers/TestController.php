<?php

namespace app\controllers;

class TestController extends \JsonRpc2\Controller
{
    public function actionError()
    {
        $exception = Yii::$app->errorHandler->exception;
        return [ "message" => $exception ];
    }

    public function actionTest($message)
    {
        return ["message" => "hello ".$message];
    }
}