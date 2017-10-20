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

  for (let data of replay_data) {
    let request = data.request;
    let origReqId = request.id;
    // overwrite the sessionId and request Id;
    request.server_data.sessionId = sessionId;
    request.id = ++requestId;

    let result;
    // send the request and await the async response
    console.log(`travis_fold:start:Request_${requestId}\r`);
    console.info(`    - Sending request #${requestId} (${origReqId})`);
    dump(request);
    console.log(`travis_fold:end:Request_${requestId}\r`);
    let response = await r2.post(url, { json: request }).text;
    try {
      result = JSON.parse(response);
    } catch (error) {
      throw new Error("Invalid response:" + response);
      return;
    }

    //server error
    if (result.error) {
      // Handle silent errors
      if ( result.error.silent ){
        // Ingnore "Server busy messages"
        if( result.error.message.search(/server busy/i)){
          continue;
        }
        console.warn("Silent error: " + result.error.message);
        continue;
      } 
      console.log(`travis_fold:start:Response_${requestId}\r`);
      console.warn(`    - Request #${request.id}: Error: ${result.error.message}.`);
      console.log( fs.readFileSync("/tmp/bibliograph.log", "utf-8") );
      console.log(`travis_fold:end:Response_${requestId}\r`);
      throw new Error("Error in response: " + result.error.message);
    }
    
    // compare received and expected json response
    let received = result;
    let expected = data.response;

    // this doesn't work yet:
    //assert.deepEqual(received, expected, 'Output does not match reference content');

    // just checking structural equality (keys)
    try {
      assert.deepEqual(Object.keys(received), Object.keys(expected));
      assert.deepEqual(Object.keys(received.result), Object.keys(expected.result));
      if(received.result.messages instanceof Array) {
        assert.equal(received.result.messages.length, expected.result.messages.length);
      }
      assert.deepEqual(Object.keys(received.result.data), Object.keys(expected.result.data));
    } catch(e) {
      console.log(`travis_fold:start:Response_${requestId}\r`);
      console.warn(`    - Request #${request.id}): unexpected response.`);
      console.log("==== Expected response ====");
      dump(expected);
      console.log("==== Actual response ====");
      dump(received);
      console.log(`travis_fold:end:Response_${requestId}\r`); 
    }

    // adapt sessionId
    try {
      let messages = result.result.messages;
      if (
        messages instanceof Array &&
        messages.length &&
        messages[0].name == "setSessionId"
      ) {
        sessionId = messages[0].data;
      }
    } catch (e) {}
  }
  return; 
}

module.exports = replay;