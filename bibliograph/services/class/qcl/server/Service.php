<?php
/*
 * qcl - the qooxdoo component library
 *
 * http://qooxdoo.org/contrib/project/qcl/
 *
 * Copyright:
 *   2007-2014 Christian Boulanger
 *
 * License:
 *   LGPL: http://www.gnu.org/licenses/lgpl.html
 *   EPL: http://www.eclipse.org/org/documents/epl-v10.php
 *   See the LICENSE file in the project's top-level directory for details.
 *
 * Authors:
 *  * Christian Boulanger (cboulanger)
 */

qcl_import("qcl_core_Object");

/**
 * The base class for all service classes. Contains introspection methods
 * similar to those in xmlrpc (see http://xmlrpc-c.sourceforge.net/introspection.html)
 */
class qcl_server_Service
  extends qcl_core_Object
{

  /**
   * Whether the request has been aborted
   */
  var $_isAborted = false;

  /**
   * constructor , configures the service
   */
  function __construct()
  {

    /*
     * call parent constructor first
     */
    parent::__construct();

    /*
     * configure service
     */
    $this->configureService();

  }

  /**
   * Returns the server object
   * @return qcl_server_Server
   */
  function getServerInstance()
  {
    return qcl_server_Server::getInstance();
  }

  /**
   * Configures the service. Stub to be overridden
   **/
  function configureService(){}

  /**
   * Returns a configuration value of the pattern "foo.bar.baz"
   * This retrieves the values set in the service.ini.php file.
   */
  function getIniValue($path)
  {
    return $this->getApplication()->getIniValue($path);
  }

  /**
   * Abort the request without throwing an error
   * @return void
   */
  function abortRequest()
  {
    $this->_isAborted = true;
  }

  /**
   * Whether the request has been aborted by the service code
   * @return bool
   */
  function isAborted()
  {
    return $this->_isAborted;
  }

  //-------------------------------------------------------------
  // shorthand methods to get rpc request parameters
  //-------------------------------------------------------------

  /**
   * Returns the service name as stated in the json-rpc request
   * @return string
   */
  public function serviceName()
  {
    return qcl_server_Request::getInstance()->getService();
  }

  /**
   * Returns the service method as stated in the json-rpc request
   * @return string
   */
  public function serviceMethod()
  {
    return qcl_server_Request::getInstance()->getMethod();
  }

  /**
   * Returns the service parameters as stated in the json-rpc request
   * @return string
   */
  public function serviceParams()
  {
    return qcl_server_Request::getInstance()->getParams();
  }

  //-------------------------------------------------------------
  // methods to pass data between service methods
  //-------------------------------------------------------------


  /**
   * Temporarily stores the supplied arguments on the server for retrieval
   * by another service method. This storage is only guaranteed to last during
   * the current session and is then discarded.
   * @param mixed $varargs
   *    The method can take a variable number of arguments
   * @return string
   *    The shelve id needed to retrieve the data later
   */
  public function shelve($varargs)
  {
    $shelveId = md5(microtime_float());
    $_SESSION[$shelveId]=func_get_args();
    return $shelveId;
  }

  /**
   * Retrieve the data stored by the shelve() method.
   * @param $shelveId
   *    The id of the shelved data
   * @param bool $keepCopy
   *    If true, the data will be preserved and can be retrieved again.
   *    If false or omitted, the data will be deleted.
   * @return array
   *    Returns an array of the elements passed to the shelve() method, which can be
   *    extracted with the list() method.
   */
  public function unshelve( $shelveId, $keepCopy=false )
  {
    $args =  $_SESSION[$shelveId];
    if ( !$keepCopy )
    {
      unset( $_SESSION[$shelveId] );
    }
    return $args;
  }

  //-------------------------------------------------------------
  // Introspection API
  //-------------------------------------------------------------

  /**
   * @see ServiceIntrospection::method_listServices()
   * @return array
   */
  public function method_listServices()
  {
    $serviceIntrospection = new ServiceIntrospection( $this );
    return $serviceIntrospection->method_listServices();
  }

  /**
   * @see ServiceIntrospection::method_listMethods()
   * @return array
   */
  public function method_listMethods()
  {
    $serviceIntrospection = new ServiceIntrospection( $this );
    return $serviceIntrospection->method_listMethods();
  }

  /**
   * @see ServiceIntrospection::method_methodSignature()
   * @param string $method
   * @return array
   */
  public function method_methodSignature( $method )
  {
    $serviceIntrospection = new ServiceIntrospection( $this );
    return $serviceIntrospection->method_methodSignature( $method );
  }

  /**
   * @see ServiceIntrospection::method_methodHelp()
   * @param string $method
   * @return string
   */
  public function method_methodHelp( $method )
  {
    $serviceIntrospection = new ServiceIntrospection( $this );
    return $serviceIntrospection->method_methodHelp( $method );
  }
}
?>