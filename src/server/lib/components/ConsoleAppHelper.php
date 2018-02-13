<?php

namespace lib\components;
use Stringy\Stringy;

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
   * @throws \Exception
   * @return \Stringy\Stringy The output of the console action as a Stringy object
   */
  public static function runAction( $route, $params = [ ], $controllerNamespace = null )
  {
    $oldApp = \Yii::$app;

    // fcgi doesn't have STDIN and STDOUT defined by default
    defined( 'STDIN' ) or define( 'STDIN', fopen( 'php://stdin', 'r' ) );
    #defined( 'STDOUT' ) or define( 'STDOUT', fopen( 'php://stdout', 'w' ) );
    defined( 'STDOUT' ) or define( 'STDOUT', fopen('php://output', 'w') );

    $config = require( \Yii::getAlias( '@app/config/console.php' ) );
    $consoleApp = new \yii\console\Application( $config );

    if (!is_null( $controllerNamespace )) {
      $consoleApp->controllerNamespace = $controllerNamespace;
    }

    try {
      // use current connection to DB
      \Yii::$app->set( 'db', $oldApp->db );

      ob_start();
      $exitCode = $consoleApp->runAction(
        $route,
        array_merge( $params, [ 'interactive' => false, 'color' => false ] )
      );
      $result = ob_get_clean();

    } catch ( \Exception $e ) {
      \Yii::$app = $oldApp;
      throw $e;
    }
    \Yii::$app = $oldApp;
    if( $exitCode ){
      throw new \LogicException("Running action '$route' exited with code $exitCode.");
    }
    return Stringy::create($result);
  }
} 