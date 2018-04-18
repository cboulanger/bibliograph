<?php

namespace controllers;

use app\controllers\traits\AuthTrait;
use app\models\FileUpload;
use Yii;
use yii\web\ServerErrorHttpException;

class UploadController extends \yii\web\Controller
{
  use AuthTrait;

  /**
   * Endpoint for HTTP file uploads. Saves the file to the system temporary dir and
   * saves the file path in the session. Get the path via FileUpload::getLastUploadPath()
   * @throws ServerErrorHttpException
   */
  public function actionUpload()
  {
    $file = new FileUpload();
    if( ! $file->upload() ){
      throw new ServerErrorHttpException(implode("; ", $file->getFirstErrors()));
    }
  }
}