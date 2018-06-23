<?php
namespace app\models;

use RuntimeException;
use Yii;
use yii\web\UploadedFile;

/**
 * Class FileUpload
 * @inheritdoc
 * @package app\models
 * @property string $path Readonly path property
 */
class LastFileUpload extends UploadedFile
{

  /**
   * Returns the path to the uploaded file
   * @readonly
   * @return string
   */
  public function getPath()
  {
    return sys_get_temp_dir() .
      DIRECTORY_SEPARATOR .
      Yii::$app->session->id . "_" .
      $this->baseName . '.' . $this->extension;
  }

  /**
   * @return string
   */
  private static function cacheKey()
  {
    return Yii::$app->session->id . "_last_upload_info";
  }

  /**
   * Store the uploaded file in the system temp folder and store the path.
   * Returns the path of the uploaded file on success and false if an error occurred. In case of errors,
   * inspect the `errors` and `firstErrors` attributes.
   * @return string|false
   */
  public function save()
  {
    $path = $this->path;
    if( ! parent::saveAs($path) ){
      return false;
    }
    Yii::$app->cache->set(self::cacheKey(), serialize($this));
    Yii::debug("Uploaded file was saved to '$path', object stored in cache.", __METHOD__);
    return $path;
  }

  /**
   * Must not be called for this class
   * @throws \BadMethodCallException
   */
  public function saveAs($file, $deleteTempFile = true)
  {
    throw new \BadMethodCallException("Not allowed for " . self::class);
  }

  /** @noinspection PhpSignatureMismatchDuringInheritanceInspection */

  /**
   * Return the instance for the last uploaded file
   * @return LastFileUpload
   * @throws RuntimeException
   */
  static function instance()
  {
    $instance = unserialize( Yii::$app->cache->get(self::cacheKey()));
    if( ! $instance ){
      throw new RuntimeException("No cached instance of " . self::class );
    }
    return $instance;
  }

  /**
   * Deletes the last uploaded file and the corresponding session variable
   */
  public function delete()
  {
    Yii::debug("Deleting last upload.", __METHOD__);
    unlink($this->path);
    Yii::$app->cache->delete(self::cacheKey());
  }
}