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

/**
 * The name of the file containing the initial configuration
 * todo rename to QCL_APPLICATION_CONFIG_FILE
 */
if ( ! defined("QCL_SERVICE_CONFIG_FILE") )
{
  define("QCL_SERVICE_CONFIG_FILE","application.ini.php");
}


/*
 * Current state of the application. Must be one of these values:
 * "development":
 *      The application codebase is changing, the code is in a secure
 *      location so that security rules can be relaxed. The database schema can
 *      be changed.
 * "maintenance":
 *      The application is deployed, but needs maintenance (bug fixes,
 *      updates, etc.). This state can be alerted to the users of the application,
 *      users can be prevented from accessing the application or the application
 *      can be put in read-only mode. This is the default mode so that the
 *      application can be configured, the databases set up, etc.
 * "production":
 *      The application is deployed and in production. Security must be tighter.
 *      The database schema can not be modified.
 */
if ( ! defined( "QCL_APPLICATION_MODE") )
{
  define( "QCL_APPLICATION_MODE", "maintenance" );
}
if ( !in_array( QCL_APPLICATION_MODE, array("development", "maintenance", "production")))
{
  throw new LogicError('QCL_APPLICATION_MODE must be any of "development", "maintenance", "production"');
}


/*
 * log filters
 */
define("QCL_LOG_SETUP","setup");
qcl_log_Logger::getInstance()->registerFilter( QCL_LOG_SETUP, "Setup-related log messages",false);
define("QCL_LOG_APPLICATION","application");
qcl_log_Logger::getInstance()->registerFilter( QCL_LOG_APPLICATION, "Application-related log messages",false);
?>