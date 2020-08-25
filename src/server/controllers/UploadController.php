<?php

namespace app\controllers;

use Yii;
use app\controllers\traits\AuthTrait;
use app\models\LastFileUpload;
use yii\web\Response;
use yii\web\ServerErrorHttpException;

class UploadController extends AppController
{
  const CATEGORY = "app";

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
    Yii::$app->response->format = Response::FORMAT_RAW;
    /** @var LastFileUpload $file */
    $file = LastFileUpload::getInstanceByName('file');
    if( ! $file->hasError ) {
      $path = $file->save();
      if ($path && file_exists($path)) {
        Yii::debug("Uploaded file successfully saved to '$path'", static::CATEGORY);
        return ""; // Empty response is success
      } elseif ($path) {
        Yii::error("Uploaded file does not exist at $path");
      }
    }
    return sprintf(_("Upload failed with error '%s'"), $file->error);
  }
}
