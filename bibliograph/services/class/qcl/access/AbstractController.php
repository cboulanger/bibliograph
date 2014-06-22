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

/** @noinspection PhpIncludeInspection */
require_once "qcl/lib/rpcphp/server/access/IAccessibilityBehavior.php";
qcl_import( "qcl_data_controller_Controller" );

/**
 * Abstract access controller 
 */
abstract class qcl_access_AbstractController
  extends qcl_data_controller_Controller
  implements IAccessibilityBehavior
{
  
  
  //-------------------------------------------------------------
  // getters and setters
  //-------------------------------------------------------------  

  /**
   * Shorthand getter for access controller. Overridden to return itself
   * when called from from subclasses
   * @return qcl_access_Controller
   */
  public function getAccessController()
  {
    return $this;
  }

  //-------------------------------------------------------------
  // access control on the session level
  //-------------------------------------------------------------

  /**
   * Whether guest access to the service classes is allowed
   * @return boolean
   */
  abstract public function isAnonymousAccessAllowed();

  /**
   * Check the accessibility of service object and service
   * method. Aborts request when access is denied, unless when the method name is
   * "authenticate" or access is explicitly granted
   * @param qcl_core_Object $serviceObject
   * @param string $method
   * @throws LogicException
   * @throws Exception
   * @throws qcl_access_AccessDeniedException
   * @return void
   */
  abstract public function checkAccessibility( $serviceObject, $method );

  //-------------------------------------------------------------
  // authentication
  //-------------------------------------------------------------

  /**
   * Checks if the requesting client is an authenticated user.
   * @throws qcl_access_AccessDeniedException
   * @throws JsonRpcException
   * @return bool True if request can continue, false if it should be aborted with
   * qcl_access_AccessDeniedException.
   * @return bool userId
   */
  abstract public function createUserSession();

  /**
   * Authenticate a user with a password. Returns an integer with
   * the user id if successful. Throws qcl_access_AuthenticationException
   * if unsuccessful
   *
   * @param string $username or null
   * @param string $password (MD5-encoded) password
   * @throws qcl_access_AuthenticationException
   * @return int|false The id of the user or false if authentication failed
   */
  abstract public function authenticate( $username, $password );

  /**
   * Terminates and destroys the active session
   * @return void
   */
  abstract public function terminate();

  /**
   * Logs out the the active user. If the user is anonymous, delete its record
   * in the user table.
   * @return bool success
   */
  abstract public function logout();

  /**
   * Grant guest access, using a new session.
   * @return int user id
   */
  abstract public function grantAnonymousAccess();

  /**
   * Creates a valid user session for the given user id, i.e. creates
   * the user object if needed. A valid session must already exist.
   * @param $userId
   * @return void
   */
  abstract public function createUserSessionByUserId( $userId );

  //-------------------------------------------------------------
  // IAccessibilityBehavior
  //-------------------------------------------------------------

  /**
   * Unused, simply here for implementing IAccessibilityBehavior.
   */
  function getErrorMessage()
  {
    throw new Exception( __METHOD__ . " is not implemented");
  }

  /**
   * Unused, simply here for implementing IAccessibilityBehavior.
   */
  function getErrorNumber()
  {
    throw new Exception( __METHOD__ . " is not implemented");
  }
}