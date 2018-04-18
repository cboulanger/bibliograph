<?php

namespace app\controllers;

use Yii;
use app\controllers\traits\AuthTrait;
use app\models\FileUpload;
use yii\web\ServerErrorHttpException;

class UploadController extends \yii\web\Controller
{
  use AuthTrait;

  /**
   * FIXME Fix to suppress Error, probably very bad.
   * @inheritdoc
   */
  public $enableCsrfValidation = false;

  /**
   * Endpoint for HTTP file uploads. Saves the file to the system temporary dir and
   * saves the file path in the session. Get the path via FileUpload::getLastUploadPath()
   * @throws ServerErrorHttpException
   */
  public function actionIndex()
  {
    $file = new FileUpload();
    if( ! $file->upload() ){
      throw new ServerErrorHttpException(implode("; ", $file->getFirstErrors()));
    }
  }
}