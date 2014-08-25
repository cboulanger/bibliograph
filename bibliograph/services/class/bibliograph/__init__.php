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

/*
 * constants
 */
define( "BIBLIOGRAPH_ROLE_USER",    "user" );
define( "BIBLIOGRAPH_ROLE_MANAGER", "manager" );
define( "BIBLIOGRAPH_ROLE_ADMIN",   "admin" );

const BIBLIOGRAPH_VALUE_SEPARATOR = "; "; // todo This cannot be a global value, dependent on record schema!

/*
 * log filters
 */
define( "BIBLIOGRAPH_LOG_APPLICATION", "bibliograph" );
qcl_log_Logger::getInstance()->registerFilter( BIBLIOGRAPH_LOG_APPLICATION, "", false );

require_once "bibliograph/schema/__init__.php";