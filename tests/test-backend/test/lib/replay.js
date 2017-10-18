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

async function replay(path) {
  let replay_data = JSON.parse(
    fs.readFileSync(path, "utf-8"),
    "utf-8"
  );
  let sessionId = null;
  for (let data of replay_data) {
    let request = data.request;
    request.server_data.sessionId = sessionId;

    let result;
    let response = await r2.post(url, { json: request }).text;
    try {
      result = JSON.parse(response);
    } catch (error) {
      throw new Error("Invalid response:" + response);
      return;
    }
    if (result.error) {
      console.log(">>>> Request");
      dump(request);      
      throw new Error("Error in response: " + error);
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
      console.log(">>>> Request");
      dump(request);
      console.log("<<<< Response (received)");
      dump(received);
      console.log("==== Response (expected)");
      dump(expected);
      console.log("#### Server log");
      console.log( fs.readFileSync("/tmp/bibliograph.log", "utf-8") );
      throw new Error("JSONRPC response does not match expectations.");
    }

    // adapt sessionId
    let messages = result.result.messages;
    if (
      messages instanceof Array &&
      messages.length &&
      messages[0].name == "setSessionId"
    ) {
      sessionId = messages[0].data;
    }
  }
}

module.exports = replay;