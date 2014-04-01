<?php
/*
 * qcl - the qooxdoo component library
 *
 * http://qooxdoo.org/contrib/project/qcl/
 *
 * Copyright:
 *   2007-2014 Christian Boulanger
 *
 * License:
 *   LGPL: http://www.gnu.org/licenses/lgpl.html
 *   EPL: http://www.eclipse.org/org/documents/epl-v10.php
 *   See the LICENSE file in the project's top-level directory for details.
 *
 * Authors:
 *  * Christian Boulanger (cboulanger)
 */

qcl_import( "qcl_data_controller_Controller" );

/**
 * Exception
 */
class qcl_test_AssertionException extends JsonRpcException
{
  function __construct( $msg )
  {
    parent::__construct( "Assertion failed: " . $msg . " in " . get_class($this) . ":" . $this->getLine());
  }
}

/**
 * Abstract class for test controllers
 */
class qcl_test_AbstractTestController
  extends qcl_data_controller_Controller
{

  /**
   * Timestamp for script execution time measurement
   * @var float
   */
  private $timestamp;

  /**
   * Analyzes a PhpRpc method's doc comment. This allows to provide non-standard
   * documentation in the sense that you can use
   * @param $docComment
   * @internal param $params [0], @param $params[1],
   * etc.
   *
   * @todo rewrite more elegantly, see overridden method
   * @return unknown_type
   */
  public static function analyzeDocComment( $docComment )
  {
    $params = array();
    $return = "";
    $doc = "";
    $rpctest = "";
    $lines = explode("\n", $docComment) ;
    $mode = 0; // 0 = doc, 1 = param, 2 = return 3= rpctest
    $paramIndex = -1;
    foreach ($lines as $index => $line )
    {
      if ( ($pos = strpos( $line, "@param" )) !== false  )
      {
        $params[++$paramIndex] = substr( $line, $pos + 7 ) . " ";
        $mode = 1;
      }
      elseif ( ($pos = strpos( $line, "@return" )) !== false  )
      {
       $return = substr( $line, $pos + 8 ) . " ";
       $mode = 2;
      }
      elseif ( ($pos = strpos( $line, "@rpctest" )) !== false  )
      {
       $rpctest = substr( $line, $pos + 9 ) . "\n";
       $mode = 3;
      }
      elseif ( $index < count( $lines ) -1 )
      {
        $text = trim( substr( $line, strrpos( $line, "*" ) + 1 ) ) . " ";
        switch( $mode )
        {
          case 0:
            $doc .=  $text;
            break;
          case 1:
            $params[$paramIndex] .=  $text;
            break;
          case 2:
            $return .=  $text;
            break;
          case 3:
            $rpctest .=  $text . "\n";
            break;
        }
      }
    }
    return array(
      "doc"     => trim($doc),
      "params"  => $params,
      "return"  => trim($return),
      "rpctest" => $rpctest
    );
  }

  /**
   * Returns the json data that can be used to run a jsonrpc console test as a string
   * The frontend needs to evaluate it.
   * @param $method
   * @return string
   */
  public function method_rpcConsoleMethodTestJson( $method )
  {
    $serviceIntrospection = new ServiceIntrospection( $this );
    $serviceIntrospection->checkServiceMethod( $method );
    $docComment = $serviceIntrospection->getDocComment( $method );
    $signature  = self::analyzeDocComment( $docComment );
    return $signature['rpctest'];
  }

  /**
   * Returns the json data to create a complete test case for all the methods
   * of a class as an string that evaluates to an object.
   * @return string
   */
  public function method_rpcConsoleClassTestJson()
  {
    return "({" . $this->rpcConsoleClassTestJson() . "})";
  }

  /**
   * Returns the json data to create a complete test case for all the methods
   * of a class as a string without the surrounding curly brackets, so it
   * can be concatenated with other json fragments.
   *
   * @return string
   */
  public function rpcConsoleClassTestJson()
  {
    $serviceIntrospection = new ServiceIntrospection( $this );
    $methods = $serviceIntrospection->method_listMethods();
    $testJsonArr = array();
    foreach( $methods as $method )
    {
      $docComment = $serviceIntrospection->getDocComment( $method );
      $signature  = self::analyzeDocComment( $docComment );
      $testJson = trim( $signature['rpctest'] );
      $service = $serviceIntrospection->getServiceName();
      $json = null;

      if ( $testJson == "OK" )
      {
        $json =  sprintf(
          '"%s.%s" : { "requestData":{"service":"%s","method":"%s","timeout":30},"checkResult":"OK"}',
          $service,$method,$service,$method
        );
      }
      elseif( $testJson )
      {
        $json = '"' . $service . "." . $method . '": ' . $testJson;
      }

      if ( $json )
      {
        /*
         * do a basic syntax check on the javascript
         * @todo find a PHP script that does lint and pretty print
         * on javascript
         */
        $problem =
          substr_count( $json, "{" ) != substr_count( $json, "}" )
          || substr_count( $json, "[" ) != substr_count( $json, "]" );
        if( $problem )
        {
          $this->warn( sprintf(
            "There is a problem with the rpc test data in class '%s', method '%s'",
            $this->className(), $method
          ));
        }
        else
        {
          $testJsonArr[] = $json;
        }
      }
    }
    return implode( ",\n", $testJsonArr );
  }

  /**
   * Assert that both values are equal. (Uses the equality operator
   * <code>==</code>.)
   *
   * @param mixed $expected Reference value
   * @param mixed $found found value
   * @param string $msg |null Message to be shown if the assertion fails.
   *  Defauts to "Values are not equal."
   * @param string|null $class name of the class. Pass the __CLASS__ constant here.
   * @param string|null $line line number. Pass the __LINE__ constant here.
   * @throws qcl_test_AssertionException
   * @return boolean true If values are equal
   */
  public function assertEquals( $expected, $found, $msg=null, $class=null, $line=null )
  {
    if ( $expected == $found ) return true;
    if ( $msg === null ) {
      $msg = sprintf(
        "Values are not equal: Expected '%s', found '%s'",
        qcl_toString($expected), qcl_toString($found)
      );
    }
    if ( $class === null ) $class = "Unknown class"; //FIXME
    if ( $line === null )  $line  = "Unknown line"; // FIXME
    throw new qcl_test_AssertionException( "$msg ($class:$line)" );
  }

  /**
   * Assert that value is boolean true.
   *
   * @param mixed $value
   * @param string $msg |null Message to be shown if the assertion fails.
   *  Defaults to "Failed."
   * @param string|null $class name of the class. Pass the __CLASS__ constant here.
   * @param string|null $line line number. Pass the __LINE__ constant here.
   * @throws qcl_test_AssertionException
   * @return boolean true If values are equal
   */
  public function assertTrue( $value, $msg="Failed.", $class=null, $line=null )
  {
    if ( $value === true ) return true;
    if ( $class === null ) $class = "Unknown class";
    if ( $line === null )  $line  = "Unknown line";
    throw new qcl_test_AssertionException( "$msg ($class:$line)" );
  }

  /**
   * Records a timestamp for this object
   * @return unknown_type
   */
  public function startTimer()
  {
    $this->timestamp = microtime_float();
  }

  /**
   * Returns the time since startTimer() was called in seconds
   * @return float
   */
  public function timerAsSeconds()
  {
    $time_end = microtime_float();
    $seconds = round($time_end - $this->timestamp,5);
    return $seconds;
  }

}

/**
 * add capability
 */
/** @noinspection PhpIncludeInspection */
require_once "qcl/lib/rpcphp/server/services/System.php";

class_System::getInstance()->addCapability(
  "rpctest",
  "http://qooxdoo.org/documentation/json_rpc_introspection",
  "0.1",
  array(),
  array("rpcConsoleMethodTestJson","rpcConsoleClassTestJson")
);
?>