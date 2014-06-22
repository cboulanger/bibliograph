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
qcl_import( "qcl_data_model_PersistentModel" );

class persist_TestModel
  extends qcl_data_model_PersistentModel
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
    $this->addListener( "changeFoo", $this, "_onChangeFoo" );
    $this->addProperties( $this->properties );

    parent::__construct();
  }

  function init()
  {
    parent::init();
    if ( $this->isNew() )
    {
      $this->set("created", new qcl_data_db_Timestamp("2010-03-17 14:20:53") );
    }
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
class qcl_test_data_model_PersistentModel
  extends qcl_test_TestRunner
{

  public function test_testModel()
  {
    $this->startLogging();

    $this->info("*** Creating and saving data ... ");
    $model = new persist_TestModel();
    $model->setFoo("abcdef");
    $model->savePersistenceData();

    $this->info("*** Deleting and recreating model.. ");
    $model = null;
    $model = new persist_TestModel();
    assert("abcdef",$model->getFoo() );

    $this->info("*** Disposing data and recreating model.. ");
    $model->disposePersistenceData();
    $model = null;
    $model = new persist_TestModel();
    assert("foo",$model->getFoo() );

    $this->info("*** Disposing data and deleting model.. ");
    $model->disposePersistenceData();
    unset($model);

    return "OK";
  }

  private function startLogging()
  {
    qcl_log_Logger::getInstance()->setFilterEnabled(QCL_LOG_PERSISTENCE,true);
  }

  private function endLogging()
  {
    qcl_log_Logger::getInstance()->setFilterEnabled(QCL_LOG_PERSISTENCE,false);
  }
}

?>