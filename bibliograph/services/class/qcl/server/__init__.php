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
 * Exception thrown when the request contains invalid arguments.
 * Does not generate a backtrace in the log.
 */
class InvalidJsonRpcArgumentException extends JsonRpcException {}

/**
 * Upload path constant
 */
if ( ! defined("QCL_UPLOAD_PATH") )
{
  define("QCL_UPLOAD_PATH", sys_get_temp_dir() );
}

/**
 * Maximal file size constant (in kilobytes)
 */
if ( ! defined("QCL_UPLOAD_MAXFILESIZE") )
{
  define("QCL_UPLOAD_MAXFILESIZE", 30000 );
}

/*
 * log filter name for request-related messages
 */
define("QCL_LOG_REQUEST", "request");
qcl_log_Logger::getInstance()->registerFilter( QCL_LOG_REQUEST, "Request-related log messages",false);

?>