<?php
/* ************************************************************************

   qcl - the qooxdoo component library

   http://qooxdoo.org/contrib/project/qcl/

   Copyright:
     2007-2015 Christian Boulanger

   License:
     LGPL: http://www.gnu.org/licenses/lgpl.html
     EPL: http://www.eclipse.org/org/documents/epl-v10.php
     See the LICENSE file in the project's top-level directory for details.

   Authors:
   *  Christian Boulanger (cboulanger)

************************************************************************ */


//-------------------------------------------------------------
// Configure paths to the various libraries used by the backend
//-------------------------------------------------------------

/**
 * Server root path
 * @var string
 */
define("SERVER_ROOT", realpath("."));

/**
 * Path to the qcl php library
 * @var string
 */
define("QCL_CLASS_PATH", SERVER_ROOT . "/class/");

/**
 * Path to the application backend classes. Defaults to "./class". You
 * shouldn't change this
 * @var string
 */
define("APPLICATION_CLASS_PATH", SERVER_ROOT . "/class/");

/*
 * The directory where persistent data is stored, for example, the data of persistent
 * objects. You MUST change this path to a world-writable path outside the server
 * document root before using the application in production, otherwise you risk losing
 * data.
 */
if ( ! defined("QCL_VAR_DIR") )
{
  define( "QCL_VAR_DIR" ,  sys_get_temp_dir() );
}

/*
 * where should the application log to. By default
 * the log file is "bibliograph.log"
 */
define( "QCL_LOG_FILE", "/tmp/bibliograph.log" );

//-------------------------------------------------------------
// Application-related configuration
//-------------------------------------------------------------

/**
 * The path to the bibutils executables. Must contain a trailing slash
 * since the exeuctable name is appended to the path. If not defined,
 * the executables must be on the PATH
 */
define('BIBUTILS_PATH','/usr/bin/');

/*
 * The mail server. If not defined, localhost is used
 */
//define( "QCL_MAIL_SMTP_HOST", "localhost" );

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


//-------------------------------------------------------------
// Development & debugging (change this only if you know what you are doing)
//-------------------------------------------------------------

/*
 * log defaults. by default, no log messages
 */
define("APPLICATION_LOG_DEFAULT", serialize( array(
  "QCL_LOG_SETUP" => true,
  "QCL_LOG_REQUEST" => true,
  "QCL_LOG_APPLICATION" => true,
  "QCL_LOG_AUTHENTICATION" => true
)));

//-------------------------------------------------------------
// PHP configuration
//-------------------------------------------------------------

//ini_set('memory_limit', '512M'); // uncomment this if the backend runs out of memory
//date_default_timezone_set("Europe/Berlin"); // uncomment and correct this if PHP complains about a missing timezone

//-------------------------------------------------------------
// DON'T TOUCH
//-------------------------------------------------------------

error_reporting( E_ALL & ~E_NOTICE & ~E_STRICT & ~E_DEPRECATED );
ini_set("display_errors",false);
ini_set("error_log",QCL_LOG_FILE);
ini_set("log_errors",true);

ini_set('include_path', implode(
  PATH_SEPARATOR,
  array(
    QCL_CLASS_PATH,
    APPLICATION_CLASS_PATH,
    ini_get("include_path")
  )
) );