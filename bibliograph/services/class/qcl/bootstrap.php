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
 * In this file, basic configuration settings and constants are
 * defined. This file MUST be included before including any other
 * qcl library file. You can override individual values by defining
 * them before including this file
 */

/*
 * PHP settings
 */
set_time_limit(120);
ini_set("html_errors",0);

/**
 * Path to the qcl php library
 * @var string
 */
if ( ! defined( "QCL_CLASS_PATH") )
{
  define("QCL_CLASS_PATH", realpath( dirname(__FILE__) ) );  
}

/*
 * Directory containing the service classes with trailing slash
 */
if ( ! defined( "QCL_SERVICE_PATH") )
{
  define( "QCL_SERVICE_PATH", str_replace("\\","/", dirname( dirname(__FILE__) ). "/" ) );
}

/**
 * A writable directory for temporary files
 */
if ( ! defined( "QCL_TMP_PATH" ) )
{
  define( "QCL_TMP_PATH", sys_get_temp_dir() );
}

/*
 * The directory where persistent data is stored, for example, the data of persistent
 * objects. Defaults to the system temp path. This is dangerous, however, because
 * this path will be regularly purged and the data is lost. Use a different directory
 * and make it writable.
 */
if ( ! defined("QCL_VAR_DIR") )
{
  define( "QCL_VAR_DIR" ,  sys_get_temp_dir() );
}


/*
 * Whether or not to use the embedded database (SQLite3) that comes with PHP for
 * the qcl model data. Will be automatically disabled when SQLite is not available
 * (for example, in a vanilla Ubuntu install). You can disable it manually by setting
 * this to false in your server.conf.php file.
 */
if ( ! defined("QCL_USE_EMBEDDED_DB") )
{
  define( "QCL_USE_EMBEDDED_DB" , false /*class_exists("SQLite3")*/ );
}

/*
 * The directory in which the SQLite database files should be stored.
 * Defaults to QCL_VAR_DIR 
 */
if ( ! defined("QCL_SQLITE_DB_DATA_DIR") )
{
  define( "QCL_SQLITE_DB_DATA_DIR" , QCL_VAR_DIR );
}

/*
 * load core functions
 */
require_once "qcl/core/__init__.php"; 
require_once "qcl/lib/rpcphp/server/error/JsonRpcError.php";

/*
 * core packages
 */
qcl_import("qcl_log_Logger");
qcl_import("qcl_lang_String");
qcl_import("qcl_lang_ArrayList");

?>