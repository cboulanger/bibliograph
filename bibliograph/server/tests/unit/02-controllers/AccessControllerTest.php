<?php

namespace app\tests\unit\controllers;

// for whatever reason, this is not loaded early enough
require_once __DIR__ . '/../../_bootstrap.php';

use Yii;

use \Graze\GuzzleHttp\JsonRpc;

use app\tests\unit\Base;

class AccessControllerTest extends Base
{
  /**
   * @var \UnitTester
   */
  protected $tester;

  public function _fixtures(){
    return require __DIR__ . '/../../fixtures/_access_models.php';
  }

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
   * @param string|null $token
   * @return \Graze\GuzzleHttp\JsonRpc\Message\Response
   */
  protected function sendJsonRpc($method, $arguments=null )
  {
    static $client = null;
    static $id = 0;

    // create and cache the client
    if( is_null($client) ){
      $client = JsonRpc\Client::factory('http://localhost:8080/?r=access');    
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

  // @todo needs real error message
  public function testUnauthorizedAccessFails()
  {
    $response = $this->sendJsonRpc('username');
    $this->assertEquals( null, $response->getRpcResult());
  }

  public function testAuthenticateWithPassword()
  {
    $response = $this->sendJsonRpc('authenticate',['admin','admin']);
    $result = $response->getRpcResult();
    $this->assertEquals( ['message', 'token', 'sessionId' ], array_keys($result) );
    $this->token($result['token']);
    // test token access
    $response = $this->sendJsonRpc('username');
    $this->assertEquals( 'admin', $response->getRpcResult());
    // test session persistence
    $this->assertEquals( 1, $this->sendJsonRpc('count')->getRpcResult() );
    $this->assertEquals( 2, $this->sendJsonRpc('count')->getRpcResult() );
    $this->assertEquals( 3, $this->sendJsonRpc('count')->getRpcResult() );
    // logout
    $this->assertEquals( "OK", $this->sendJsonRpc('logout')->getRpcResult() );
  }

  public function testLoginAnonymously()
  {
    $result = $this->sendJsonRpc('authenticate',[])->getRpcResult();
    $this->assertEquals( ['message', 'token', 'sessionId' ], array_keys($result) );
    $this->token($result['token']);
    // test token access
    $response = $this->sendJsonRpc('username');
    $this->assertStringStartsWith( 'guest', $response->getRpcResult());    
    // test persistence
    $this->assertEquals( 1, $this->sendJsonRpc('count')->getRpcResult() );
    $this->assertEquals( 2, $this->sendJsonRpc('count')->getRpcResult() );
    $this->assertEquals( 3, $this->sendJsonRpc('count')->getRpcResult() );
    // test logout
    $this->assertEquals( "OK", $this->sendJsonRpc('logout')->getRpcResult() );
  }

}
