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
 * The path to the bibutils executables. Must contain a trailing slash
 * since the exeuctable name is appended to the path. If not defined,
 * the bibutils executables must be on the PATH
 */
if( !defined('BIBUTILS_PATH') )
{
  define('BIBUTILS_PATH','');
}
