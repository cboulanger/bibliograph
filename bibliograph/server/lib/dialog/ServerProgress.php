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
 * browser event, but a long-running chunked HTTP respose. It works only if
 * no headers have been sent before and must be called via a normal http
 * GET request (not in a JSONRPC request). It is the server companion of
 * the qcl.dialog.ServerProgress calls on the client. 
 */
class ServerProgress extends Dialog
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
   * Constructor
   * @param string $widgetId The id of the progress widget
   */
  public function __construct($widgetId)
  {
    $this->widgetId = $widgetId;
    header("Cache-Control: no-cache, must-revalidate");
    header("Expires: Mon, 26 Jul 1997 05:00:00 GMT");
    header("Pragma:");
    header('Content-Encoding: chunked');
    header('Transfer-Encoding: chunked');
    header('Content-Type: text/html');
    header('Connection: keep-alive');  

    flush();
    @apache_setenv('no-gzip', 1);
    @ini_set('output_buffering', 0);
    @ini_set('zlib.output_compression', 0);
    @ini_set('implicit_flush', 1);
    flush();
  }
  
  /**
   * Internal function to send a chunk of data 
   */
  protected function send($chunk)
  {
    // add padding to force Safari and IE to render
    if( strlen($chunk) < 1024)
    {
      $chunk = str_pad( $chunk, 1024 );
    }
    $chunk .= "<br/>"; // needed by Safari and Internet Explorer
    echo sprintf("%x\r\n", strlen($chunk));
    echo $chunk;
    echo "\r\n";
    flush();
    ob_flush();
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
   * API function to set the state of the progress par 
   * @param integer $value The valeu of the progress, in percent
   */
  public function setProgress($value, $message=null, $newLogText=null)
  {
    $nl = $this->getNewlineChar();
    $js = '<script type="text/javascript">';
    //$js .= $nl . sprintf('console.log("%d, %s, %s");',$value, $message, $newLogText);
    $js .= $nl . "window.top.qcl.__{$this->widgetId}.set({";
    $js .= $nl . sprintf( 'progress:%d',$value);            
    if( $message )    $js .= sprintf(',message:"%s"', $message);
    if( $newLogText)  $js .= sprintf(',newLogText:"%s"', $newLogText);
    $js .= $nl . '});</script>' . $nl;
    $this->send( $js );
  }

  /**
   * API function to dispatch a client message (scope: application)
   * @param string $name Name of message
   * @param mixed|null $data Message data
   *
   */
  public function dispatchClientMessage($name,$data=null)
  {
    $nl = $this->getNewlineChar();
    $js = '<script type="text/javascript">';
    $js .= $nl . 'window.top.qx.event.message.Bus.getInstance().dispatchByName("' . $name . '",';
    $js .= $nl . json_encode($data);
    $js .= $nl . ');</script>' . $nl;
    $this->send( $js );
  }
  
  /**
   * API function to dispatch a event (scope: progress widget)
   * @param string $name Name of event
   * @param mixed|null $data event data
   *
   */
  public function dispatchClientEvent($name,$data=null)
  {
    $nl = $this->getNewlineChar();
    $js = '<script type="text/javascript">';
    $js .= $nl . 'window.top.qx.core.Init.getApplication().getWidgetById("' . $this->widgetId  . '").fireDataEvent("' . $name . '",';
    $js .= $nl . json_encode($data);
    $js .= $nl . ');</script>' . $nl;
    $this->send( $js );
  }  

  /**
   * API function to trigger an error alert
   * @param string $message
   */
  public function error($message)
  {
    $this->setProgress(100);
    $nl = $this->getNewlineChar();
    $js = '<script type="text/javascript">';
    $js .= $nl . 'window.top.dialog.Dialog.error("' . $message . '");';
    $js .= $nl . "window.top.qcl.__{$this->widgetId}.hide();";
    $js .= $nl . '</script>' . $nl;
    $this->send( $js );
    $this->send("");
    exit;
  }

  /**
   * Must be called on completion of the script
   * @param string|null Optional message that will be shown in an alert dialog
   */
  public function complete($message=null)
  {
    $this->setProgress(100);
    if ( $message )
    {
      $nl = $this->getNewlineChar();
      $js = '<script type="text/javascript">';
      $js .= $nl . 'window.top.dialog.Dialog.alert("' . $message . '");';
      $js .= $nl . '</script>' . $nl;
      $this->send( $js );      
    }
    $this->send("");
    exit(); // necessary to not mess up the http response
  }
}