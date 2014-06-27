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
 * include this at the beginning of the script.
 */

date_default_timezone_set("Europe/Berlin");

ini_set('include_path', implode(
  PATH_SEPARATOR,
  array(
    dirname( dirname(__DIR__) ),
    ini_get("include_path")
  )
) );
require_once dirname(__DIR__) . "/bootstrap.php";