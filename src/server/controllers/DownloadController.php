<?php

namespace app\controllers;

use Yii;
use app\controllers\traits\AuthTrait;
use app\models\LastFileUpload;
use yii\web\ServerErrorHttpException;

class DownloadController extends \yii\web\Controller
{
  use AuthTrait;

  /**
   * FIXME Fix to suppress Error, probably very bad.
   * @inheritdoc
   */
  public $enableCsrfValidation = false;

  /**
   * Endpoint for HTTP file downloads
   * @throws ServerErrorHttpException
   */
  public function actionIndex()
  {

  }
}