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
 * HTTP request model
 * @todo rewrite using HttpRequest class
 */
class qcl_io_remote_HttpRequest
  extends qcl_core_Object
{

  /**
   * Request method, only POST implemented so far
   */
  var $method  = "POST";

  /**
   * Request data
   */
  var $data = null;

  /**
   * Optional headers to send with the request
   */
  var $headers  = array ();

  /**
   * Request timeout
   */
  var $timeout = 3;

  /**
   * Request content type
   */
  var $contentType ="application/x-www-form-urlencoded";


  /**
   * constructor
   * @return \qcl_io_remote_HttpRequest
   * @param $controller Object
   * @param null $url
   * @param $method string[optional]
   * @internal param array $data [optional]
   */
  function __construct( $controller, $url=null, $method = "POST" )
  {
    parent::__construct( $controller);
    $this->setUrl($url);
    $this->setMethod($method);
  }

  /**
   * setter for method
   * @param $method string
   * @throws qcl_core_NotImplementedException
   * @return void
   */
  function setMethod($method)
  {
    if ($method != "POST")
    {
      throw new qcl_core_NotImplementedException("Method $method not implemented.");
    }
    $this->method = $method;
  }

  /**
   * getter for method
   * @return string
   */
  function getMethod()
  {
    return $this->method;
  }

  /**
   * Sets the GET/POST data, which must be an associative array
   * @param $data
   * @throws InvalidArgumentException
   * @internal param array $method
   * @return void
   */
  function setData($data)
  {
    if ( is_array( $data ) )
    {
      $this->data = $data;
    }
    else
    {
      throw new InvalidArgumentException("qcl_http_request::setData : argument must be associative array.");
    }
  }


  /**
   * getter for data
   * @return array
   */
  function getData()
  {
    return $this->data;
  }

  /**
   * setter for url
   * @param $url
   * @internal param string $method
   * @return void
   */
  function setUrl($url)
  {
    $this->url = $url;
  }

  /**
   * getter for url
   * @return string
   */
  function getUrl()
  {
    return $this->url;
  }

  function setTimeout( $timeout )
  {
    $this->timeout = $timeout;
  }

  /**
   * add a http header
   * @param $header string
   */
  function addHeader($header)
  {
    $this->headers[] = $header;
  }

  /**
   * sends the request depending on method and PHP version
   * @throws LogicException
   * @throws InvalidArgumentException
   * @return string
   */
  function send()
  {
    /*
     * check url
     */
    if ( ! $this->url )
    {
      throw new InvalidArgumentException("qcl_http_request::send : No url provided.");
    }

    /*
     * create header string from array
     */
    $headers = count($this->headers) > 0 ? implode("\r\n", $this->headers) . "\r\n" : null;

    /*
     * encode data
     */
    $data = "\n\r";
    if ( is_array( $this->data ) )
    {
      foreach ( $this->data as $key => $value )
      {
        $data .= urlencode($key) . "=" . $this->safe_urlencode($value). "&";
      }

      /*
       * save for debugging
       */
      $this->data = $data;
    }
    else
    {
      $data = $this->data;
    }

    /*
     * send request
     * @todo Does this work in PHP5 also? It should
     */
    if ($this->method == "POST")
    {
      $response = $this->post($this->url, $data, $this->timeout, $headers);
    }
    else
    {
      throw new LogicException("Request method {$this->method} not yet supported.");
    }

    /*
     * save response
     */
    $this->response = $response;

    return $this->response;
  }

  function safe_urlencode($value)
  {
    /*
     * urlencode
     */
    $value = urlencode( $value );

    /*
     * double-encode ampersands
     */
    $value = str_replace("%26","%2526", $value );

    /*
     * encode hash sign
     */
    $value = str_replace("#", "%23", $value);

    return $value;
  }

  function safe_urldecode($value)
  {
    /*
     * decode
     */
    $value = urldecode( $value );

    return $value;
  }

  /**
   * Get the raw response data including http headers
   * @return string
   */
  function getResponse()
  {
    return $this->response;
  }

  /**
   * Get response content without http headers
   * @return string
   */
  function getResponseContent()
  {
    $content = str_replace("\r\n", "\n", $this->response );
    return substr( $content, strpos($content,"\n\n") +1 );
  }

  /**
   * Returns an array of headers
   * @return array
   */
  function getHeaders()
  {
    $content = str_replace("\r\n", "\n", $this->response );
    $headers = explode("\n", substr( $content, 0, strpos( $content,"\n\n" ) +1 ) );
    array_shift( $headers ); // remove http response code
    array_pop( $headers ); // remove empty line at the end
    return $headers;
  }

  /**
   * Returns a map of headers
   * @return array
   */
  function getHeaderMap()
  {
    $headers = $this->getHeaders();
    $map = array();
    foreach( $headers as $header )
    {
      $splitPos = strpos( $header, ":" );
      $key = substr( $header, 0, $splitPos );
      $value = trim( substr( $header, $splitPos+1 ) );
      if ($key) $map[$key] = $value;
    }
    return $map;
  }

  /**
   * PHP4/PHP5 POST request
   * taken from http://www.enyem.com/wiki/index.php/Send_POST_request_(PHP)
   *
   * @param string $url
   * @param string $data
   * @param int $timeout
   * @param array [optional] $optional_headers
   * @throws LogicException
   * @return string
   * @todo use curl if available
   */
  function post( $url, $data, $timeout=10, $optional_headers = null )
  {
    $start    = strpos($url, '//') + 2;
    $end      = either(strpos($url,"/",$start),strlen($url));
    $portPos  = strpos($url,":",$start);

    if (  $portPos > 0 )
    {
      $port   = (int) substr($url,$portPos+1, ($end-$portPos) -1 );
      $host   = substr($url, $start, $portPos - $start);
      $domain = substr($url, $end);
    }
    else
    {
      $host   = substr($url, $start, $end - $start);
      $port   = 80;
      $domain = substr($url, $end);
    }

    /*
     * Connect
     */
    //$this->debug("Connecting to $host, port $port, path $domain.");
    $errno = ""; $errstr="";
    $fp = fsockopen($host, $port, $errno, $errstr, $timeout );

    /*
     * handle errors
     */
    if ( ! $fp )
    {
      throw new LogicException("Cannot connect to $host, port $port, path $domain: $errstr");
    }

    /*
     * connection successful, write headers
     */
    fputs($fp, "POST $domain HTTP/1.1\r\n");
    fputs($fp, "Host: $host\r\n");
    if ($optional_headers)
    {
      fputs($fp, $optional_headers);
    }

    /*
     * content type
     */
    if ( $this->contentType )
    {
      fputs($fp, "Content-type: {$this->contentType}\r\n");
    }

    fputs($fp, "Content-length: " . strlen($data) . "\r\n\r\n");
    fputs($fp, "$data\r\n\r\n");

    $response = "";
    $time = time();

    /*
     * Get response. Badly behaving servers might not maintain or close
     * the stream properly, we need to check for a timeout if the server
     * doesn't send anything. At the same time, there is a bug in php that
     * prevents the correct setting of feof when a stream is opened with
     * fsockopen().
     *
     * @todo rewrite this using the curl library or native stream functions
     * when upgrading to php5
     */
    stream_set_blocking ( $fp, 0 );
    $clt = "Content-Length:"; $cll = strlen($clt); $len = 0;
    while ( ! feof( $fp )  and ( time() - $time <  $timeout ) )
    {
      if ( $r = fgets( $fp, 1024*8 ) )
      {
        $response .= $r;
        if ( ! strncmp( $r, $clt, $cll ) )
        {
          $len = ( (int) substr( $r, $cll ) ) + strlen( $response );
        }
        $time = time();
        if ( feof( $fp ) ) break;
        if ( $len and strlen( $response ) >= $len ) break;
      }
    }

    /*
     * Close stream and return response data.
     */
    fclose($fp);
    return $response;
  }
}
?>