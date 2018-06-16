<?php
/**
 * Created by PhpStorm.
 * User: cboulanger
 * Date: 06.06.18
 * Time: 09:33
 */

namespace app\modules\backup\controllers;

use app\models\Datasource;
use app\models\Reference;
use app\modules\backup\Module;
use cebe\markdown\tests\MarkdownOLStartNumTest;
use DateTime;
use lib\interfaces\Progress;
use lib\models\BaseModel;
use PhpParser\Node\Expr\AssignOp\Mod;
use RuntimeException;
use Yii;
use yii\db\ActiveQuery;
use yii\db\Connection;
use yii\db\Exception;
use ZipArchive;

trait ServicesTrait
{

  /**
   * Given the datasource and a timestamp, return the name of the filename containing the backup
   * @param Datasource $datasource
   * @param int $timestamp
   * @return string
   */
  protected function createBackupFileName( Datasource $datasource, int $timestamp )
  {
    return $datasource->namedId .
      "_" . Module::BACKUP_VERSION .
      "_" . $datasource->migrationApplyTime .
      "_" . $timestamp .
      "." . Module::BACKUP_FILE_EXT;
  }

  /**
   * Parses the name of the backup file and returns the elements
   * @return array
   *    Array consisting of
   *    0 => the named id of the datasource,
   *    1 => the backup version
   *    2 => the migration apply time, and
   *    3 => the timestamp of the backup
   */
  protected function parseBackupFilename($filename)
  {
    $filename = substr(basename($filename), 0,  - strlen("." . Module::BACKUP_FILE_EXT));
    return explode("_", $filename);
  }

  /**
   * Given a Unix timestamp, Return the path to the backup file, or null if no such file exists
   * @param Datasource $datasource
   * @param int $timestamp
   * @return null|string The nane of the backup file (without containing directory)
   */
  protected function findFileByTimestamp( Datasource $datasource, int $timestamp )
  {
    $files = $this->listBackupFiles($datasource);
    foreach( $files as $file ){
      if( (int) $this->parseBackupFilename($file)[3] === $timestamp ) {
        return basename($file);
      }
    }
    return null;
  }

  /**
   * Returns an array of options for use in form data
   * @param array $files An array of file paths
   * @return array
   */
  protected function createFormOptions(array $files)
  {
    $options = [];
    foreach ($files as $file) {
      list($datasource, $backupVersion, $migrationApplyTime, $timestamp) = $this->parseBackupFilename($file);
      $datetime = new DateTime();
      $datetime->setTimestamp($timestamp);
      $options[] = [
        //'datasource'          => $datasource,
        //'backupVersion'       => $backupVersion,
        //'migrationApplyTime'  => $migrationApplyTime,
        'timestamp'           => (int) $timestamp,
        'label'               => $datetime->format('Y-m-d H:i:s'),
        'value'               => basename($file)
      ];
    }
    return $options;
  }

  /**
   * Returns a list of backup files for the given datasource that matches the migration version
   * @param Datasource $datasource
   */
  protected function listBackupFiles(Datasource $datasource)
  {
    $backupPath = BACKUP_PATH ? BACKUP_PATH : TMP_PATH;
    $backupVersion = Module::BACKUP_VERSION;
    $migrationApplyTime = $datasource->migrationApplyTime;
    $ext = Module::BACKUP_FILE_EXT;
    $files = glob("{$backupPath}/{$datasource->namedId}_{$backupVersion}_{$migrationApplyTime}*.{$ext}");
    return $files;
  }

  /**
   * Method to create a backup of a datasource
   * @param Datasource $datasource
   * @param Progress $progressBar
   * @param string|null $comment Optional comment
   * @return string The path to the ZIP-Archive containing the backups
   * @throws RuntimeException
   */
  public function createBackup(Datasource $datasource, Progress $progressBar = null, $comment = null)
  {
    // ZIP Archive
    $timestamp = time();
    $datetime = new DateTime();
    $datetime->setTimestamp($timestamp);
    $backuptime = $datetime->format('Y-m-d H:i:s');
    $migrationApplyTime = $datasource->migrationApplyTime;
    $zipFileName = $this->createBackupFileName($datasource, $timestamp);
    $backupPath = BACKUP_PATH ? BACKUP_PATH : TMP_PATH;
    $zipFilePath = "$backupPath/$zipFileName";
    $zip = new ZipArchive();
    $res = $zip->open($zipFilePath, ZipArchive::CREATE);
    if ($res !== true) {
      throw new RuntimeException("Could not create zip archive: " . $zip->getStatusString());
    }
    // Model data
    $classes = Yii::$app->datasourceManager->getModelClasses($datasource);
    $tmpFiles = [];
    $step1 = 100 / count($classes);
    $index1 = 0;
    $converter = function ($v) {
      if (is_bool($v)) return $v ? 1 : 0;
      if ( $v===null ) return "NULL";
      return $v;
    };
    foreach ($classes as $type => $class) {
      $tmpFileName = TMP_PATH . "/" . md5(microtime());
      $tmpFileHandle = fopen($tmpFileName, "w");
      $tmpFiles[] = $tmpFileName;
      $columns = $class::getTableSchema()->columnNames;
      /** @var ActiveQuery $query */
      $query = $class::find();
      /** @var BaseModel $model */
      $model = new $class;
      $total = (int) $query->count();
      $comment = $comment ?? "Backup of $datasource->namedId/$type at $backuptime";
      Yii::info("Creating backup for '$datasource->namedId', model type '$type', migration timestamp $migrationApplyTime, $total records, note: '$comment'.", Module::CATEGORY);
      // header
      $header = [
        Module::BACKUP_VERSION,
        $total,
        $comment
      ];
      fputcsv($tmpFileHandle, $header);
      fputcsv($tmpFileHandle, $columns);
      // save record data, if any
      if( $total > 0 ){
        $count = 1;
        $step2 = $step1 / $total;
        $index2 = 0;
        foreach ( $query->asArray()->each() as $row) {
          Yii::debug($row);
          // marshal data to be stored as CSV, converting booleans to integers
          $data = array_map($converter, array_values($row));
          fputcsv($tmpFileHandle, $data);
          if ($progressBar) {
            $progress = $step1 * $index1 + $step2 * $index2;
            $progressBar->setProgress(
              $progress,
              sprintf("Backing up '%s', %d/%d ... ", $type, $count++, $total)
            );
            $index2++;
          }
        }
      }
      fclose($tmpFileHandle);
      $zip->addFile($tmpFileName, "{$datasource->namedId}_{$timestamp}_{$type}.csv");
      $index1++;
    }
    // wrap up
    $res = $zip->close();
    foreach ($tmpFiles as $file) {
      @unlink($file);
    }
    if ($res === false) {
      throw new RuntimeException("Failed to create zip archive");
    }
    return $zipFilePath;
  }

  /**
   * Actual function to restore the backup. Returns an associative array with diagnostic information
   * @param Datasource $datasource
   * @param string $file The name of the ZIP file containing the backup in the backup dir
   * @param Progress|null $progressBar
   * @throws RuntimeException
   * @return array
   */
  protected function restoreBackup(Datasource $datasource, string $file, Progress $progressBar = null)
  {
    list(, , , $timestamp) = $this->parseBackupFilename($file);
    $backupPath = BACKUP_PATH ? BACKUP_PATH : TMP_PATH;
    $zipFilePath = "$backupPath/$file";
    if (!file_exists($zipFilePath)) {
      throw new RuntimeException(Yii::t(Module::CATEGORY, "Backup file does not exist."));
    }
    $zip = new ZipArchive();
    if ($zip->open($zipFilePath, ZIPARCHIVE::CHECKCONS) !== TRUE) {
      throw new RuntimeException("Cannot open backup archive");
    }
    $zip->extractTo(TMP_PATH);
    $classes = Yii::$app->datasourceManager->getModelClasses($datasource);
    $step1 = 100 / count($classes);
    $index1 = 0;
    $tmpFiles = array();
    $errors = 0;
    $grandTotal = 0;
    foreach ($classes as $type => $class) {
      $tmpFileName = TMP_PATH . "/{$datasource->namedId}_{$timestamp}_{$type}.csv";
      if (!file_exists($tmpFileName) or !is_readable($tmpFileName)) {
        throw new RuntimeException("No valid file data for '$type'");
      }
      $tmpFiles[] = $tmpFileName;
      $tmpFileHandle = fopen($tmpFileName, "r");
      // header
      list($version, $total, $comment) = fgetcsv($tmpFileHandle);
      if( (int) $version !== Module::BACKUP_VERSION ){
        throw new RuntimeException(
          sprintf("Backup has incompatible version format (is: %s, should be: %s).", $version, Module::BACKUP_VERSION)
        );
      }
      Yii::debug(
        "Restoring backup to '$datasource->namedId', model type '$type', backup version $version, $total records, note: '$comment'.",
        Module::CATEGORY
      );
      // restore records
      if( $total > 0 ){
        // columns
        $columns = fgetcsv($tmpFileHandle);
        if (! count($columns) or $columns !== $class::getTableSchema()->columnNames ){
          throw new RuntimeException("Invalid or incompatible column names");
        }
        $count = 1;
        $step2 = $step1 / $total;
        $index2 = 0;
        /** @var Connection $db */
        $db =  $class::getDb();
        // truncate the table
        try {
          $db->createCommand()->truncateTable($class::tableName())->execute();
        } catch (Exception $e) {
          throw new RuntimeException($e->getMessage());
        }
        // insert values
        while ($values = fgetcsv($tmpFileHandle)) {
          $data = array_combine($columns, array_map( function($value){
            return $value === "NULL" ? null : $value;
          }, $values));
          /** @var BaseModel $model */
          $model = new $class($data);
          try {
            $model->save();
          } catch (\yii\db\Exception $e) {
            $errors++;
          }
          if ($progressBar) {
            $progress = $step1 * $index1 + $step2 * $index2;
            $progressBar->setProgress(
              $progress,
              Yii::t(
                Module::CATEGORY,
                "Restoring '{type}', {count}/{total} ... ",
                [
                  'type'  => $type,
                  'count' => $count++,
                  'total' => $total
                ]
              )
            );
            $index2++;
          }
        }
      }
      fclose($tmpFileHandle);
      $index1++;
      $grandTotal += $total;
    }
    $zip->close();
    foreach ($tmpFiles as $file) {
      @unlink($file);
    }
    // todo: reset transaction ids
//    foreach ($classes as $class) {
//      $class->resetTransactionId();
//    }
    return [
      'classes' => count($classes),
      'records' => $grandTotal,
      'errors'  => $errors,
    ];
  }
}