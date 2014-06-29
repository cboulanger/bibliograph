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

/*
 * This is the bootstrap file for the test files. Each test file must
 * include this file at the beginning of the script.
 */

/*
 * some PHP installations require this setting
 */
date_default_timezone_set("Europe/Berlin");

/*
 * set include paths
 */
ini_set('include_path', implode(
  PATH_SEPARATOR,
  array(
    dirname( dirname(__DIR__) ),
    ini_get("include_path")
  )
) );

/*
 * if any of the following constant names are set as environment
 * variables, use these values
 */
$ENV_TO_CONST = array(
  "QCL_LOG_FILE",
  "QCL_TMP_PATH",
  "QCL_VAR_DIR",
  "QCL_USE_EMBEDDED_DB",
  "QCL_SQLITE_DB_DATA_DIR"
);
foreach( $ENV_TO_CONST as $name)
{
  if ( isset( $_ENV[$name] ) )
  {
    $value = $_ENV[$name];
    define( $name, $value );
  }
}

/*
 * bootstrap qcl
 */
require_once dirname(__DIR__) . "/bootstrap.php";

/*
 * start logging
 */
qcl_import("qcl_log_Logger");
qcl_log_Logger::getInstance()->info("\n\n\n");
qcl_log_Logger::getInstance()->info("Starting tests...");
qcl_log_Logger::getInstance()->info("-----------------");

echo "\n\n\n";
echo "Starting tests. Log file is located at " . QCL_LOG_FILE;
echo "\n";

