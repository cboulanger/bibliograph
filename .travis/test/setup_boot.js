/* global describe, it */
const 
  fs      = require('fs'),
  assert  = require('assert-diff'),
  r2      = require('r2');

let url = "http://0.0.0.0:8080/bibliograph/services/server.php";

function dump(data){
  console.log(JSON.stringify(data,null,2));
}

describe('Bibliograph', () => {
  it('should boot and setup the application', async () => {
    let replay_data = JSON.parse( fs.readFileSync( __dirname + '/setup_boot.json','utf-8'), 'utf-8');
    let sessionId=null;
    for( let data of replay_data ){
      let request = data.request;
      if( request.id > 5 ) return;
      request.server_data.sessionId = sessionId;
      console.log(">>>> Request");
      dump(request);
      let result = await r2.post(url, { json: request }).json;
      console.log("<<<< Response (received)");
      dump(result);
      console.log("==== Response (expected)");
      dump(data.response);
      if (result.messages && result.messages.length && result.messages[0].name == "setSessionId" ){
        sessionId = result.messages[0].data;
      }
    }
    //assert.deepEqual(content, referenceContent, 'Output does not match reference content');
  });
});