<?php
/**
 * Created by PhpStorm.
 * User: cboulanger
 * Date: 12.06.18
 * Time: 08:31
 */

namespace app\modules\backup\controllers;

use app\controllers\traits\DatasourceTrait;
use lib\console\Progress;
use app\modules\backup\Module;
use Yii;
use yii\console\ExitCode;

class ConsoleController extends \yii\console\Controller
{
  use ServicesTrait;
  use DatasourceTrait;

  /**
   * Service to backup a datasource's model data
   * @param string $datasource Name of the datasource
   * @param string $id The widgetId of the widget displaying
   *    the progress of the backup
   * @param string|null $comment Optional comment
   * @throws \JsonRpc2\Exception
   */
  public function actionCreate(string $datasource, string $comment = null)
  {
    $progressBar = new Progress();
    try {
      $file = $this->createBackup($this->datasource($datasource, false), $progressBar, $comment);
      $progressBar->complete(Yii::t(Module::CATEGORY, "Backup of '$datasource' has been saved in '$file'."));
    } catch (\RuntimeException $e) {
      $progressBar->complete();
      $this->stderr("Error: " . $e->getMessage() . PHP_EOL);
      return ExitCode::UNSPECIFIED_ERROR;
    }
    return ExitCode::OK;
  }

  /**
   * @param string $datasource
   */
  public function actionList(string $datasource)
  {
    $files = $this->listBackupFiles($this->datasource($datasource,false));
    $options = $this->createFormOptions($files);
    foreach ( $options as $option){
      $this->stdout("{$option['timestamp']} ({$option['label']})" . PHP_EOL);
    }
    return ExitCode::OK;
  }

  /**
   * @param string $datasource
   * @param int $timestamp The timestamp of the backup
   * @throws \JsonRpc2\Exception
   */
  public function actionRestore(string $datasource, int $timestamp)
  {
    $datasource = $this->datasource($datasource,false);
    $file = $this->findFileByTimestamp($datasource, $timestamp);
    if( ! $file ){
      $this->stderr('Error: Timestamp does not match any file' . PHP_EOL);
      return ExitCode::DATAERR;
    }
    $progressBar = new Progress();
    try {
      $result = $this->restoreBackup($datasource, $file, $progressBar);
      Yii::debug($result,Module::CATEGORY);
      if( $result['errors'] > 0 ){
        throw new \RuntimeException("Restore unsuccessful. Please check log files.");
      }
      $progressBar->complete("The backup has been restored.");
    } catch (\RuntimeException $e) {
      $progressBar->complete();
      $this->stderr("Error: " . $e->getMessage() . PHP_EOL);
      return ExitCode::UNSPECIFIED_ERROR;
    }
    return ExitCode::OK;
  }
}