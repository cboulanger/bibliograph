<?php
/* ************************************************************************

   Bibliograph: Collaborative Online Reference Management

   http://www.bibliograph.org

   Copyright:
     2004-2014 Christian Boulanger

   License:
     LGPL: http://www.gnu.org/licenses/lgpl.html
     EPL: http://www.eclipse.org/org/documents/epl-v10.php
     See the LICENSE file in the project's top-level directory for details.

   Authors:
   *  Christian Boulanger (cboulanger)

************************************************************************ */

/*
 * access constants
 */
define( "QCL_ACCESS_READ",    "read"  );
define( "QCL_ACCESS_WRITE",   "write" );
define( "QCL_ACCESS_CREATE",  "create" );
define( "QCL_ACCESS_DELETE",  "delete" );
define( "QCL_ACCESS_ALL",     "*" );

/*
 * three default roles.
 */
define( "QCL_ROLE_ANONYMOUS", "anonymous" );
define( "QCL_ROLE_USER", "user" );
define( "QCL_ROLE_ADMIN", "admin" );

/*
 * the prefix for the anonymous user
 */
if ( ! defined('QCL_ACCESS_ANONYMOUS_USER_PREFIX') )
{
  define('QCL_ACCESS_ANONYMOUS_USER_PREFIX', "anonymous_");
}

/*
 * the timeout of a normal session, in seconds, Defaults to 60 minutes
 */
if ( ! defined('QCL_ACCESS_TIMEOUT') )
{
  define('QCL_ACCESS_TIMEOUT', 60*60 );
}

/*
 * The lifetime of an anonymous user session (if not refreshed), in seconds. Defaults to 1 minute.
 */
if ( ! defined("QCL_ACCESS_ANONYMOUS_SESSION_LIFETIME") )
{
  define( "QCL_ACCESS_ANONYMOUS_SESSION_LIFETIME" , 60 );
}

/*
 * The lifetime of a token (a distributable session id), in seconds. Defaults to 24h
 * todo not yet used, implement
 */
if ( ! defined("QCL_ACCESS_TOKEN_LIFETIME") )
{
  define( "QCL_ACCESS_TOKEN_LIFETIME" , 60*60*24 );
}

/*
 * the salt used for storing encrypted passwords
 */
define('QCL_ACCESS_SALT_LENGTH', 9);

/*
 * log filter
 */
define("QCL_LOG_AUTHENTICATION", "authentication");
qcl_log_Logger::getInstance()->registerFilter(QCL_LOG_AUTHENTICATION,"Authentication-related log messages",false);

define("QCL_LOG_ACL", "acl");
qcl_log_Logger::getInstance()->registerFilter(QCL_LOG_ACL,"Access-control-related log messages",false);

define("QCL_LOG_LDAP", "ldap");
qcl_log_Logger::getInstance()->registerFilter(QCL_LOG_LDAP,"LDAP-related log messages",false);


/*
 * Exceptions thrown in this class and subclasses
 */
 qcl_import("qcl_server_ServiceException");
class qcl_access_AccessDeniedException extends qcl_server_ServiceException {}
class qcl_access_AuthenticationException extends qcl_access_AccessDeniedException {}
class qcl_access_InvalidSessionException extends qcl_access_AccessDeniedException {}
class qcl_access_TimeoutException extends qcl_access_InvalidSessionException {}