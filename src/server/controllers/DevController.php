<?php

namespace app\controllers;

use \lib\components\Configuration;
use lib\exceptions\UserErrorException;
use Yii;

/**
 * Console methods for development
 * @package app\controllers
 */
class DevController extends \yii\console\Controller
{
  private function becomeMysqlRoot() {
    if (!isset($_SERVER['DB_ROOT_PASSWORD']) or ! $_SERVER['DB_ROOT_PASSWORD'] ) {
      throw new UserErrorException("You must set the DB_ROOT_PASSWORD environment variable");
    }
    Yii::$app->db->username = "root";
    Yii::$app->db->password = $_SERVER['DB_ROOT_PASSWORD'];
    Yii::$app->db->dsn = "{$_SERVER['DB_TYPE']}:host={$_SERVER['DB_HOST']};port={$_SERVER['DB_PORT']};charset=UTF8";
  }

  private function executeCmd(yii\db\Command $cmd) {
    //echo $cmd->rawSql;
    $cmd->execute();
  }


  /**
   * Create the DB user needed by the application, given in the .env file
   * @param boolean $drop If true, drop any existing user of that name
   * before recreating them
   */
  public function actionCreateDbUser($drop=false) {
    $this->becomeMysqlRoot();
    $this->executeCmd(Yii::$app->db->createCommand(
      ($drop ? "DROP USER IF EXISTS :user; " : "") .
      "CREATE USER IF NOT EXISTS :user IDENTIFIED BY :password;".
      "CREATE USER :user IDENTIFIED BY :password;",
      [ 'user' => $_SERVER['DB_USER'], 'password' => $_SERVER['DB_PASSWORD']]
    ));
    echo "Created mysql user " . $_SERVER['DB_USER'] . ".\n";
  }

  /**
   * Create the database needed by the application, given in the .env file
   * (User needs to be created first)
   * @param boolean $drop If true, drop any existing database of that name
   * before recreating it
   */
  public function actionCreateDatabase($drop=false) {
    $this->becomeMysqlRoot();
    $this->executeCmd(Yii::$app->db->createCommand(
      ($drop ? "DROP DATABASE IF EXISTS `{$_SERVER['DB_DATABASE']}`; " : "") .
      "CREATE DATABASE IF NOT EXISTS `{$_SERVER['DB_DATABASE']}`; ".
      "GRANT SELECT, INSERT, DELETE, UPDATE, CREATE, DROP, ALTER, EXECUTE ON `{$_SERVER['DB_DATABASE']}`.* TO '{$_SERVER['DB_USER']}'@'%'; " .
      "FLUSH PRIVILEGES;"
    ));
    echo "Created database '{$_SERVER['DB_DATABASE']}' and assigned necessary rights to user '{$_SERVER['DB_USER']}'.\n";
  }

  /**
   * Completely resets the database (deleting it!)
   */
  public function actionResetDatabase() {
    $this->actionCreateDbUser(true);
    $this->actionCreateDatabase(true);
  }


  /**
   * Resets the application caches
   */
  public function actionResetCaches() {
    try {
      Yii::$app->cache->flush();
    } catch (\Exception $e) {}
    echo "Caches flushed.\n";
  }

  /**
   * Resets database and caches to factory state, deleting all data.
   */
  public function actionReset() {
    $this->actionResetCaches();
    $this->actionResetDatabase();
    echo "Application reset to factory state. Clear your browser cookies to finishe the reset.\n";
  }

}
