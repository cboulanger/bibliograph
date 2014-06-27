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
qcl_import("qcl_data_model_PersistentModel");

class persist_TestModel
  extends qcl_data_model_PersistentModel
{
  private $properties = array(
    "foo" => array(
      "check"     => "string",
       "init"      => "foo",
      "nullable"  => true
    ),
    "bar"  => array(
      "check"     => "integer",
      "init"      => 1,
      "nullable"  => false
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
    $this->addProperties( $this->properties );
    parent::__construct();
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

    $this->info("Creating and saving data");
    $model = new persist_TestModel();
    $model->setFoo("abcdef");
    $model->savePersistenceData();

    $this->info("Deleting and recreating model...");
    $model = null;
    $model = new persist_TestModel();
    assert('$model->getFoo()=="abcdef"');

    $this->info("Disposing data and recreating model...");
    $model->disposePersistenceData();
    $model = null;
    $model = new persist_TestModel();
    assert('$model->getFoo()==="foo"');

    $this->info("Disposing data and deleting model...");
    $model->disposePersistenceData();
    unset($model);
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

qcl_test_data_model_PersistentModel::getInstance()->run();