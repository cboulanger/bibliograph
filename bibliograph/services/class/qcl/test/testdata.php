<?php
/* ************************************************************************

   qcl - the qooxdoo component library

   http://qooxdoo.org/contrib/project/qcl/

   Copyright:
     2007-2010 Christian Boulanger

   License:
     LGPL: http://www.gnu.org/licenses/lgpl.html
     EPL: http://www.eclipse.org/org/documents/epl-v10.php
     See the LICENSE file in the project's top-level directory for details.

   Authors:
     * Chritian Boulanger (cboulanger)

************************************************************************ */

/*
 * check that constants are defined.
 * @todo check if paths exist
 */
if( ! defined( "QCL_TEST_CLASS_DIR" ) )
{
  throw new Exception("QCL_TEST_CLASS_DIR constant must be defined.");
}

if( ! defined( "QCL_TEST_SERVER_DIR" ) )
{
  throw new Exception("QCL_TEST_SERVER_DIR constant must be defined.");
}

/*
 * The parent class that test service classes must extend in order to
 * be included in the test suite
 */
if( ! defined( "QCL_TEST_SERVICE_PARENT_CLASS" ) )
{
  define( "QCL_TEST_SERVICE_PARENT_CLASS" , "qcl_test_AbstractTestController" );
}

/*
 * we're delivering a javascript file
 */
header("Content-Type: text/javascript");

/*
 * urls
 */
$serverUrl =  "http://" . $_SERVER["HTTP_HOST"] .
  dirname( dirname( dirname( dirname( $_SERVER["SCRIPT_NAME"] ) ) ) ) . "/server.php";
$testDataUrl = "http://" . $_SERVER["HTTP_HOST"] . $_SERVER["SCRIPT_NAME"];

/*
 * change the working directory to the server's and load required
 * libraries
 */
chdir( QCL_TEST_SERVER_DIR );
require_once "config.php";
require_once "qcl/bootstrap.php";

/*
 * introduction
 */
echo <<<EOF
/* ************************************************************************

   qcl - the qooxdoo component library

   http://qooxdoo.org/contrib/project/qcl/

   Copyright:
     2007-2010 Christian Boulanger

   License:
     LGPL: http://www.gnu.org/licenses/lgpl.html
     EPL: http://www.eclipse.org/org/documents/epl-v10.php
     See the LICENSE file in the project's top-level directory for details.

   Authors:
     * Christian Boulanger (cboulanger)

   The following file contains data that can be fed into the RpcConsole
   contribution to automatically run all tests contained in the
   subdirectories. Load the RpcConsole with the following GET parameters:

   ?serverUrl=$serverUrl
   &testDataUrl=$testDataUrl

   You can also load the data from the testDataUrl with the "Load/Edit Tests"
   -> "Load test data..." command or copy and paste the data into the editor
   using the "Load/Edit Tests" -> "Edit test data" menu command.

************************************************************************ */

EOF;



/**
 * Recursively collect all files that end with ".php"
 * @param $dir
 * @return array
 */
function getServiceClassFiles( $dir = QCL_TEST_CLASS_DIR )
{
  $serviceClasses = new ArrayList();
  foreach( scandir( $dir ) as $file )
  {
    $path = $dir . "/" . $file;
    if (  $file[0] == "." ) continue;
    if ( is_dir( $path) )
    {
      $serviceClasses->addAll( getServiceClassFiles( $path ) );

    }
    elseif ( get_file_extension( $file ) == "php" )
    {
      $serviceClasses->add( $path );
    }
  }
  return $serviceClasses->toArray();
}

/** @noinspection PhpIncludeInspection */
require_once "qcl/lib/rpcphp/server/JsonRpcServer.php";

$tests = array();
$files = getServiceClassFiles();
$packages = array();
$packageCount = 0;

echo <<<EOF
qx.core.Init.getApplication().setTestData(
{
EOF;

foreach( $files as $path )
{
  /*
   * package
   */
  $packageDir = dirname( $path );
  $package = str_replace( "/",".", substr( $packageDir, strlen(QCL_TEST_CLASS_DIR) +1 ) ) . ".*";


  $file_content = file_get_contents( $path );
  if ( strstr( $file_content, "extends ". QCL_TEST_SERVICE_PARENT_CLASS) )
  {
    if ( ! isset( $packages[$package] ) )
    {
      $packageCount++;
      echo <<<EOF

      "runPackage{$packageCount}":{
        "label":"Execute $package test suite",
        "execute":function (){
          this.info( "Starting test suite $package ");
          this.runTests("$package");
        }
      },

EOF;
      $packages[$package] = true;
    }

    /*
     * convert path into class name
     */
    $import_class = substr(
       str_replace("/","_", substr( $path, strlen(QCL_TEST_CLASS_DIR) +1 ) ), 0, -4
    );
    qcl_import( $import_class );

    $className = JsonRpcClassPrefix . $import_class;
    $class = new $className;

    $testJson = $class->rpcConsoleClassTestJson();

    $testJson = implode("\n  ",explode("\n",$testJson) );


    if ( $testJson != "" )
    {
      $test = "\n";
      $test .= "  //=================================================================\n";
      $test .= "  // Class $className \n";
      $test .= "  //=================================================================\n";
      $test .= "  " . $testJson;
      $tests[] = $test;
    }

  }
}
echo implode(",\n",$tests) . "\n});";


?>