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
 * A writable directory for log files
 */
if ( ! defined("QCL_LOG_PATH") )
{
  define ( "QCL_LOG_PATH", QCL_VAR_DIR );
}

/*
 * The name of the log file
 */
if ( ! defined("QCL_LOG_FILE_NAME") )
{
  define("QCL_LOG_FILE_NAME", "qcl.log");
}

/*
 * the path of the logfile of the main application
 */
if ( ! defined("QCL_LOG_FILE") )
{
  define( "QCL_LOG_FILE" ,  QCL_LOG_PATH . "/" . QCL_LOG_FILE_NAME );
}

/*
 * The maximum size of the logfile, defaults to 500 KB
 */
if ( ! defined("QCL_LOG_MAX_FILESIZE") )
{
  define( "QCL_LOG_MAX_FILESIZE" , 1024 * 500 );
}