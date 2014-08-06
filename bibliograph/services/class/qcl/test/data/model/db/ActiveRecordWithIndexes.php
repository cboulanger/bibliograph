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

class indexed_Customer
  extends qcl_data_model_db_ActiveRecord
{

  protected $tableName = "test_customers";

  private $properties = array(
    "customerId" => array(
      "check"     => "integer",
      "sqltype"   => "int(11)",
      "column"   => "customer_id"
    ),
    "orderId"  => array(
      "check"     => "integer",
      "sqltype"   => "int(11)",
      "column"    => "order_id"
    ),
    "productId" => array(
      "check"     => "integer",
      "sqltype"   => "int(11)",
      "column"    => "product_id"
    ),
  );

  private $indexes = array(
    "process_index" => array(
      "type"        => "unique",
      "properties"  => array("customerId","orderId","productId")
    )
  );

  function __construct()
  {
    parent::__construct();
    $this->addProperties( $this->properties );
    $this->addIndexes( $this->indexes );
  }
}

/**
 * Service class containing test methods
 */
class qcl_test_data_model_db_ActiveRecordWithIndexes
  extends qcl_test_TestRunner
{
  /**
   * @rpctest OK
   */
  public function test_testModel()
  {
    //$this->startLogging();

    $customer = new indexed_Customer();
    $customer->deleteAll();

    $customer->create( array(
      'customerId' => 1,
      'orderId'    => 2,
      'productId'  => 3
    ) );

    $customer->create( array(
      'customerId' => 2,
      'orderId'    => 2,
      'productId'  => 3
    ) );

    try
    {
      $customer->create( array(
        'customerId' => 1,
        'orderId'    => 2,
        'productId'  => 3
      ) );
    }
    catch( PDOException $e )
    {
      $this->info("Caught PDO Exception");
    }
    $this->endLogging();

    $customer->destroy();

    return "OK";
  }


  function startLogging()
  {
    //$this->getLogger()->setFilterEnabled( QCL_LOG_DB, true );
    $this->getLogger()->setFilterEnabled( QCL_LOG_TABLES, true );
    $this->getLogger()->setFilterEnabled( QCL_LOG_PERSISTENCE, true );
  }

  function endLogging()
  {
    $this->getLogger()->setFilterEnabled(array(QCL_LOG_DB,QCL_LOG_TABLES),false);
  }
}

