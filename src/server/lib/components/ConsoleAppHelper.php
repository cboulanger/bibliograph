<?php

namespace lib\components;
use Stringy\Stringy;
use Yii;
use yii\db\Exception;

/**
 * Class ConsoleAppHelper
 * from https://github.com/yiisoft/yii2/issues/1764#issuecomment-54537675
 * @package lib\components
 */
class ConsoleAppHelper extends \yii\base\Component
{

  /**
   * Runs a console action. Returns the result if successful, otherwise
   * throws the error.
   *
   * @param string $route
   * @param array $params
   * @param string $controllerNamespace
   * @param yii\db\Connection|null $customDb
   *    The database connection to use or null for the default application db
   * @return \Stringy\Stringy The output of the console action as a Stringy object
   * @throws MigrationException
   * @throws \Exception
   */
  public static function runAction( 
    $route, 
    $params = [ ], 
    $controllerNamespace = null, 
    yii\db\Connection $customDb = null )
  {
    $oldApp = Yii::$app;

    // fcgi doesn't have STDIN and STDOUT defined by default
    defined( 'STDIN' ) or define( 'STDIN', fopen( 'php://stdin', 'r' ) );
    defined( 'STDOUT' ) or define( 'STDOUT', fopen('php://output', 'w') );

    $config = require( Yii::getAlias( '@app/config/console.php' ) );
    $consoleApp = new \yii\console\Application( $config );

    if (!is_null( $controllerNamespace )) {
      $consoleApp->controllerNamespace = $controllerNamespace;
    }

    try {

      if( $customDb ){
        //Yii::info("*****Setting new db: " . var_export($customDb, true));
        Yii::$app->set( 'db', $customDb );
      } else {
        //Yii::info("*****Using old db");
        // use current connection to DB
        Yii::$app->set( 'db', $oldApp->db );
      }

      ob_start();

      $exitCode = $consoleApp->runAction(
        $route,
        array_merge( $params, [ 'interactive' => false, 'color' => false ] )
      );
      $consoleOutput = ob_get_clean();
      Yii::debug( "\n" .
        "Output of console action '$route':\n" .
        "---------------------------------------------------------\n" .
        $consoleOutput . "\n".
        "---------------------------------------------------------\n",
        "console" 
      );      
    } catch ( \Exception $e ) {
      Yii::$app = $oldApp;
      throw $e;
    }
    Yii::$app = $oldApp;
    $consoleOutput = Stringy::create($consoleOutput);
    if( $exitCode ){
      $e = new MigrationException("Running '$route' failed.");
      $e->exitCode = $exitCode;
      $e->consoleApp = $consoleApp;
      $e->consoleOutput = $consoleOutput;
      throw $e;
    }
    return $consoleOutput;
  }
}

class MigrationException extends Exception
{
  /**
   * The console output of the migration command, wrapped in a
   * Stringy object for easier manipulation
   * @var \Stringy\Stringy
   */
  public $consoleOutput;

  /**
   * The console application used
   * @var \yii\console\Application
   */
  public $consoleApp;

  /**
   * The exit code of the console command
   * @var int
   */
  public $exitCode;
}
