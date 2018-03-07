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

/**
 * Directory containing the xml files with z30.50 datasource information.
 * Needs trailing slash
 * @var unknown_type
 */
define ( 'Z3950_XML_SERVERDATA_DIR', dirname(__FILE__) . "/servers/");

define ( 'Z3950_ANS2UNI_DAT_PATH' , dirname(__FILE__) . "/data/ans2uni.dat" );

/**
 * The path to the bibutils executables. Must contain a trailing slash
 * since the exeuctable name is appended to the path. If not defined,
 * the bibutils executables must be on the PATH
 */
if( !defined('BIBUTILS_PATH') )
{
  define('BIBUTILS_PATH','');
}

/*
 * log filters
 */
define( "BIBLIOGRAPH_LOG_Z3950", "Z3950" );
qcl_log_Logger::getInstance()->registerFilter( "BIBLIOGRAPH_LOG_Z3950", "Library Inmport Plugin", false );
qcl_log_Logger::getInstance()->registerFilter( "BIBLIOGRAPH_LOG_Z3950_VERBOSE", "Library Inmport Plugin (Verbose)", false );
