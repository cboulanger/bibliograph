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

qcl_import( "qcl_test_TestRunner");
qcl_import( "qcl_data_model_db_ActiveRecord" );
qcl_import( "qcl_data_db_Timestamp" );

class ComplexModel
  extends qcl_data_model_db_ActiveRecord
{

  protected $tableName = "test_complex_model";

  private $properties = array(
    "name" => array(
      "check"     => "string",
      "sqltype"   => "varchar(32)",
      "init"      => "foo"
    ),
    "counter"  => array(
      "check"     => "integer",
      "sqltype"   => "int(11)",
      "init"      => 1,
      "nullable"  => false
    ),
    "flag"  => array(
      "check"     => "boolean",
      "sqltype"   => "int(1)",
      "init"      => true,
      "nullable"  => false
    ),
    "list"  => array(
      "check"     => "array",
      "serialize" => true,
      "sqltype"   => "blob",
      "init"      => array( 1,2,3)
    ),
    "object" => array(
      "check"     => "ArrayList",
      "sqltype"   => "blob",
      "serialize" => true
    )
  );

  function __construct()
  {
    $this->addProperties( $this->properties );
    parent::__construct();
  }
}

/**
 * Service class containing test methods
 */
class qcl_test_data_model_db_ComplexActiveRecord
  extends qcl_test_TestRunner
{

  /**
   * @rpctest OK
   */
  public function test_testModel()
  {
    //$this->startLogging();

    /*
     * creating complex object
     */
    $model = new ComplexModel();
    $model->deleteAll();

    $id = $model->create();
    $model->setList( array(4,5,6) );
    $model->setObject( new ArrayList( array( 7,8,9 ) ) );
    $model->save();

    /*
     * retrieving complex object
     */
    $model->load($id);
    assert(array(4,5,6), $model->getList(), "Array serialization failed.");
    $object = $model->getObject();
    assert("ArrayList", get_class($object), "Object serialization failed.");
    assert(array( 7,8,9 ) , $object->toArray(), "Object serialization failed.");

    /*
     * delete the table
     */
    $model->destroy();

    return "OK";
  }


  function startLogging()
  {
    $this->getLogger()->setFilterEnabled( QCL_LOG_DB, true );
    $this->getLogger()->setFilterEnabled( QCL_LOG_TABLES, true );
  }

  function endLogging()
  {
    $this->getLogger()->setFilterEnabled(array(QCL_LOG_DB,QCL_LOG_TABLES),false);
  }
}

