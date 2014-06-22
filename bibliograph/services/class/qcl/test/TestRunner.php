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
    echo $this->lb . "   (!) " . $msg;
  }

  public function error( $msg )
  {
    echo $this->lb . "   (X) " . $msg;
    echo $this->lb . " - Aborted." . $msg;
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
        $this->error( $e-getMessage() );
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
    echo "$lb - Done.";
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
    $this->failed = true;
    $this->warn( $description );
  }
}