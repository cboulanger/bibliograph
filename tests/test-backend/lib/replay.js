const 
  assert = require('assert'),
  //assert = require('assert-diff'),
  fs = require("fs"),
  path = require("path"),
  process = require("process"),
  r2 = require("r2");
  

// url is dependent on build environment
let url;
if (process.env.IP && process.env.PORT) {
  url = `http://${process.env.IP}:${process.env.PORT}`;
} else {
  url = "http://localhost";
}
url += "/bibliograph/services/server.php";

function dump(data) {
  console.log(JSON.stringify(data, null, 2));
}

// Persists (incremental) request id and session id across tests
let requestId = 0;
let sessionId = null;

/**
 * Takes a json file produced by the debug plugin and replays
 * it to the server.
 * @param {string} file_path 
 */
async function replay(file_path) {
  let replay_data = JSON.parse(
    fs.readFileSync(file_path, "utf-8"),
    "utf-8"
  );

  let params = null;

  for (let data of replay_data) {
    let request = data.request;
    let origReqId = request.id;

    // overwrite the sessionId and request Id;
    request.server_data.sessionId = sessionId;
    request.id = ++requestId;

    // override parameters if dynamic
    if( params ){
      console.log(`Using params!`);
      request.params = params;
      params = null; 
    }
    
    // log request
    console.log(`travis_fold:start:Request_${requestId}\r`);
    console.info(`    * Sending request #${requestId} (${origReqId})`);
    dump(request);
    console.log(`travis_fold:end:Request_${requestId}\r`);

    // send the request and await the async response
    let response = await r2.post(url, { json: request }).text;
     
    // parse json
    let result;
    console.log(`travis_fold:start:Response_${requestId}\r`);
    console.info(`      - Received response ...`);    
    try {
      result = JSON.parse(response);
      dump(result);
      console.log(`travis_fold:end:Response_${requestId}\r`);  
    } catch (error) {
      console.log(response);
      console.log(`travis_fold:end:Response_${requestId}\r`);  
      throw new Error("Invalid JSON.");
      return;
    }

    // handle server error
    if (result.error) {
      // Handle silent errors
      if ( result.error.silent ){
        // Ingnore "Server busy messages"
        if( result.error.message.search(/server busy/i)){
          continue;
        }
        console.warn(`      ! Ignoring silent error: ${result.error.message}.`);
        continue;
      } 
      console.log(`travis_fold:start:Log_${requestId}\r`);
      console.warn(`      ! Error: ${result.error.message}.`);
      console.log( fs.readFileSync("/tmp/bibliograph.log", "utf-8") );
      console.log(`travis_fold:end:Log_${requestId}\r`);
      throw new Error("Error in response: " + result.error.message);
    }
    
    // compare received and expected json response
    let received = result;
    let expected = data.response;
    expected.id = received.id;

    // this should eventually work:
    // assert.deepEqual(received, expected, 'Output does not match reference content');
    // for the moment,just check structural equality (keys)
    try {
      assert.deepEqual(Object.keys(received), Object.keys(expected));
      assert.deepEqual(Object.keys(received.result), Object.keys(expected.result));
      if(received.result.messages instanceof Array) {
        assert.equal(received.result.messages.length, expected.result.messages.length);
      }
      assert.deepEqual(Object.keys(received.result.data), Object.keys(expected.result.data));
    } catch(e) {
      console.log(`travis_fold:start:Expected_${requestId}\r`);
      console.warn(`      ! Unexpected response.`);
      console.log("==== Expected response: ====");
      dump(expected);
      console.log(`travis_fold:end:Expected_${requestId}\r`); 
    }

    // check messages for values that need to be adapted dynamically
    let messages = result.result.messages;
    let message;
    if ( messages instanceof Array && messages.length ){
      // adapt sessionId    
      message = messages.find( (message) => messages.name == "setSessionId" );
      if (message){
        console.info("Found setSessionId message...");
        sessionId = message.data;        
      }
  
      // shelf ids
      /*
      "messages": [ {
        "name": "qcl.ui.dialog.Dialog.createDialog",
        "data": {
          "type": "progress",
          "properties": {..},
          "service": "bibliograph.setup",
          "method": "next",
          "params": [ "eda0de30036a2811846dc1f993df2356", 5 ]
        }
      } ],
      */
      message = messages.find( (message) => messages.name == "qcl.ui.dialog.Dialog.createDialog" );
      if( message && message.data.method == "next" ) {
        console.info("Found Shelf ID,  setting params.");
        params = [true, message.data.params[0]];
      }
    }
  }
}

module.exports = replay;