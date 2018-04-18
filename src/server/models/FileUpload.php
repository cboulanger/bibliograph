<?php
namespace app\models;

use RuntimeException;
use Yii;
use yii\base\Model;
use yii\web\UploadedFile;

class FileUpload extends Model
{
  const LAST_UPLOAD_PATH_SESSION_VAR = "lastUploadPath";

  /**
   * @var UploadedFile
   */
  public $file;

  public function rules()
  {
    // @todo dynamically assign validation rule for extensions?
    return [
      [['file'], 'file', 'skipOnEmpty' => false, 'extensions' => null],
    ];
  }

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
  public function upload()
  {
    if ($this->validate()) {
      $path = realpath( sys_get_temp_dir() . DIRECTORY_SEPARATOR . $this->file->baseName . '.' . $this->file->extension);
      $this->file->saveAs($path);
      Yii::$app->session->set(self::LAST_UPLOAD_PATH_SESSION_VAR, $path);
      return $path;
    }
    return false;
  }
}