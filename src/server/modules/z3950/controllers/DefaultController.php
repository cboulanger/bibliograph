<?php

namespace app\modules\z3950\controllers;

use yii\web\Controller;

/**
 * Default controller for the `z3950` module
 */
class DefaultController extends Controller
{
    /**
     * Renders the index view for the module
     * @return string
     */
    public function actionIndex()
    {
        return $this->render('index');
    }
}
