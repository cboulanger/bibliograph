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
    \Yii::$app->response->format = \yii\web\Response::FORMAT_RAW;
    /** @var FileUpload $file */
    $file = FileUpload::getInstanceByName('file');
    if( ! $file->hasError ) {
      $file->save();
    } else {
      return $file->error;
    }
  }
}