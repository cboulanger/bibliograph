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
    if( ! is_null($t) ) $token = $t;
    return $token;
  }

  /**
   * Clears the token
   */
  public function clearToken()
  {
    $this->token(false);
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
      'method'  => "$service.$method",
      'params'  => $params,
      'id'      => $id++
    ];

    $url = "/json-rpc";

    // authentication
    $token = $this->token();
    if ( $token ){
      $this->haveHttpHeader('Authorization', 'Bearer ' . $token);
    }

    // enable xdebug
    if (YII_DEBUG) {
      $this->setCookie("XDEBUG_SESSION",1);
    }

    // send request and validate response
    $this->sendPOST( $url, $json );
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
   * @return string The access token
   */
  public function seeAndSaveTokenInJsonResponse()
  {
    $this->seeResponseJsonMatchesJsonPath('$.result.token');
    $token = $this->grabDataFromResponseByJsonPath('$.result.token')[0];
    if( ! $token ){
      throw new RuntimeException( $this->grabDataFromResponseByJsonPath('$.result.error')[0]);
    }
    return $this->token($token);
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
   * Log in as an Adminstrator
   *
   * @return string The Access token
   */
  public function loginAsAdmin()
  {
    return $this->loginWithPassword( "admin", "admin" );
  }

  /**
   * Log in with a username and password
   *
   * @return string The access token
   */
  public function loginWithPassword( $user, $password )
  {
    $this->sendJsonRpcRequest( "access","authenticate", [ $user, $password ] );
    return $this->seeAndSaveTokenInJsonResponse();
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
   * Returns the jsonrpc result, removing the event transport layer
   *
   * @return mixed
   */
  public function grabRpcData()
  {
    $result = $this->grabJsonRpcResult();
    if (isset($result['type']) and $result['type'] === "ServiceResult"){
      return $result['data'];
    }
    return $result;
  }

  /**
   * Return events from the event transport, if any.
   * @return array
   */
  public function grabRpcEvents()
  {
    $result = $this->grabJsonRpcResult();
    if (isset($result['type']) and $result['type']==="ServiceResult"){
      return $result['events'];
    }
    return [];
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
    if ($message){
      $this->assertContains( $message, $this->grabDataFromResponseByJsonPath('$.error.message')[0] );
    }
    if ($code){
      $this->assertEquals( $this->grabDataFromResponseByJsonPath('$.error.code')[0], $code );
    }
  }

  /**
   * Throws if the RPC method does not return an error
   * @param bool $includeUserError Also throw if a runtime error occurs that is caused by user input
   * @return void
   */
  public function dontSeeJsonRpcError($includeUserError=true)
  {
    $this->dontSeeResponseJsonMatchesJsonPath('$.error');
    if($includeUserError) $this->dontSeeUserError();
  }

  /**
   * Returns the jsonrpc error
   *
   * @return mixed
   */
  public function grabJsonRpcError()
  {
    $this->seeJsonRpcError();
    return $this->grabDataFromResponseByJsonPath('$.error')[0];
  }

  /**
   * Throws if the RPC method returns a user error, which is currently
   * an error dialog event. This will change!
   * FIXME Once User errors are handled differently....
   * @return void
   * @throws RuntimeException
   */
  public function dontSeeUserError()
  {
    $events = $this->grabRpcEvents();
    if (count($events) and $event = array_first($events, function($item){
      return $item['name'] === "dialog"
        and $item['data']['type'] === "alert"
        and $item['data']['properties']['image'] ?? null === "dialog.icon.error";
    })){
      throw new RuntimeException($event['data']['properties']['message']);
    };
  }


  /**
   * Throws if the RPC method does not return a user error
   * @throws RuntimeException
   */
  public function seeUserError()
  {
    try{
      $this->dontSeeUserError();
    } catch (RuntimeException $e) {
      return;
    }
    throw new RuntimeException("Expected User Error was not thrown");
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
   * Compares the JSONRPC received with the given value as two pretty-printed
   * JSON strings and throws if differences exist. The result can be drilled into
   * using the key syntax from Yii's ArrayHelper
   *
   * @param mixed $result
   * @param string|\Closure|array $key
   * @param string $path
   * @see \yii\helpers\ArrayHelper::getValue()
   * @return void
   */
  public function compareJsonRpcResultWith( $result, $path=null )
  {
    $expected = json_encode( $result, JSON_PRETTY_PRINT );
    $received = $this->grabJsonRpcResult();
    if( ! is_null( $path) ){
      if( is_numeric($path) and is_array($received) ){
        $received = $received[$path];
      } else {
        $received = \yii\helpers\ArrayHelper::getValue($received, $path);
      }
    }
    $received = json_encode( $received, JSON_PRETTY_PRINT );
    $this->assertEquals($expected, $received);
  }

  /**
   * Compares the rpc data (with the event transport layer removed)
   * with the given value as two pretty-printed
   * JSON strings and throws if differences exist. The result can be drilled into
   * using the key syntax from Yii's ArrayHelper
   *
   * @param mixed $result
   * @param string|\Closure|array $key
   * @see \yii\helpers\ArrayHelper::getValue()
   * @return void
   */
  public function compareRpcDatatWith( $result, $path=null )
  {
    $expected = json_encode( $result, JSON_PRETTY_PRINT );
    $received = $this->grabRpcData();
    if( ! is_null( $path) ){
      if( is_numeric($path) and is_array($received) ){
        $received = $received[$path];
      } else {
        $received = \yii\helpers\ArrayHelper::getValue($received, $path);
      }
    }
    $received = json_encode( $received, JSON_PRETTY_PRINT );
    $this->assertEquals($expected, $received);
  }

  /**
   * Logs out current user
   *
   * @return void
   */
  public function logout()
  {
    $this->sendJsonRpcRequest('access','logout');
  }
}
