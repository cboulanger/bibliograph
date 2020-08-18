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

/**
 * This dialog widget is different from the others as it does not create a
 * browser event, but a long-running chunked HTTP response. It works only if
 * no headers have been sent before and must be called via a normal http
 * GET request (not in a JSONRPC request). It is the server companion of
 * the qcl.dialog.ServerProgress on the client.
 */
class ServerProgress extends Dialog implements \lib\interfaces\Progress
{
  /**
   * The id of the progress widget
   */
  protected $widgetId;

  /**
   * If true, newlines will be inserted into the generated javascript code
   * @var bool
   */
  public $insertNewlines = false;

  /**
   * @var bool
   */
  private $debug = false;

  /** @noinspection PhpMissingParentConstructorInspection */

  /**
   * Constructor
   * @param string $widgetId The id of the progress widget
   * @param bool $debug If true, omit the Transfer-encoding: chunked header. This allows to debug the response in a browser
   */
  public function __construct($widgetId, $debug=false)
  {
    $this->debug = $debug;
    $this->widgetId = $widgetId;
    header("Cache-Control: no-cache, must-revalidate");
    header("Expires: Mon, 26 Jul 1997 05:00:00 GMT");
    header("Pragma:");
    if( ! $debug ){
      header('Content-Encoding: chunked');
      header('Transfer-Encoding: chunked');
    }
    header('Content-Type: text/html');
    header('Connection: keep-alive');
    flush();
    //function_exists("apache_setenv") ? @apache_setenv('no-gzip', 1) : null;
    @ini_set('output_buffering', 0);
    @ini_set('zlib.output_compression', 0);
    @ini_set('implicit_flush', 1);
    flush();
    $this->start();
  }

  /**
   * Internal function to send a chunk of data
   */
  protected function send($chunk)
  {
    // add padding to force Safari and IE to render
    if( strlen($chunk) < 1024){
      $chunk = str_pad( $chunk, 1024 );
    }
    $chunk .= "<br/>\n"; // needed by Safari and Internet Explorer
    echo sprintf("%x\r\n", strlen($chunk));
    echo $chunk;
    echo "\r\n";
    flush();
    ob_flush();
  }

  /**
   * Sends a script via a script tag
   * @param array $lines An array of javascript script lines
   */
  protected function sendScript(array $lines)
  {
    $script = $this->createScript($lines);
    $this->send($script);
  }

  /**
   * Returns new line character or empty string depending on insertNewlines property
   * @return string
   */
  protected function getNewlineChar()
  {
    return $this->insertNewlines ? "\n" : "";
  }

  /**
   * Creates a script tag with the given lines
   * @param array $lines
   * @return string
   */
  protected function createScript( array $lines)
  {
    $nl = $this->getNewlineChar();
    $tag = '';
    $tag .= '<script type="text/javascript">';
    $tag .= $nl . implode( $nl, $lines );
    $tag .= $nl . '</script>' . $nl;
    return $this->debug ? nl2br(htmlentities($tag)) : $tag;
  }

  /**
   * Called at the start of the transmission, sets a few global variables inside the iframe.
   */
  public function start()
  {
    $this->sendScript([
      "window.progress=window.top.qx.core.Id.getQxObject('{$this->widgetId}');",
      "window.bus=top.qx.event.message.Bus.getInstance();",
    ]);
  }

  /**
   * API function to set the state of the progress par
   * @param integer $value The valeu of the progress, in percent
   */
  public function setProgress(int $value, string $message=null, string $newLogText=null)
  {
    // sprintf('console.log("%d, %s, %s");',$value, $message, $newLogText);
    static $oldValue = 0;
    static $oldMessage = "";
    // update only if necessary
    if( $newLogText or $value !== $oldValue or $message !== $oldMessage){
      $data =['progress'=>$value];
      if($message) $data['message']=$message;
      if($newLogText) $data['newLogText']= $newLogText;
      $this->sendScript([
        "window.progress.set(", json_encode($data), ");"
      ]);
    }
  }

  /**
   * API function to dispatch a client message (scope: application)
   * @param string $name Name of message
   * @param mixed|null $data Message data
   *
   */
  public function dispatchClientMessage($name,$data=null)
  {
    $this->sendScript([
      "window.bus.dispatchByName('$name',", json_encode($data), ");"
    ]);
  }

  /**
   * API function to dispatch a event (scope: progress widget)
   * @param string $name Name of event
   * @param mixed|null $data event data
   *
   */
  public function dispatchClientEvent($name,$data=null)
  {
    $this->sendScript([
      "window.progress.fireDataEvent('$name',", json_encode($data), ");"
    ]);
  }

  /**
   * API function to trigger an error alert
   * @param string $message
   */
  public function error(string $message)
  {
    $this->setProgress(100);
    $this->sendScript([
      "window.progress.hide();",
      "window.top.dialog.Dialog.error('" . addslashes($message) . "');"
    ]);
    $this->close();
  }

  /**
   * Closes the chunked transfer connection
   */
  public function close()
  {
    echo sprintf("%x\r\n", 0);
    echo "\r\n";
    flush();
    ob_flush();
    exit();
  }

  /**
   * Must be called on completion of the script
   * @param string|null Optional message that will be shown in an alert dialog
   */
  public function complete(string $message=null)
  {
    $this->setProgress(100);
    if ( $message )
    {
      $this->sendScript([
        "window.top.dialog.Dialog.alert('$message');"
      ]);
    }
    $this->close();
  }
}
