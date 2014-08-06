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

qcl_import( "qcl_test_TestRunner");
qcl_import( "qcl_access_model_User" );


class qcl_test_access_ModelAccessControl
  extends qcl_test_TestRunner
{

  /**
   * Access control list. Determines what role has access to what kind
   * of information.
   * @var array
   */
  private $modelAcl = array(

    /*
     * access to user data
     */
    array(

      'datasource'  => "access",
      'modelType'   => "user",

      /*
       * which roles have generally access to this model?
       * Here: all roles
       */
      'roles'       => "*",

      /*
       * now we set up some rules
       */
      'rules'         => array(

        /*
         * anonymous and user can only read
         */
        array(
          'roles'       => array(
            QCL_ROLE_ANONYMOUS,
            "user"
          ),
          'access'      => array( QCL_ACCESS_READ ),
          'properties'  => array( "allow" => array( NAMED_ID ) )
        ),

        /*
         * admin and manager can also write
         */
        array(
          'roles'       => array(
            "admin",
            "manager"
          ),
          'access'      => array(
            QCL_ACCESS_READ,
            QCL_ACCESS_WRITE,
            QCL_ACCESS_CREATE,
            QCL_ACCESS_DELETE
          ), // equvivalent to QCL_ACCESS_ALL or "*"
          'properties'  => array( "allow" => "*" )
        )
      )
    )
  );

  /**
   * Constructor. Adds model acl
   */
  function __construct()
  {
    parent::__construct();
    $this->addModelAcl( $this->modelAcl );
  }

  /**
   * @rpctest {
   *   "requestData" : {
   *     "service" : "qcl.test.access.ModelAccessControl",
   *     "method"  : "testModel",
   *     "timeout" : 30
   *   },
   *   "checkResult" : "OK"
   * }
   */
  public function test_testModel()
  {
    /*
     * programmatically log in as administrator
     */
    //qcl_log_Logger::getInstance()->setFilterEnabled(QCL_LOG_ACCESS,true);
    qcl_import( "qcl_access_Service" );
    $service = new qcl_access_Service();
    $service->method_authenticate("admin","admin");

    /*
     * read from the "access" datasources
     */
    $where = new stdClass();
    $where->anonymous = false;
    $users = $this->method_fetchValues( "access", "user", "namedId", $where );
    $this->assertTrue( 0 === count( array_diff( $users,array( "user1","user2","user3","admin" ) ) ), "Users are incorrect");
    $query = new stdClass();
    $query->properties = array( NAMED_ID, "name", "password" );
    $query->where = array( "anonymous" => false );
    $data = $this->method_fetchRecords( "access","user", $query );
    $expected =  array (
      array (
        'id'      => 1,
        'namedId' => 'user1',
        'name' => 'User 1',
        'password' => 'user1'
      ),
      array (
        'id'      => 2,
        'namedId' => 'user2',
        'name' => 'User 2',
        'password' => 'user2'
      ),
      array (
        'id'      => 3,
        'namedId' => 'user3',
        'name' => 'User 3',
        'password' => 'user3'
      ),
      array (
        'id'      => 4,
        'namedId' => 'admin',
        'name' => 'Administrator',
        'password' => 'admin'
      )
    );
    assert( print_r( $expected, true), print_r( $data, true));

    /*
     * create new user
     */
    $newUser = new stdClass();
    $newUser->namedId = "user4";
    $newUser->name = "User 4";
    $newUser->password = "user4";
    $this->method_createRecord("access","user", $newUser );
    $userModel = $this->getModel( "access", "user" );
    $userModel->load("user4");
    assert( "user4", $userModel->getPassword());

    /*
     * change user property
     */
    $data = new stdClass();
    $data->email = "foo@bar.com";
    $this->method_updateRecord( "access", "user", "user4", $data );
    $userModel->load("user4");
    assert( $data->email, $userModel->getEmail());

    /*
     * delete user
     */
    $this->method_deleteRecord( "access", "user", "user4" );
    assert( false, $userModel->namedIdExists('user4'));

    /*
     * now logout and try the same
     */
    $service->method_logout();

    /*
     * this should work
     */
    $where = new stdClass();
    $where->anonymous = false;
    $users = $this->method_fetchValues( "access", "user", "namedId", $where );
    $this->assertTrue( 0 === count( array_diff( $users,array( "user1","user2","user3","admin" ) ) ), "Users are incorrect");

    /*
     * the following test must all fail, otherwise there is a failure
     */
    try
    {
      $query = new stdClass();
      $query->properties = array( NAMED_ID, "name", "password" );
      $query->where = array( "anonymous" => false );
      $data = $this->method_fetchRecords( "access","user", $query );
      throw new qcl_test_AssertionException( "Access violation in line" . __LINE__ );
    }
    catch( qcl_access_AccessDeniedException $e)
    {
      $this->info( $e->getMessage() );
    }

    try
    {
      $newUser = new stdClass();
      $newUser->namedId = "user4";
      $newUser->name = "User 4";
      $newUser->password = "user4";
      $this->method_createRecord("access","user", $newUser );
      $userModel = $this->getModel( "access", "user" );
      throw new qcl_test_AssertionException( "Access violation in line" . __LINE__ );
    }
    catch( qcl_access_AccessDeniedException $e)
    {
      $this->info( $e->getMessage() );
    }

    try
    {
      $data = new stdClass();
      $data->email = "foo@bar.com";
      $this->method_updateRecord( "access", "user", "user4", $data );
      throw new qcl_test_AssertionException( "Access violation in line" . __LINE__ );
    }
    catch( qcl_access_AccessDeniedException $e)
    {
     $this->info( $e->getMessage() );
    }

    try
    {
      $this->method_deleteRecord( "access", "user", "user4" );
      throw new qcl_test_AssertionException( "Access violation in line" . __LINE__ );
    }
    catch( qcl_access_AccessDeniedException $e)
    {
      $this->info( $e->getMessage() );
    }

    return "OK";
  }
}
