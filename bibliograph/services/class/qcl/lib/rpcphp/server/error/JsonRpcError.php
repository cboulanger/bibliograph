<?php
/*
 * qooxdoo - the new era of web development
 *
 * http://qooxdoo.org
 *
 * Copyright:
 *   2006-2009 Derrell Lipman
 *
 * License:
 *   LGPL: http://www.gnu.org/licenses/lgpl.html
 *   EPL: http://www.eclipse.org/org/documents/epl-v10.php
 *   See the LICENSE file in the project's top-level directory for details.
 *
 * Authors:
 *  * Derrell Lipman (derrell)
 *  * Christian Boulanger (cboulanger)
 */

/*
 * Base class
 */
require dirname(__FILE__) . "/AbstractError.php";


/*
 * class JsonRpcError
 *
 * This class allows service methods to easily provide error information for
 * return via JSON-RPC.
 */
class JsonRpcError extends AbstractError
{

  /**
   * The id of the request if script transport is used
   */
  private $scriptTransportId = ScriptTransport_NotInUse;

  /**
   * Setter for script transport id
   * @param $id
   * @return unknown_type
   */
  public function setScriptTransportId($id)
  {
    $this->scriptTransportId = $id;
  }

  /**
   * Getter for script transport id
   * @return int
   */
  public function getScriptTransportId()
  {
    return $this->scriptTransportId;
  }

  /**
   * Returns the error jsonrpc data to the client
   * @param array $optional_data An optional array of key=>value pairs that should be included
   * in the array response. Must not contain the keys "error" and "id"
   */
  public function sendAndExit( $optional_data=array() )
  {
    $ret = array_merge(
      array(
        "error" => $this->getData(),
         "id"   => $this->getId()
      ),
      $optional_data
    );

    $json = new JsonWrapper;

    if ( handleQooxdooDates )
    {
      $json->useJsonClass();
      $this->sendReply( $json->encode($ret) );
    }
    else
    {
      $this->sendReply( $json->encode($ret) );
    }
    exit;
  }

  /**
   * Sends text to the client
   * @param $reply
   * @param $scriptTransportId
   * @return unknown_type
   */
  public function sendReply( $reply )
  {
    $scriptTransportId = $this->getScriptTransportId();

    /* If not using ScriptTransport... */
    if ($scriptTransportId == ScriptTransport_NotInUse)
    {
        /* ... then just output the reply. */
        print $reply;
    }
    else
    {
        /* Otherwise, we need to add a call to a qooxdoo-specific function */
        header("Content-Type: application/javascript");
        $reply =
            "qx.io.remote.transport.Script._requestFinished(" .
            $scriptTransportId . ", " . $reply . ");";
        print $reply;
    }
  }
}

/**
 * JsonRpcError will be renamed into JsonRpcException in the next major release
 */
class JsonRpcException extends JsonRpcError {}
?>