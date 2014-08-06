<?php
/*
 * qcl - the qooxdoo component library
 *
 * http://qooxdoo.org/contrib/project/qcl/
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

qcl_import( "qcl_data_model_db_ActiveRecord" );
qcl_import( "qcl_data_datasource_DbModel" );
qcl_import( "qcl_test_TestRunner");

/**
 * Model for sensitive personal data that should not get into the
 * wrong hands
 */
class acl_PersonalDataModel
  extends qcl_data_model_db_ActiveRecord
{

  private $properties = array(
    "owner"  => array(
      "check"     => "string",
      "sqltype"   => "varchar(30)"
    ),
    "type"  => array(
      "check"     => "string",
      "sqltype"   => "varchar(50)"
    ),
    "data"  => array(
      "check"     => "string",
      "sqltype"   => "varchar(100)"
    ),
    "password"  => array(
      "check"     => "string",
      "sqltype"   => "varchar(100)"
    ),
    "admindata"  => array(
      "check"     => "string",
      "sqltype"   => "varchar(100)"
    )
  );

  function __construct()
  {
    $this->addProperties( $this->properties );
    parent::__construct();
  }
}



/**
 * model for bibliograph datasources based on an sql database
 */
class acl_DatasourceModel
  extends qcl_data_datasource_DbModel
{

  protected $schemaName = "personaldata";

  /**
   * Returns singleton instance of this class.
   * @return acl_DatasourceModel
   */
  public static function getInstance()
  {
    return qcl_getInstance( __CLASS__ );
  }

  /**
   * Initialize the datasource, registers the models
   */
  public function init()
  {
    if ( parent::init() )
    {
      $this->registerModels( array(
        'personaldata'   => array( "class" => "acl_PersonalDataModel"),
      ) );
    }
  }
}

class qcl_test_data_model_db_ModelAccessControl
  extends qcl_test_TestRunner
{

  /**
   * Model access control list. Determines what role has access to what kind
   * of information in the model.
   * @var array
   */
  private $modelAcl = array(

    array(
      /*
       * datasource and model type
       */
      'datasource'  => "acl_test",
      'modelType'   => "personaldata",

      /*
       * now we set up some rules
       */
      'rules'         => array(

        /*
         * anonymous and other roles can only read the properties
         * "id", "owner", "type", "data"
         */
        array(
          'roles'       => "*",
          'access'      => array( QCL_ACCESS_READ ),
          'properties'  => array( "allow" => array( "id", "owner", "type", "data" ) )
        ),

        /*
         * anonymous and other roles can write the "data" property
         * since it is also protected on the record-level
         */
        array(
          'roles'       => "*",
          'access'      => array( QCL_ACCESS_WRITE ),
          'properties'  => array( "allow" => array( "data" ) )
        ),

        /*
         * admin can read and write the properties "id", "owner", "type", "data",
         * "admindata" and create and delete records
         */
        array(
          'roles'       => array( QCL_ROLE_ADMIN ),
          'access'      => "*",
          'properties'  => array( "allow" => array( "id", "owner", "type", "data", "admindata" ) )
        ),

        /*
         * admin can also update the password
         */
        array(
          'roles'       => array( QCL_ROLE_ADMIN ),
          'access'      => array( QCL_ACCESS_WRITE ),
          'properties'  => array( "allow" => array( "password" ) )
        )
      )
    )
  );

  /**
   * Record access control list. Determines access to individual
   * result rows based on specific rules
   *
   * @var array
   */
  private $recordAcl = array(


    array(
      /*
       * determine the datasource and model type
       */
      'datasource'  => "acl_test",
      'modelType'   => "personaldata",

      /*
       * now we set up some rules
       */
      'rules'         => array(

        /*
         * callback method which is called with the record
         * data and which returns true or false
         */
        array(
          'callback'   => "checkOwner"
        )
      )
    )
  );


  /**
   * Constructor. Adds model and record acl
   */
  function __construct()
  {
    parent::__construct();
    $this->addModelAcl( $this->modelAcl );
    $this->addRecordAcl( $this->recordAcl );
  }

  /**
   * @rpctest {
   *   "requestData" : {
   *     "service" : "qcl.test.data.model.db.ModelAccessControl",
   *     "method"  : "testModel",
   *     "timeout" : 30
   *   },
   *   "checkResult" : "OK"
   * }
   */
  public function test_testModel()
  {

    //qcl_log_Logger::getInstance()->setFilterEnabled(QCL_LOG_MODEL,true);

    $dsManager = $this->getDatasourceManager();

    try
    {
      $dsManager->registerSchema( "acl_test", array(
        'class' => "acl_DatasourceModel"
      ) );
      $dsModel = $dsManager->createDatasource("acl_test","acl_test");

      /*
       * fill personaldata model with some data
       */
      $model = $dsModel->getModelOfType("personaldata");
      $model->deleteAll();

      $people = array( "Susi", "John", "Helmut", "Akiko", "Achmed", "Sonja", "Olaf");
      $types = array( "Secret Diary","Password list", "Personal emails", "Health records" );
      foreach( $people as $person )
      {
        foreach( $types as $type )
        {
          $model->create( array(
            "owner"     => $person,
            "type"      => $type,
            "data"      => "$person's $type. Only to be viewed by $person!",
            "password"  => "Super secret password. This should NEVER be visible!",
            "admindata" => "This is data that only the admin is allowed to see."
          ) );
        }
      }

      /*
       * as anonymous, get properties "owner","type","data" for the user
       * "Sonja" and "Helmut", which should return only their "own" records
       */
      $queryData = new stdClass();
      $queryData->properties = array( "owner","type","data" );
      $this->setPerson( "Sonja" );
      $result =  $this->method_fetchRecords( "acl_test", "personaldata", $queryData );
      $hash = md5( print_r( $result, true) );
      //$this->info(  $hash ) );
      assert("fee6e7991d41e3fba67d54e8422104da",$hash,null,__CLASS__,__LINE__);

      $this->setPerson( "Helmut" );
      $result =  $this->method_fetchRecords( "acl_test", "personaldata", $queryData );
      $hash = md5( print_r( $result, true) );
      //$this->info( $hash );
      assert("d7f92dcda8029c87180c2396efc263de",$hash,null,__CLASS__,__LINE__);

      /*
       * a non-existent user must return an empty array
       */
      $this->setPerson( "I don't exist" );
      $result =  $this->method_fetchRecords( "acl_test", "personaldata", $queryData );
      assert( array(),$result,null,__CLASS__,__LINE__);

      /*
       * Akiko wants to get her password list:
       * change the person and add a where condition to the query
       */
      $this->setPerson( "Akiko" );
      $queryData->where = array(
        'type'  => $types[1],
        'owner' => "Akiko"
      );

      /*
       * also, we add the "id" property to be retrieved to the query
       */
      $queryData->properties[] = "id";

      /*
       * run query and get id of the record
       */
      $result =  $this->method_fetchRecords( "acl_test", "personaldata", $queryData );
      $id = $result[0]['id'];
      assert( 14, $id, null);

      /*
       * now she knows the id, she can directly access the value
       */
      $data = $this->method_getValue( "acl_test", "personaldata", $id, "data" );
      assert( "Akiko's Password list. Only to be viewed by Akiko!", $data, null);

      /*
       * she can also change this data
       */
      $this->method_setValue("acl_test", "personaldata", $id, "data", "new data" );
      $data = $this->method_getValue( "acl_test", "personaldata", $id, "data" );
      assert( "new data", $data, null);

      /*
       * now Susi tries to read Akiko's password list!!!
       */
      $this->setPerson( "Susi" );
      try
      {
        $data = $this->method_getValue( "acl_test", "personaldata", $id, "data" );
        throw new qcl_test_AssertionException( "Evil Susi was successful!" );
      }
      catch( qcl_access_AccessDeniedException $e)
      {
        /*
         * no way!!
         */
        $this->info("Thwarted Susi's evil attempt to steal Akiko's data!");
      }

      /*
       * Poor Akiko! Now the fiendish Olaf tries to overwrite her passwords!
       */
      $this->setPerson( "Olaf" );
      try
      {
        $this->method_setValue("acl_test", "personaldata", $id, "data", "olaf's handcrafted computer virus, hehehe!" );
        throw new qcl_test_AssertionException( "Uh oh, Olaf did something very bad." );
      }
      catch( qcl_access_AccessDeniedException $e)
      {
        /*
         * But qcl comes to the rescue!
         */
        $this->info("Take that, Olaf! - Punch - ");
      }

      /*
       * now check that anonymous, whatever the record-level acl,
       * cannot read a property, but admin can
       */
      $this->setPerson( "Akiko" );
      try
      {
        $data = $this->method_getValue( "acl_test", "personaldata", $id, "admindata" );
        throw new qcl_test_AssertionException( "Access violation on line " . __LINE__ );
      }
      catch( qcl_access_AccessDeniedException $e)
      {
        $this->info("No, Akiko, you cannot read secret stuff the admin wrote about you...");
      }

      /*
       * now the admin logs in and accessed the same data successfully
       */
      qcl_import( "qcl_access_Service" );
      $service = new qcl_access_Service();
      $service->method_authenticate("admin","admin");
      $data = $this->method_getValue( "acl_test", "personaldata", $id, "admindata" );
      $this->info( $data );
    }
    catch( Exception $e )
    {
      $this->info( "Test failed, cleaning up..." );
      $dsManager->unregisterSchema( "acl_test", true );
      throw $e;
    }

    /*
     * Success!
     */
    $this->info( "Test succeeded, cleaning up..." );
    $dsManager->unregisterSchema( "acl_test", true );

    return "OK";
  }

  /**
   * Store the currently requesting person
   * @param $person
   * @return void
   */
  protected function setPerson( $person )
  {
    $this->person = $person;
  }

  /**
   * Callback method to check record-level access
   *
   * @param string $datasource
   * @param string $modelType
   * @param string $record
   * @return boolean
   */
  protected function checkOwner( $datasource, $modelType, $record )
  {
    return $this->person == $record['owner'];
  }
}
