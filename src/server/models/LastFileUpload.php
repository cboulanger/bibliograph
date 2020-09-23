<?php
namespace app\models;

use app\controllers\AppController;
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

  const CATEGORY = "app";

  /**
   * @var string The path to the file which has been copied from the temporary
   * file that is provided by PHP. Needs to be discarded after use with
   * {@link LastFileUpload::delete()}
   */
  public $path;

  /**
   * Creates a unique path to the uploaded file in the system
   * temp dir
   * @return string
   */
  protected function createPath()
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
    return "__last_upload_info";
  }

  /**
   * Store the uploaded file in the system temp folder and store the path.
   * Returns the path of the uploaded file on success and false if an error occurred. In case of errors,
   * inspect the `errors` and `firstErrors` attributes.
   * @return string|false
   */
  public function save()
  {
    $this->path = $this->createPath();
    if (!parent::saveAs($this->path)) {
      return false;
    }
    Yii::$app->session->set(self::cacheKey(), serialize($this));
    return $this->path;
  }

  /**
   * Must not be called for this class
   * @throws \BadMethodCallException
   */
  public function saveAs($file, $deleteTempFile = true)
  {
    throw new \BadMethodCallException("Not allowed for " . self::class);
  }

  /**
   * Return the instance for the last uploaded file
   * @return LastFileUpload
   * @throws RuntimeException
   */
  static function instance()
  {
    $serialized = Yii::$app->session->get(self::cacheKey());
    $instance = unserialize($serialized);
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
    Yii::debug("Deleting last upload.", self::CATEGORY);
    unlink($this->path);
    Yii::$app->session->remove(self::cacheKey());
  }
}
