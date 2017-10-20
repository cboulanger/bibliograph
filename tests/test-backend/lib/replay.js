/**
 * Replays pre-recorded JSONRPC traffic, adapting the source data in the
 * case of dynamic values. 
 * 
 * The behavior of the script can be controlled with the following environment
 * variables:
 * - JSONRPC_COMP_KEYSONLY : 1 - compare the structure of the json data only, 
 *   0/empty: compare keys and values.
 * - JSONRPC_RECORD : record the ongoing jsonrpc traffic and display it at the 
 *   end. 
 */

const 
  assert = require('assert'),
  fs = require("fs"),
  path = require("path"),
  process = require("process"),
  r2 = require("r2"),
  json_diff = require("json-diff");
  

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
 * @param {string} name The name of the test
 */
async function replay(name) {
  let test_path = path.join(__dirname,"..","test");
  let replay_path = `${test_path}/replay/${name}.json`;
  let record_path = `${test_path}/record/${name}.json`;

  let replay_data = JSON.parse(
    fs.readFileSync(replay_path, "utf-8"),
    "utf-8"
  );

  let tape = [];
  let params = null;

  for (let data of replay_data) {
    let request = data.request;
    let origReqId = request.id;

    // overwrite the sessionId and request Id;
    request.server_data.sessionId = sessionId;
    request.id = ++requestId;

    // override parameters if dynamic
    if( params ){
      request.params = params;
      params = null; 
    }
    
    // log request
    console.log(`travis_fold:start:Request_${requestId}\r`);
    console.info(`    * Sending request #${requestId} (${origReqId}): ${request.service}.${request.method}`);
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

    // we have a valid response now
    let received = result;
    let expected = data.response;
    expected.id = received.id;

    // record for later replay
    tape.push( {request,response:received} );

    // handle server error
    if (received.error) {
      // Handle silent errors
      if ( received.error.silent ){
        // Ingnore "Server busy messages"
        if( received.error.message.search(/server busy/i)){
          continue;
        }
        console.log(`      ! Ignoring silent error: ${received.error.message}.`);
        continue;
      } else {
        console.log(`travis_fold:start:Log_${requestId}\r`);
        console.log(`      ! Error: ${received.error.message}.`);
        console.log( fs.readFileSync("/tmp/bibliograph.log", "utf-8") );
        console.log(`travis_fold:end:Log_${requestId}\r`);
        throw new Error("Error in response: " + received.error.message);
      }
    }

    // check messages for values that need to be adapted dynamically
    let messages = received.result.messages;
    let message;
    if ( messages instanceof Array && messages.length ){
      // adapt sessionId    
      message = messages.find( (message) => message.name == "setSessionId" );
      if (message){
        //console.log("Found setSessionId message...");
        sessionId = message.data;  
        expected.result.messages.map( (message) => {
          message.name == "setSessionId" 
        });     
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
      message = messages.find( (message) => message.name == "qcl.ui.dialog.Dialog.createDialog" );
      if( message && message.data.method == "next" ) {
        //console.log("Found Shelf ID,  setting params.");
        params = message.data.params;
        params.unshift(true);
      }
    }    

    // if recording, skip verification
    //if ( process.env.JSONRPC_RECORD ) continue;
    
    // compare received and expected json response
    if( json_diff.diff(received,expected,{keysOnly:process.env.JSONRPC_COMP_KEYSONLY||false}) ) {
      console.log(`travis_fold:start:Diff_${requestId}\r`);
      console.log(`      ! Response differs from playback response:`);
      console.log(json_diff.diffString(received,expected));
      console.log(`travis_fold:end:Diff_${requestId}\r`); 
    }
  }
  console.log(`travis_fold:start:Recording\r`);
  console.log(`Recorded jsonrpc traffic for later replay as '${name}.json':`);
  dump(tape);
  console.log(`travis_fold:end:Recording\r`); 
}

module.exports = replay;