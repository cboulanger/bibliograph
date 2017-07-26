<?php
/* ************************************************************************

   Bibliograph: Collaborative Online Reference Management

   http://www.bibliograph.org

   Copyright:
     2007-2010 Christian Boulanger

   License:
     LGPL: http://www.gnu.org/licenses/lgpl.html
     EPL: http://www.eclipse.org/org/documents/epl-v10.php
     See the LICENSE file in the project's top-level directory for details.

   Authors:
     * Chritian Boulanger (cboulanger)

************************************************************************ */

// for the definition of constants, log filters, exceptions

/*
 * the path to a directory where backups are stored by default
 */
 if ( ! defined("BACKUP_PATH") )
 {
   define("BACKUP_PATH", QCL_VAR_DIR );
 }
 
/*
 * log filter name for this plugin
 */
define("QCL_LOG_PLUGIN_BACKUP", "QCL_LOG_PLUGIN_BACKUP");
qcl_log_Logger::getInstance()->registerFilter( QCL_LOG_PLUGIN_BACKUP, "Log messages for backup plugin",false);
