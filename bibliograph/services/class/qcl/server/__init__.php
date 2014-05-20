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

/** @noinspection PhpIncludeInspection */
require_once "qcl/lib/rpcphp/server/JsonRpcServer.php";

/**
 * Upload path constant
 */
if ( ! defined("QCL_UPLOAD_PATH") )
{
  define("QCL_UPLOAD_PATH", sys_get_temp_dir() );
}

/**
 * Maximal file size constant (in kilobytes)
 */
if ( ! defined("QCL_UPLOAD_MAXFILESIZE") )
{
  define("QCL_UPLOAD_MAXFILESIZE", 30000 );
}

/*
 * log filter name for request-related messages
 */
define("QCL_LOG_REQUEST", "request");
qcl_log_Logger::getInstance()->registerFilter( QCL_LOG_REQUEST, "Request-related log messages",false);

/**
 * Exception thrown by jsonrpc services
 * todo rename using namespace, move elsewhere
 */
class JsonRpcException extends JsonRpcError {}

/*
 * import service exception (no backtrace, option to suppress visible errors)
 */
qcl_import("qcl_server_ServiceException");

/**
 * Exception thrown by services that request data from other web services and
 * encounter problems
 */
class qcl_server_IOException extends qcl_server_ServiceException{}

/**
 * Wrapper around file_get_contents for remote files
 * @param string $url
 *  The URL
 * @param array $opts
 *  Optional data passed to stream_context_create. See
 *  http://de1.php.net/manual/en/function.stream-context-create.php
 */
function qcl_server_getHttpContent($url,$opts=null)
{
  qcl_assert_valid_url( $url );
  $context = is_array($opts) ? stream_context_create($opts) : null;
  $file = @file_get_contents($url, false, $context);
  if ( $file === false )
  {
    throw new qcl_server_IOException("Problem requesting data from $url");
  }
  return $file;
}

/**
 * Wrapper around file_get_contents for remote xml files, returning a SimpleXml object
 * @param string $url
 *  The URL
 * @return SimpleXMLElement
 */
function qcl_server_getXmlContent($url)
{
  $opts = array(
    'http'=>array(
      'method'=>"GET",
      'header'=>"Accept: application/xml"
    )
  );
  $xml = qcl_server_getHttpContent($url,$opts);
  //qcl_log_Logger::getInstance()->log($xml,"warn");
  return simplexml_load_string($xml);
}

/**
 * Wrapper around file_get_contents for remote json files, returning an array
 * @param string $url
 *  The URL
 * @return array
 */
function qcl_server_getJsonContent($url)
{
  $opts = array(
    'http'=>array(
      'method'=>"GET",
      'header'=>"Accept: application/json"
    )
  );
  $json = qcl_server_getHttpContent($url,$opts);
  return json_decode($json,true);
}