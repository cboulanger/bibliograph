<?php
/*
 * qcl - the qooxdoo component library
 *
 * http://qooxdoo.org/contrib/project/qcl/
 *
 * Copyright:
 *   2007-2015 Christian Boulanger
 *
 * License:
 *   LGPL: http://www.gnu.org/licenses/lgpl.html
 *   EPL: http://www.eclipse.org/org/documents/epl-v10.php
 *   See the LICENSE file in the project's top-level directory for details.
 *
 * Authors:
 *  * Christian Boulanger (cboulanger)
 */

/*
 * log filter
 */
define("QCL_LOG_PLUGIN","plugin");
qcl_log_Logger::getInstance()->registerFilter(QCL_LOG_PLUGIN,"Plugin-related log messages",false);

/*
 * Exception
 */
class qcl_application_plugin_Exception extends JsonRpcException {}
