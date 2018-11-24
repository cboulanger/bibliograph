<?php

namespace app\modules\backup;

use app\models\{
  Role
};
use app\modules\webservices\models\{
  Datasource as WebservicesDatasource
};
use Exception;
use lib\exceptions\UserErrorException;
use Yii;
use ZipArchive;

/**
 * The path to a writable directory where backups are stored
 * Defaults to the system temporary directory, which is fine, since
 * backups are not meant to be permanently stored.
 */
defined('BACKUP_PATH') or define('BACKUP_PATH', TMP_PATH);

/**
 * webservices module definition class
 * @property WebservicesDatasource[] $datasources
 * @property int backupVersion
 */
class Module extends \lib\Module
{
  /**
   * The version of the module
   * @var string
   */
  protected $version = "0.0.3";

  /**
   * A string constant defining the category for logging and translation
   */
  const CATEGORY = "backup";

  /**
   * The extension of the backup file without the preceding period.
   * @var string
   */
  const BACKUP_FILE_EXT = "backup.zip";

  /**
   * @inheritdoc
   */
  public $controllerNamespace = 'app\modules\backup\controllers';

  /**
   * The version of the backup data format. Increment each time the format changes.
   * @return int
   */
  const BACKUP_VERSION = 1;

  /**
   * Installs the plugin.
   * @param boolean $enabled
   *    Whether the module should be enabled after installation (defaults to false)
   * @return boolean
   * @throws Exception
   */
  public function install($enabled = false)
  {
    $this->_install();
    return parent::install(true);
  }

  protected function _install()
  {
    // Check prerequisites
    $backupPath = BACKUP_PATH ? BACKUP_PATH : TMP_PATH;
    $error = array();
    if (!class_exists("ZipArchive")) {
      array_push($error, "You must install the ZIP extension.");
    }
    if (!file_exists($backupPath) or !is_writable($backupPath)) {
      array_push($error, "Directory '$backupPath' needs to exist and be writable");
    }
    if (count($error) == 0) {
      if( ! @chmod(BACKUP_PATH,0777) ){
        Yii::warning("Cannot make Backup folder world-writable.");
      };
      $zip = new ZipArchive();
      $testfile = "$backupPath/test.zip";
      if ($zip->open($testfile, ZIPARCHIVE::CREATE) !== TRUE) {
        array_push($error, "Cannot create backup archive in backup folder - please check file permissions.");
      } else {
        $zip->addFile(Yii::getAlias('@runtime/logs/app.log'));
        $zip->close();
        if (@unlink($testfile) === false) {
          Yii::warning("Cannot delete files in backup folder - please check file permissions.", Module::CATEGORY);
          //array_push($error, "Cannot delete files in backup folder - please check file permissions.");
        }
      }
    }

    if (count($error)) {
      throw new UserErrorException(implode(" ", $error));
    }

    // preferences and permissions
    Yii::$app->config->addPreference("backup.daysToKeepBackupFor", 3);
    Yii::$app->accessManager->addPermissions(
      [
        "backup.create",
        "backup.restore",
        "backup.delete",
        "backup.download",
        "backup.upload"
      ],
      [
        Role::findByNamedId('manager')
      ]
    );

  }
}
