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

/**
 * The response is the "view" part of the mvc Architecture in qcl.
 * It models the jsonrpc response. It differs from the "normal" jsonrpc
 * response on which the different jsonrpc backends and the databinding
 * feature of qooxdoo in that an additional data layer is added, which
 * transports events and messages between server and client.
 *
 * {
 *   // result property should always be provided in order to allow events and
 *   // messages to be transported
 *   result :
 *   {
 *     data       :  (... result data ...) ,
 *     events : [ { type : "fooEvent", data : ... },
 *                { type : "barEvent", data: ... }, ... ],
 *     messages : [ { name : "appname.messages.foo", data : ... },
 *                  { name : "appname.messages.bar", data: ... }, ... ]
 *   }
 *   // error property only exists if an error occurred
 *   error :
 *   {
 *     (... error data ...)
 *   }
 *   id : (int id of rpc request)
 * }
 *
 * The result.result element can be of any type, however, usually
 * it is an object, which allows to enforce a specific interface and,
 * thus, to enforce a signature for response data. You can use the co
 *
 */
class qcl_server_Response
  extends qcl_core_Object
{

  /**
   * The id of the request
   */
  public $id = null;

  /**
   * The result of the service call
   * @var unknown_type
   */
  public $result = array( "__qcl" => true );

  /**
   * Error data. of amy
   */
  public $error = null;

  /**
   * Returns a singleton instance of this class
   * @return qcl_data_Result
   */
  public static function getInstance( )
  {
    return qcl_getInstance(__CLASS__);
  }

  /**
   * Sets the response data
   * @param $data
   * @return void
   */
  public function setData( $data )
  {
    $this->result['data'] = $data;
  }

  /**
   * Sets the events
   * @param $events
   * @return unknown_type
   */
  public function setEvents( $events )
  {
    if ( $events and count($events) )
    {
      $this->result['events'] = $events;
    }
  }

  /**
   * Sets the messages
   * @param $messages
   * @return unknown_type
   */
  public function setMessages( $messages )
  {
    if ( $messages and count($messages) )
    {
      $this->result['messages'] = $messages;
    }
  }
}
