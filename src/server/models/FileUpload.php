<?php
namespace app\models;

use RuntimeException;
use Yii;
use yii\web\UploadedFile;

/**
 * Class FileUpload
 * @inheritdoc
 * @package app\models
 * @property string $path
 */
class FileUpload extends UploadedFile
{
  const LAST_UPLOAD_PATH_SESSION_VAR = "lastUploadPath";

  /**
   * The path the file upload has been saved to
   * @var string
   */
  protected $path = null;

  /**
   * @return string
   */
  public function getPath()
  {
    return $this->path;
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
    if( ! $this->saveAs($path) ){
      return false;
    }
    Yii::$app->cache->set(self::LAST_UPLOAD_PATH_SESSION_VAR, $path);
    Yii::debug("Uploaded file was saved to '$path', path stored in session.");
    $this->path = $path;
    return $path;
  }

  /**
   * Return the path of the file which was uploaded last
   * @return string
   * @throws RuntimeException
   */
  static function getLastUploadPath()
  {

    $path = Yii::$app->cache->get(self::LAST_UPLOAD_PATH_SESSION_VAR);
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
    Yii::debug("Deleting last upload.");
    unlink(self::LAST_UPLOAD_PATH_SESSION_VAR);
    Yii::$app->cache->delete(self::LAST_UPLOAD_PATH_SESSION_VAR);
  }
}