<?php
/* ************************************************************************

   Bibliograph: Collaborative Online Reference Management

   http://www.bibliograph.org

   Copyright:
   2007-2017 Christian Boulanger

   License:
   LGPL: http://www.gnu.org/licenses/lgpl.html
   EPL: http://www.eclipse.org/org/documents/epl-v10.php
   See the LICENSE file in the project's top-level directory for details.

   Authors:
   * Chritian Boulanger (cboulanger)

************************************************************************ */

namespace lib\dialog;

class Prompt extends Alert
{

  /**
   * The default text for the prompt
   * @var string|null
   */
  public $default = null;

  /**
   * Set the default text for the prompt
   * @param string $value
   * @return $this
   */
  public function setDefault($value){$this->default = $value; return $this;}

  /**
   * Set whether input is required
   * @var bool
   */
  public $requireInput = false;

  /**
   * Whether input is required
   * @param bool $value
   * @return $this
   */
  public function setRequireInput($value){$this->default = $value; return $this;}

  /**
   * The timeout in seconds before the prompt is auto-submitted after a value has been entered
   * @var bool
   */
  public $autoSubmitTimeout = false;

  /**
   * Set the timeout in seconds before the prompt is auto-submitted after a value has been entered
   * @param int $value
   * @return $this
   */
  public function setAutoSubmitTimeout($value){$this->default = $value; return $this;}

  /**
   * @inheritdoc
   */
  public function sendToClient()
  {
    static::create(
      $this->message,
      $this->default,
      $this->service,
      $this->method,
      $this->params,
      $this->requireInput,
      $this->autoSubmitTimeout
    );
  }

  /**
   * Returns a message to the client which prompts the user with an message and
   * an input field.
   * @param string $message
   *    The message text
   * @param string|null $default
   *    The default value
   * @param string|null $callbackService
   *    Optional service that will be called when the user clicks on the OK button
   * @param string|null $callbackMethod Optional service method
   * @param array|null $callbackParams
   *    Optional service params
   * @param bool|null $requireInput
   *    Optional flag to prevent user from submitting an empty response
   * @param number|null $autoSubmitTimeout
   *    Optional timeout in seconds If provided, the prompt dialog "submits itself"
   *    after the given timeout. If the $requireInput flag is set to true, this
   *    happens only if input has been entered and this input hasn't changed
   *    for the duration of the timeout
   * @deprecated Please use setters instead
   */
  public static function create() {
    list(
      $message,
      $default,
      $callbackService,
      $callbackMethod,
      $callbackParams,
      $requireInput,
      $autoSubmitTimeout
    ) = array_pad( func_get_args(), 7, null);

    $properties = array(
      'message'           => $message,
      'value'             => $default,
    );
  
    if ($requireInput !== null) {
      $properties['requireInput'] = $requireInput;
    }
    if ($autoSubmitTimeout !== null) {
      $properties['autoSubmitTimeout'] = $autoSubmitTimeout;
    }
    
    static::addToEventQueue( array(
      'type' => "prompt",
      'properties' => $properties,
      'service' => $callbackService,
      'method'  => $callbackMethod,
      'params'  => $callbackParams
    ));
  }
}
