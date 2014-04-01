<?php
/*
 * qooxdoo - the new era of web development
 *
 * http://qooxdoo.org
 *
 * Copyright:
 *   2006-2009 Derrell Lipman, Christian Boulanger
 *
 * License:
 *   LGPL: http://www.gnu.org/licenses/lgpl.html
 *   EPL: http://www.eclipse.org/org/documents/epl-v10.php
 *   See the LICENSE file in the project's top-level directory for details.
 *
 * Authors:
 *  * Derrell Lipman (derrell)
 *  * Christian Boulanger (cboulanger) Error-Handling and OO-style rewrite
 */

/**
 * PHP JSON-RPC server for qooxdoo
 * This file contains global settings for the server. See AbstractServer.php and
 * JsonRpcServer.php for an explanation of the constants.
 */

/*
 * default accessibility mode for the services, defaults to "domain"
 */
define( "defaultAccessibility", "public" );

/*
 * if the service classes are not in the class/ folder,
 * customize the service path prefix here (trailing slash required)
 */
//define( "servicePathPrefix", "/your/custom/location/" );

/*
 * the class name prefix for service classes, defaults to "class_"
 */
//define("JsonRpcClassPrefix",  "class_");

/*
 * the method name prefix for service methods, defaults to "method_"
 */
//define("JsonRpcMethodPrefix",  "method_");

/*
 * signature mode, defaults to "check"
 * see AbstractServer.php, lines 69-90
 */
//define( "RpcMethodSignatureMode", "check" );

/*
 * turn debugging on or off, defaults to false
 */
//define( "JsonRpcDebug", false );

/*
 * log file
 */
//define( "JsonRpcDebugFile", "/tmp/jsonrpc.log");

/*
 * Whether to encode and decode Date objects the "qooxdoo way"
 * See JsonRpcServer.php, line 72-101
 */
if ( ! defined("handleQooxdooDates") )
{
  define( "handleQooxdooDates", true );
}

?>
