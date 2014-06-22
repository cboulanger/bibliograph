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
    
    foreach( get_class_methods( get_class( $this ) ) as $method )
    {
      if ( strpos( $method, "test_" ) !== 0 ) continue;
      echo "$lb - Calling $method()";
      $this->$method();
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
    echo "$lb - Done.";
    echo "$lb";
  }
  
  /**
   * The handler function called when an assert fails
   */
  protected function assert_handler($file, $line, $code, $description)
  {
    $this->failed = true;
    echo $this->lb . "  !!! line $line: $description";
  }
}