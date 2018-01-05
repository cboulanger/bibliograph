<?php

namespace app\controllers\traits;

use \Graze\GuzzleHttp\JsonRpc;

// @todo Convert to component!

trait JsonRpcTrait
{
  /**
   * Cache the access token
   *
   * @param string|null $t If set, store the value as the current token
   * @return string The access token, if one has been set
   */
  protected function token($t=null)
  {
    static $token = null;
    if( $t ) $token = $t;
    return $token;
  }

  /**
   * Executes a JSONRPC call
   *
   * @param string $method
   * @param mixed|null $arguments
   * @param string|null $route
   * @return \Graze\GuzzleHttp\JsonRpc\Message\Response
   */
  protected function sendJsonRpc($method, $arguments=null, $route=null )
  {
    static $client = null;
    static $id = 0;

    if( $route ) $cachedRoute = $route;
    if( ! $client and ! $route ) throw new \InvalidArgumentException('Missing route!');

    // create and cache the client
    if( $route ){
      $url = 'http://localhost:8080/?r=' . $route;
      codecept_debug("New RPC Call to $url");  
      $client = JsonRpc\Client::factory($url);    
    }
    $request = $client->request(++$id, $method, $arguments);
    // add auth token
    $token = $this->token(); 
    if( $token ){
      $json = JsonRpc\json_decode( (string) $request->getBody(), true);
      $json['auth'] = $token;
      $body = JsonRpc\json_encode($json); 
      $request = $request->withBody( \GuzzleHttp\psr7\stream_for( $body ) );
    }
    return $client->send($request);
  }  
}