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
qcl_import( "qcl_data_model_db_NamedActiveRecord" );
qcl_import( "qcl_data_db_Timestamp" );
qcl_import( "qcl_data_datasource_DbModel" );
qcl_import( "qcl_data_datasource_Manager" );

/**
 * This is the datasource that holds the information
 * for the model classes.
 */
class ds_Addressbook
  extends qcl_data_datasource_DbModel
{
  /**
   * When called for the first time, register the connected models.
   * This cannot be done in the constructor, because the datasource
   * model must exists before the models are registered.
   *
   * @return boolean True if initialization has to be done in the subclass,
   *   false if object was already initialized earlier.
   */
  public function init()
  {
    /*
     * register the model classes when called the first timethe following pattern to register the models.
     */
    if ( parent::init() )
    {
      $this->registerModels( array(
        'person' => array( 'class' => "ds_Person" ),
        'group'  => array( 'class' => "ds_Group" ),
        'tag'    => array( 'class' => "ds_Tag" )
      ) );
      return true;
    }
    return false;
  }

  /**
   * @return ds_Addressbook
   */
  public static function getInstance()
  {
    return qcl_getInstance( __CLASS__ );
  }


  /**
   * Returns the "person" model
   * @return ds_Person
   */
  public function getPersonModel()
  {
    return $this->getModelOfType("person");
  }

  /**
   * Returns the "group" model
   * @return ds_Group
   */
  public function getGroupModel()
  {
    return $this->getModelOfType("group");
  }

 /**
   * Returns the "tag" model
   * @return ds_Tag
   */
  public function getTagModel()
  {
    return $this->getModelOfType("tag");
  }

}

class ds_AddressbookManager
  extends qcl_data_datasource_Manager
{
  /**
   * @return ds_AddressbookManager
   */
  static public function getInstance()
  {
    return qcl_getInstance( __CLASS__ );
  }

  /**
   * @return ds_Addressbook
   */
  public function getDatasourceModel()
  {
    return ds_Addressbook::getInstance();
  }
}

class ds_Person
  extends qcl_data_model_db_NamedActiveRecord
{

  // ... would have properties, and relations to the other models ...
}

class ds_Group
  extends qcl_data_model_db_NamedActiveRecord
{
  // ... would have properties, and relations to the other models ...
}

class ds_Tag
  extends qcl_data_model_db_NamedActiveRecord
{
  // ... would have properties, and relations to the other models ...
}

/**
 * Service class containing test methods
 */
class qcl_test_data_datasource_DatasourceTest
  extends qcl_test_TestRunner
{
  /**
   * @rpctest OK
   */
  public function test_testModel()
  {
    qcl_data_model_db_ActiveRecord::resetBehaviors();

    //$this->startLogging();

    $dsManager = ds_AddressbookManager::getInstance();
    $dsCount = count( $dsManager->datasources() );

    try
    {

      /*
       * register the "addressbook" schema
       */
      $dsManager->registerSchema( "addressbook", array(
        "class"       => "ds_Addressbook",
        "description" => "A schema for addressbooks that have a person, group and tag model"
      ) );

      /*
       * create a new addressbook datasource
       */
      $addressbook1 = $dsManager->createDatasource( "my_addressbook", "addressbook");

      /*
       * create model data
       */
      $person1 = $addressbook1->getPersonModel();
      $group1  = $addressbook1->getGroupModel();
      $tag1    = $addressbook1->getTagModel();

      $person1->create("Peter");
      $person1->create("Paul");
      $person1->create("Mary");

      $group1->create("Work");
      $group1->create("Personal");
      $group1->create("Leisure");

      $tag1->create("foo");
      $tag1->create("bar");

      /*
       * create a second addressbook datasource that is stored in the
       * same database and create some models that depend on it
       */
      $addressbook2 = $dsManager->createDatasource( "meine_adressen", "addressbook");

      $person2 = $addressbook2->getPersonModel();
      $group2  = $addressbook2->getGroupModel();
      $tag2    = $addressbook2->getTagModel();

      $person2->create("Monika");
      $person2->create("Fritz");
      $person2->create("Ingrid");

      $group2->create("Arbeit");
      $group2->create("Freizeit");
      $group2->create("Familie");

      $tag2->create("dies");
      $tag2->create("das");

      /*
       * some logging
       */
      $this->info( sprintf(
        "'%s' is in addressbook '%s'",
        $person1->namedId(), $person1->datasourceModel()->namedId()
      ) );

      $this->info( sprintf(
        "'%s' is in addressbook '%s'",
        $person2->namedId(), $person2->datasourceModel()->namedId()
      ) );

      /*
       * testing
       */
      $this->assertTrue( $addressbook1 === $dsManager->getDatasourceModelByName( "my_addressbook") );
      $this->assertTrue( $addressbook2 === $dsManager->getDatasourceModelByName( "meine_adressen") );

      assert( $dsCount + 2, count( $dsManager->datasources() ) );

      $addressbook1->delete();
      assert( $dsCount + 1, count( $dsManager->datasources() ) );

      $dsManager->deleteDatasource( "meine_adressen" );
      assert( $dsCount, count( $dsManager->datasources() ) );

      /*
       * unregister schema and destruction of data
       */
      $dsManager->unregisterSchema( "addressbook", true );

    }
    catch( qcl_data_model_RecordExistsException $e )
    {
      $this->warn( $e );
      $dsManager->unregisterSchema( "addressbook", true );
      throw new JsonRpcException("Test failed ... cleaning up");
    }
    return "OK";
  }


  function startLogging()
  {
    $this->getLogger()->setFilterEnabled( QCL_LOG_DATASOURCE, true );
//    $this->getLogger()->setFilterEnabled( QCL_LOG_MODEL, true );
//    $this->getLogger()->setFilterEnabled( QCL_LOG_OBJECT, true );
//    $this->getLogger()->setFilterEnabled( QCL_LOG_PROPERTIES, true );
//    $this->getLogger()->setFilterEnabled( QCL_LOG_MODEL_RELATIONS, true );
//    $this->getLogger()->setFilterEnabled( QCL_LOG_DB, true );
//    $this->getLogger()->setFilterEnabled( QCL_LOG_TABLES, true );

  }

  function endLogging()
  {
    $this->getLogger()->setFilterEnabled( QCL_LOG_OBJECT, false );
    $this->getLogger()->setFilterEnabled( QCL_LOG_DATASOURCE, false );
    $this->getLogger()->setFilterEnabled( QCL_LOG_DB, false );
    $this->getLogger()->setFilterEnabled( QCL_LOG_TABLES, false );
    $this->getLogger()->setFilterEnabled( QCL_LOG_MODEL, true );
    $this->getLogger()->setFilterEnabled( QCL_LOG_PROPERTIES, false );
    $this->getLogger()->setFilterEnabled( QCL_LOG_MODEL_RELATIONS, false );
  }
}

?>