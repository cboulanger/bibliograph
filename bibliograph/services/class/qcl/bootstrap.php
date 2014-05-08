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
 * set the default timeout for script execution
 */
set_time_limit(120);

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
 * load core functions
 */
require_once "qcl/core/functions.php";
require_once "qcl/lib/rpcphp/server/error/JsonRpcError.php";

/*
 * core packages
 */
qcl_import("qcl_log_Logger");
qcl_import("qcl_lang_String");
qcl_import("qcl_lang_ArrayList");

?>