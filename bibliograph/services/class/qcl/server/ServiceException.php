<?php
/*
 * qcl - the qooxdoo component library
 *
 * http://qooxdoo.org/contrib/project/qcl/
 *
 * Copyright:
 *   2007-2015 Christian Boulanger
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
 * An exception that is the result of data supplied by the client
 * and therefore should not generate backtraces. It replaces the deprecated
 * third argument to the constructor (previous error) with a
 * a "silent" option that indicates that there should be no visible alerts to
 * the user to the client
 */
class qcl_server_ServiceException
  extends JsonRpcException
{

  /**
   * @var bool
   */
  private $silent = false;

  /**
   * @param string $message
   * @param int $code The error code
   * @param bool $silent If true, suppress any visible alerts to the user on the client
   * @param int $origin
   */
  function __construct( $message = "Unspecified error",
                        $code = JsonRpcError_ScriptError,
                        $silent = false,
                        $origin = JsonRpcError_Origin_Application)
  {
    parent::__construct($message,$code,null,$origin);
    $this->setSilent( $silent );
  }

  /**
   * Setter for silent option
   * @param bool $value
   */
  public function setSilent( $value )
  {
    qcl_assert_boolean( $value );
    $this->silent = $value;
  }

  /**
   * Getter for error data. Overrides parent method to add "silent" property to error data.
   * @return array
   */
  function getData()
  {
    $data = parent::getData();
    $data['silent'] = $this->silent;
    return $data;
  }
}