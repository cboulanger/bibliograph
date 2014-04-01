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
 */
if ( ! defined("QCL_SERVICE_CONFIG_FILE") )
{
  define("QCL_SERVICE_CONFIG_FILE","application.ini.php");
}

/*
 * log filters
 */
define("QCL_LOG_SETUP","setup");
qcl_log_Logger::getInstance()->registerFilter( QCL_LOG_SETUP, "Setup-related log messages",false);
define("QCL_LOG_APPLICATION","application");
qcl_log_Logger::getInstance()->registerFilter( QCL_LOG_APPLICATION, "Application-related log messages",false);
?>