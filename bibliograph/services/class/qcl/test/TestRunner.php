<?php
/*
 * qcl - the qooxdoo component library
 *
 * http://qooxdoo.org/contrib/project/qcl/
 *
 * Copyright:
 *   2007-2015 Christian Boulanger
 *
 * License:
 *   LGPL: http://www.gnu.org/licenses/lgpl.html
 *   EPL: http://www.eclipse.org/org/documents/epl-v10.php
 *   See the LICENSE file in the project's top-level directory for details.
 *
 * Authors:
 *  * Christian Boulanger (cboulanger)
 */

/**
 * Class qcl_test_TestRunner
 *
 * Extremely simple test class runner. Currently used only for running
 * tests on the command line. Will execute all methods that are prefixed
 * with "test_". Uses "assert()" to check for correct test results. In
 * addition, you can use the warn() method to signal that a test has
 * failed and to inform the developer about the cause of the failure.
 * If a fatal error condition exists, use the error() method to inform
 * about the condition and abort the tests in this class.
 *
 * Each test file must contain a class inheriting from this class and have
 * a line as follows at the end of the file:
 *
 * name_of_class::getInstance()->run();
 *
 * The tests are then run with php -f /path/to/file.php. To batch run tests,
 * use a simple bash file that (recursively) finds all the test files in a
 * directory and runs them.
 *
 */
class qcl_test_TestRunner extends qcl_core_Object
{
  /**
   * Line break character(s)
   */
  protected $lb = "\n";

  /**
   * Flag to indicate whether an assertion failed
   */
  protected $failed = false;

  /**
   * Sets the line break character(s)
   */
  public function setLineBreak( $chars )
  {
    // todo: check
    $this->lb = $chars;
  }

  public function info( $msg )
  {
    echo $this->lb .  "   - " . $msg;
  }

  public function warn( $msg )
  {
    $this->failed = true;
    echo $this->lb . "   (!) " . $msg;
  }

  public function error( $msg )
  {
    echo $this->lb . "   (X) " . $msg;
    echo $this->lb . "(X) Aborted.";
    echo $this->lb;
    $this->tearDown();
    exit();
  }

  /**
   * Run test methods in this class. Test methods must be public and
   * prefixed by "test_" in order to be included in the test.
   */
  public function run()
  {
    assert_options(ASSERT_ACTIVE, 1);
    assert_options(ASSERT_WARNING, 0);
    assert_options(ASSERT_QUIET_EVAL, 1);
    assert_options(ASSERT_CALLBACK, array( $this, 'assert_handler') );

    $lb = $this->lb;

    echo  "$lb>>> Testing Class " . $this->className();
    
    $this->setup();

    foreach( get_class_methods( get_class( $this ) ) as $method )
    {
      if ( strpos( $method, "test_" ) !== 0 ) continue;
      echo "$lb - Calling $method()";
      
      try
      {
        $this->$method();  
      }
      catch( Exception $e )
      {
        $this->getLogger()->error($e->getMessage() . "\n" . $e->getTraceAsString());
        return $this->error( $e->getMessage() );
      }
      
      if ( $this->failed )
      {
        // reset flag
        $this->failed = false;
      }
      else
      {
        echo " √";
      }
    }
    
    $this->tearDown();
    echo "$lb(√) Done.";
    echo "$lb";
  }
  
  /**
   * Method called before the tests are run
   */
  protected function setup(){}
  
  /**
   * Method called after all tests have passed, or after an error occurred.
   */
  protected function tearDown(){}

  /**
   * The handler function called when an assert fails
   */
  protected function assert_handler($file, $line, $code, $description)
  {
    $this->warn( $description . "(assertion in line $line)");
  }
}