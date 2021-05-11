<?php
/**
 * Created by PhpStorm.
 * User: cboulanger
 * Date: 12.06.18
 * Time: 08:31
 */

namespace app\modules\backup\controllers;

use app\controllers\traits\DatasourceTrait;
use yii\console\ExitCode;

class ConsoleController extends \yii\console\Controller
{
  use ServicesTrait;
  use DatasourceTrait;

  public function getActiveUser()
  {
    return null;
  }

  /**
   * Create a backup
   * @param string $datasource Name of the datasource
   * @param string|null $comment Optional comment
   * @throws \lib\exceptions\Exception
   */
  public function actionCreate(string $datasource, string $comment = null)
  {
    try {
      $this->createBackup($this->datasource($datasource, false), $progressBar, $comment);
    } catch (\RuntimeException $e) {
      $this->stderr("Error: " . $e->getMessage() . PHP_EOL);
      return ExitCode::UNSPECIFIED_ERROR;
    }
    return ExitCode::OK;
  }

  /**
   * List available backups
   * @param string $datasource
   */
  public function actionList(string $datasource)
  {
    try {
      $files = $this->listBackupFiles($this->datasource($datasource,false));
      $options = $this->createFormOptions($files);
      foreach ( $options as $option){
        $this->stdout("{$option['timestamp']} ({$option['label']})" . PHP_EOL);
      }
    } catch (\Exception $e) {
      $this->stderr("Error: " . $e->getMessage() . PHP_EOL);
      return ExitCode::UNSPECIFIED_ERROR;
    }
    return ExitCode::OK;
  }

  /**
   * Restore a backup
   * @param string $datasource
   * @param int $timestamp The timestamp of the backup
   * @throws \lib\exceptions\Exception
   */
  public function actionRestore(string $datasource, int $timestamp)
  {
    $datasource = $this->datasource($datasource,false);
    $file = $this->findFileByTimestamp($datasource, $timestamp);
    if( ! $file ){
      $this->stderr('Error: Timestamp does not match any file' . PHP_EOL);
      return ExitCode::DATAERR;
    }
    try {
      $result = $this->restoreBackup($datasource, $file);
      if( $result['errors'] > 0 ){
        throw new \RuntimeException("Restore unsuccessful. Please check log files.");
      }
    } catch (\RuntimeException $e) {
      $this->stderr("Error: " . $e->getMessage() . PHP_EOL);
      return ExitCode::UNSPECIFIED_ERROR;
    }
    return ExitCode::OK;
  }
}
