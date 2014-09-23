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

qcl_import( "qcl_access_model_User" );
qcl_import( "qcl_config_UserConfigModel" );

/*
 * constants
 */
define( "QCL_CONFIG_TYPE_STRING",   "string");
define( "QCL_CONFIG_TYPE_NUMBER",   "number");
define( "QCL_CONFIG_TYPE_BOOLEAN",  "boolean");
define( "QCL_CONFIG_TYPE_LIST",     "list");


/*
 * exceptions
 */
class qcl_config_Exception extends JsonRpcException {}

/*
 * log filters
 */
define( "QCL_LOG_CONFIG", "config" );
qcl_log_Logger::getInstance()->registerFilter( QCL_LOG_CONFIG, "Configuration-related log messages", false );
