<?php

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
   * Send a JSONRPC request. 
   * Authentication is done with the "Authorization: Bearer <token>" header.
   * The token is retrieved from the token() method. 
   * Throws if the transport layer or the RPC method return an error.
   *
   * @param string $serviceController
   *    The name of the service (=controller) to be called
   * @param string $method
   *    The name of the RPC method
   * @param array $params
   *    The parameters to be passed to the method
   * @param boolean $allowError Allow an error response. Default false
   * @return void
   */
  public function sendJsonRpcRequest( $service, $method, array $params=[], $allowError=false )
  {
    /** @var int $id the id of the request */
    static $id = 1;
    
    // headers
    $this->haveHttpHeader('Content-Type', 'application/json');
    $this->haveHttpHeader('Accept', 'application/json'); 

    // payload
    $json = [
      'jsonrpc' => '2.0',
      'method'  => $method,
      'params'  => $params,
      'id'      => $id++
    ];

    $path = $service;

    // authentication
    $token = $this->token();
    if ( $token ){
      $this->haveHttpHeader('Authorization', 'Bearer ' . $token); 
     //$path .= "&auth=$token"; 
    }
    
    // send request and validate response
    $this->sendPOST( $path, $json );
    $this->canSeeResponseCodeIs(200);
    $this->seeResponseIsJson();
    if( ! $allowError ){
      $this->dontSeeJsonRpcError();
      $this->seeJsonRpcResult();
    }
  }
  
  /**
   * Expects a token in the json response and caches it.
   *
   * @return void
   */
  public function seeAndSaveTokenInJsonResponse()
  {
    $this->seeResponseJsonMatchesJsonPath('$.result.token');
    $this->token($this->grabDataFromResponseByJsonPath('$.result.token')[0]);
  }

  /**
   * Log in anonymously
   *
   * @return void
   */
  public function loginAnonymously()
  {
    $this->sendJsonRpcRequest( "access","authenticate", [] );
    $this->seeAndSaveTokenInJsonResponse();
  }

  /**
   * Log in with a username and password
   *
   * @return void
   */
  public function loginWithPassword( $user, $password )
  {
    $this->sendJsonRpcRequest( "access","authenticate", [ $user, $password ] );
    $this->seeAndSaveTokenInJsonResponse();
  }

  /**
   * Throws if no RPC result is in the response
   *
   * @return void
   */
  public function seeJsonRpcResult()
  {
    $this->seeResponseJsonMatchesJsonPath('$.result');
  }

  /**
   * Returns the jsonrpc result
   *
   * @return mixed
   */
  public function grabJsonRpcResult()
  {
    $this->seeJsonRpcResult();
    return $this->grabDataFromResponseByJsonPath('$.result')[0];
  }

  /**
   * Throws if the RPC method does not return an error
   * @param string|null $message Optionally checks the message
   * @param int|null $code Optionally checks the error code
   * @return void
   */
  public function seeJsonRpcError($message=null, $code=null)
  {
    $this->seeResponseJsonMatchesJsonPath('$.error');
    if( $message){
      $this->assertContains( $message, $this->grabDataFromResponseByJsonPath('$.error.message')[0] );
    }
    if( $code){
      $this->assertEquals( $this->grabDataFromResponseByJsonPath('$.error.code')[0], $code );
    }
  }

  /**
   * Throws if the RPC method does not return an error
   *
   * @return void
   */  
  public function dontSeeJsonRpcError()
  {
    $error = $this->grabDataFromResponseByJsonPath('$.error');
    //if( count($error) ) codecept_debug(json_decode($error[0]));
    $this->dontSeeResponseJsonMatchesJsonPath('$.error');
  }

  /**
   * Returns the jsonrpc result
   *
   * @return mixed
   */
  public function grabJsonRpcError()
  {
    $this->seeJsonRpcError();
    return $this->grabDataFromResponseByJsonPath('$.error')[0];
  }

  /**
   * Shorthand method aliasing grabDataFromResponseByJsonPath($path)[0]
   *
   * @param string $path
   * @return mixed
   */
  public function getByJsonPath($path)
  {
    return $this->grabDataFromResponseByJsonPath($path)[0];
  }

  /**
   * Logs out current user
   *
   * @return void
   */
  public function logout()
  {
    $this->sendJsonRpcRequest('access','logout');
    $this->assertSame( $this->grabJsonRpcResult(), "OK" );   
  }
}