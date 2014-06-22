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

qcl_import( "qcl_test_TestRunner" );
qcl_import( "qcl_data_model_Model" );

class model_TestModel
  extends qcl_data_model_Model
{
  private $properties = array(
    "foo" => array(
      "check"     => "string",
      "apply"     => "_applyFoo",
      "init"      => "foo",
      "nullable"  => true,
      "event"     => "changeFoo"
    ),
    "bar"  => array(
      "check"     => "integer",
      "init"      => 1,
      "nullable"  => false,
      "apply"     => "_applyBar"
    ),
    "baz"  => array(
      "check"     => "boolean",
      "init"      => true,
      "nullable"  => false
    ),
    "created" => array(
      "check"    => "DateTime",
      "nullable" => true
    )
  );

  function __construct()
  {
    /*
     * listeners and properties must be declared BEFORE calling the parent constructor
     */
    $this->addListener( "changeFoo", $this, "_onChangeFoo" );
    $this->addProperties( $this->properties );

    parent::__construct();
  }

  function init()
  {
    parent::init();
    $this->set("created", new qcl_data_db_Timestamp("2010-03-17 14:20:53") );
  }

  private function valueToString( $value )
  {
    if ( is_scalar( $value ) )
    {
      return "'$value' (" . gettype( $value ) . ")";
    }
    else
    {
      return typeof( $value, true );
    }
  }

  public function _applyFoo($value, $old)
  {
    $this->info("foo was " . $this->valueToString( $old ) . ", is now " . $this->valueToString( $value )  );
  }

  public function _applyBar($value, $old)
  {
    $this->info("bar was " . $this->valueToString( $old ) . ", is now " . $this->valueToString( $value ) );
  }

  public function _onChangeFoo( qcl_event_type_DataEvent $e )
  {
    $this->info( "'changeFoo' event tells me that foo was changed to " . $this->valueToString( $e->getData() ) );
  }

}

/**
 * Service class containing test methods
 */
class qcl_test_data_model_Model
  extends qcl_test_TestRunner
{
  public function test_testModel()
  {
    $model = new model_TestModel();

    assert('\1');
    assert('\1');
    assert('\1');

    try
    {
      $model->setBar("boo"); // should raise an error
      throw new qcl_test_AssertionException("Assigning the wrong value type to a property should throw an error!");
    }
    catch( qcl_core_PropertyBehaviorException $e ){}

    // nullable
    $model->setFoo(null);
    assert('\1');

    try
    {
      $model->setBar(null); // should raise an error
      throw new qcl_test_AssertionException("Assigning null to a non-nullable property should throw an error!");
    }
    catch( qcl_core_PropertyBehaviorException $e ){}

    return "OK";
  }
}

?>