<?php

namespace app\controllers;

use Yii;
use app\controllers\traits\AuthTrait;
use app\models\LastFileUpload;
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
    /** @var LastFileUpload $file */
    $file = LastFileUpload::getInstanceByName('file');
    if( ! $file->hasError ) {
      $path = $file->save();
      if ( $path === false ){
        Yii::warning('Saving file failed: ' . $file->error);
      } else {
        Yii::debug("Uploaded file successfully saved to '$path'", __METHOD__);
      }
    } else {
      Yii::warning('File upload failed: ' . $file->error);
      return $file->error;
    }
  }
}