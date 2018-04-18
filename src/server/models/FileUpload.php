<?php
namespace app\models;

use RuntimeException;
use Yii;
use yii\web\UploadedFile;

/**
 * Class FileUpload
 * @inheritdoc
 * @package app\models
 */
class FileUpload extends UploadedFile
{

  const LAST_UPLOAD_PATH_SESSION_VAR = "lastUploadPath";

  /**
   * Return the path of the file which was uploaded last
   * @return string
   * @throws RuntimeException
   */
  static function getLastUploadPath()
  {
    $path = Yii::$app->session->get(self::LAST_UPLOAD_PATH_SESSION_VAR);
    if( ! $path ){
      throw new RuntimeException("No file was uploaded");
    }
    return $path;
  }

  /**
   * Deletes the last uploaded file and the corresponding session variable
   */
  static function deleteLastUpload()
  {
    unlink(self::LAST_UPLOAD_PATH_SESSION_VAR);
    Yii::$app->session->remove(self::LAST_UPLOAD_PATH_SESSION_VAR);
  }

  /**
   * Store the uploaded file in the system temp folder and store the path.
   * Returns the path of the uploaded file on success and false if an error occurred. In case of errors,
   * inspect the `errors` and `firstErrors` attributes.
   * @return string|false
   */
  public function save()
  {
    $path = sys_get_temp_dir() . DIRECTORY_SEPARATOR . $this->baseName . '.' . $this->extension;
    $this->saveAs($path);
    Yii::$app->session->set(self::LAST_UPLOAD_PATH_SESSION_VAR, $path);
  }
}