<?php
/* ************************************************************************

   qcl - the qooxdoo component library

   http://qooxdoo.org/contrib/project/qcl/

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
 * This is an  example index.php file that you can drop into a
 * folder that contains test classes. The script scans this folder and
 * subfolders and looks for classes that extend from qcl_test_AbstractTestController
 * (this is configurable). The methods of these classes are checked
 * as to whether their doc comments contain the @rpctest tag. If so,
 * the content of this tag is parsed and returned as part of the json
 * data.
 */

/**
 * The  absolute path to the top-level "class" folder containing
 * the namespace folders with the test classes.
 */
define( "QCL_TEST_CLASS_DIR" , realpath( "../.." ) );

/**
 * The absolute path to the directory containing the server script.
 * Usually the parent directory of QCL_TEST_CLASS_DIR
 */
define( "QCL_TEST_SERVER_DIR" , realpath(  "../../.." ) );

/*
 * configure paths
 */
require QCL_TEST_SERVER_DIR . "/config.php";

/*
 * load the script that creates the test data
 */
require "qcl/test/testdata.php";
?>