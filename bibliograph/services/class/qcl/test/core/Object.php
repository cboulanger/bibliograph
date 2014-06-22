<?php
/*
 * qooxdoo - the new era of web development
 *
 * http://qooxdoo.org
 *
 * Copyright:
 *   2007-2010 Christian Boulanger
 *
 * License:
 *   LGPL: http://www.gnu.org/licenses/lgpl.html
 *   EPL: http://www.eclipse.org/org/documents/epl-v10.php
 *   See the LICENSE file in the project's top-level directory for details.
 *
 * Authors:
 *  * Christian Boulanger (cboulanger)
 */
 
require_once dirname(__DIR__)."/bootstrap.php";
qcl_import("qcl_test_TestRunner");

class TestObject extends qcl_core_Object
{
  public $foo = "foo";

  public $bar;

  public $baz;

  private $boo = "boo";

}

class qcl_test_core_Object
  extends qcl_test_TestRunner
{

  public function test_testObjectId()
  {
    $this->info("Testing object id creation.");
    $time_start =  microtime_float();
    $obj = new qcl_core_Object;
    $id = $obj->objectId();
    $time_end = microtime_float();
    $time = $time_end - $time_start;
    $this->info( "It took $time seconds to generate object id $id" );
  }

  public function test_testPropertyBehavior()
  {
    $obj = new TestObject();

    /*
     * testing initial values
     */
    assert( '"foo"==$obj->getFoo()');

    /*
     * trying to set a non-accessible property
     */
    try
    {
      assert( '"boo"==$obj->getBoo()' );
    }
    catch( qcl_core_PropertyBehaviorException $e ){}

    /*
     * setting mulitple properties at the same time
     */
    $obj->set(array(
      'foo' => "foo2",
      'bar' => "bar",
      'baz' => "baz"
    ));
    assert('"bar"==$obj->getBar()');

    /*
     * using setFoo acccess
     */
    $obj->setBaz("bla");
    assert('"bla"==$obj->getBaz()');
  }
}

qcl_test_core_Object::getInstance()->run();