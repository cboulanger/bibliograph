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
qcl_import( "qcl_test_TestRunner" );
qcl_import( "qcl_core_PersistentObject" );

class PersistentTestObject
  extends qcl_core_PersistentObject
{
  public $counter = 0;

  public $object = null;
}

/**
 * Service class containing test methods
 */
class qcl_test_core_PersistentObject
  extends qcl_test_TestRunner
{

  /**
   * Tests the persistence behavior mechanism by creating a server-side counter.
   *
   * @return object
   * @rpctest {
   *    "requestData" : {
   *      "service" : "qcl.test.core.PersistentObject",
   *      "method" : "testCounter"
   *    },
   *    "execute" : function() {
   *      this.__persistenceCounter = 1;
   *      return true;
   *    },
   *    "checkResult" : function(result) {
   *      var count = result;
   *      if (parseInt(count) == NaN) {
   *        return "Result is not a number";
   *      }
   *      if (this.__persistenceCounter > 0) {
   *        this.__persistenceCounter++;
   *        if (count != this.__persistenceCounter) {
   *          return "Expected: " + this.__persistenceCounter + ", got: "
   *              + count;
   *        }
   *        return true;
   *      } else {
   *        this.__persistenceCounter = count;
   *        return "You need to run the test again to see if it worked";
   *      }
   *    }
   *  }
   */
  public function test_testCounter()
  {
    //$this->startLogging();
    $obj = new PersistentTestObject();
    $obj->counter++;
    $this->info("Count:" . $obj->counter );
    return $obj->counter;
  }

  /**
   * @rpctest OK
   */
  public function test_testPersistentObject()
  {
    //$this->startLogging();

    $obj = new PersistentTestObject();
    $obj->setObject( new DateTime("now") );
    $obj->savePersistenceData(); // this is necessary because __destruct is not called when unsetting object.
    $obj = null;

    $obj = new PersistentTestObject();
    assert('"DateTime"==typeof( $obj->getObject(), true)', "Object member was not persisted.");

    $obj->disposePersistenceData();
    $obj = null;

    $obj = new PersistentTestObject();
    assert('"NULL"==typeof( $obj->getObject() )', "Persistence data was not disposed.");

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

qcl_test_core_PersistentObject::getInstance()->run();