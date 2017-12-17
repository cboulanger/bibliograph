<?php

namespace bibliograph\controllers;

use \JsonRpc2\Controller;

class ApplicationController extends Controller
{
    public function actionUpdate($message)
    {
        return ["message" => "Hallo liebe ".$message];
    }
}