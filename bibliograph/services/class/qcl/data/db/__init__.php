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
 * required classes
 */
qcl_import( "qcl_data_db_Manager" );
qcl_import( "qcl_data_db_Query" );
qcl_import( "qcl_data_db_Timestamp" );

/*
 * register log filters
 */
$logger = qcl_log_Logger::getInstance();
define("QCL_LOG_DB","db");
$logger->registerFilter( QCL_LOG_DB, "Database connection and queries",false);

define("QCL_LOG_TABLES","tables");
$logger->registerFilter( QCL_LOG_TABLES, "Database table schemas",false);


