/* global describe, it */
const 
  assert = require('assert'),
  //jayson = require('jayson/promise'),
  raptor = require('raptor-client'),
  equals = require('array-equal')
  //replay = require('../lib/replay')
  ;

// build env
let c9 = (process.env.IP && process.env.PORT);
let host = "127.0.0.1";
let port = 8080;
let server_url = `http://${host}:${port}/?r=`;

describe('Bibliograph', async function() {
  this.timeout(20000);
  const client = raptor(server_url + "access");

  it('should not be able to access jsonrpc method without authentication', async () => {
    let response = await client.send('username');
    assert.equal( response, null );
  }); 

  var token = null;

  it('should be able to login anonymously', async () => {
    let response = await client.send('authenticate');
    assert( equals( Object.keys(response), ['message','token','sessionId'] ) );
    token = response.token;
    response = await client.send('username', null, token );
    assert( response.startsWith('guest') );
  });

  it('should maintain the session', async () => {
    assert.equal( 1, await client.send('count', null, token ) );
    assert.equal( 2, await client.send('count', null, token ) );
    assert.equal( 3, await client.send('count', null, token ) );
  });

  it('should be able to authenticate as Administrator with a password', async () => {
    let response = await client.send('authenticate',['admin','admin']);
    assert( equals( Object.keys(response), ['message','token','sessionId'] ) );
    token = response.token;
    response = await client.send('username', null, token );
    assert.equal( response, 'admin' );
  }); 

  it('should maintain the session', async () => {
    assert.equal( 1, await client.send('count', null, token ) );
    assert.equal( 2, await client.send('count', null, token ) );
    assert.equal( 3, await client.send('count', null, token ) );
  });

  // public function testLoginAnonymously()
  // {
  //   $result = $this->sendJsonRpc('authenticate',[])->getRpcResult();
  //   $this->assertEquals( ['message', 'token', 'sessionId' ], array_keys($result) );
  //   $this->token($result['token']);
  //   // test token access
  //   $response = $this->sendJsonRpc('username');
  //   $this->assertStringStartsWith( 'guest', $response->getRpcResult());    
  //   // test persistence
  //   $this->assertEquals( 1, $this->sendJsonRpc('count')->getRpcResult() );
  //   $this->assertEquals( 2, $this->sendJsonRpc('count')->getRpcResult() );
  //   $this->assertEquals( 3, $this->sendJsonRpc('count')->getRpcResult() );
  //   // test logout
  //   $this->assertEquals( "OK", $this->sendJsonRpc('logout')->getRpcResult() );
  // }

});