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

require_once dirname(dirname(__DIR__)) . "/bootstrap.php";

qcl_import("qcl_test_TestRunner");
qcl_import("qcl_data_model_Model");
qcl_import("qcl_data_db_Timestamp");

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
  

  public function _applyFoo($value, $old)
  {
    qcl_test_data_model_Model::getInstance()->_applyFoo($value, $old);
  }

  public function _applyBar($value, $old)
  {
    qcl_test_data_model_Model::getInstance()->_applyBar($value, $old);
  }

  public function _onChangeFoo( qcl_event_type_DataEvent $e )
  {
    qcl_test_data_model_Model::getInstance()->_onChangeFoo( $e );
  }

}

/**
 * Service class containing test methods
 */
class qcl_test_data_model_Model
  extends qcl_test_TestRunner
{
  private $_applyFooWorked = false;
  private $_applyBarWorked = false;
  private $_onChangeFooWorked = false;
  
  public function test_setupModel()
  {
    $model = new model_TestModel();
    
    $model->init(); // this sets up the property system
    assert('$this->_applyFooWorked===true',"Apply function was not invoked.");
    assert('$this->_applyBarWorked===true',"Apply function was not invoked.");
    assert('$this->_onChangeFooWorked===true',"Change function was not invoked.");
    
    $model->set("created", new qcl_data_db_Timestamp("2010-03-17 14:20:53") );  
    $created = (string) $model->getCreated();
    assert('$created=="2010-03-17 14:20:53"', "Incorrect 'created' value: $crated");
    
    assert('$model->getFoo()=="foo"',"Incorrect 'foo' value");
    assert('gettype( $model->getBar() )=="integer"' );
  }
  
  public function test_setProperties()
  {
    $model = new model_TestModel();
    try
    {
      $model->setBar("boo"); // should raise an error
      $this->warn("Assigning the wrong value type to a property should throw an error!");
    }
    catch( qcl_core_PropertyBehaviorException $e ){
      $this->info("Wrong value detected");    
    }

    // nullable
    $model->setFoo(null);
    assert('$model->getFoo()===null',"Failed to set property to null");

    try
    {
      $model->setBar(null); // should raise an error
      $this->warn("Assigning null to a non-nullable property should throw an error!");
    }
    catch( qcl_core_PropertyBehaviorException $e )
    {
      $this->info("Incorrect null value detected");
    }
  }
  
  public function _applyFoo($value,$old)
  {
    $this->_applyFooWorked = true;
    //$this->info("foo was " . $this->valueToString( $old ) . ", is now " . $this->valueToString( $value )  );
  }
  
  public function _applyBar($value, $old)
  {
    $this->_applyBarWorked = true;
    //$this->info("bar was " . $this->valueToString( $old ) . ", is now " . $this->valueToString( $value ) );
  }
  
  public function _onChangeFoo( qcl_event_type_DataEvent $e )
  {
    $this->_onChangeFooWorked = true;
    //$this->info( "'changeFoo' event tells me that foo was changed to " . $this->valueToString( $e->getData() ) );
  }  
  
  public function valueToString( $value )
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
}

qcl_test_data_model_Model::getInstance()->run();