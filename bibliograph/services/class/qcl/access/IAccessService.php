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

/**
 * Interface for classes that provide access behavior
 */
interface qcl_access_IAccessService
{

  /**
   * Authenticate with username and password (two parameters),
   * or a valid session id (one parameter).
   * If authenticated, return an array of the following structure
   *
   * array(
   *   "error"       => false,  // must be part of the array
   *   "permissions" => array( "permission1","permission2" ....),
   *   "sessionId"   => "a23j2h3i4h2l2...",
   *   "username"    => "jondoe",
   *   "anonymous"   => false,   // or true if unauthenticated
   *   ... < Any more userdata that you want to send to client >
   * );
   *
   * If authentication fails, you must return
   * array(
   *    "error"     => "Error message",
   *    "sessionId" => <session id or null if no session>
   * )
   * @param string $first If two arguments, this is the username. If no argument,
   * or null use the session id that has already been established by the access
   * behavior.
   * @param string $password (MD5-encoded) password
   * @return qcl_access_AuthenticationResult
   */
  function method_authenticate( $first=null, $password=null );

  /**
   * Service method to log out the active user. Automatically creates guest
   * access, if allowed
   * @return qcl_data_Result
   */
  function method_logout();


  /**
   * Service method to terminate a session (remove session and user data).
   * Useful for example when browser window is closed.
   * @return qcl_data_Result
   */
  function method_terminate();
}
?>