const fs = require("fs"),
  path = require("path"),
  process = require("process"),
  //assert  = require('assert-diff'),
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

function replay(path) {
  let replay_data = JSON.parse(
    fs.readFileSync(path, "utf-8"),
    "utf-8"
  );
  let sessionId = null;
  for (let data of replay_data) {
    let request = data.request;
    if (request.id > 5) return;
    request.server_data.sessionId = sessionId;
    console.log(">>>> Request");
    dump(request);
    let result;
    let response = await r2.post(url, { json: request }).text;
    try {
      result = JSON.parse(response);
    } catch (error) {
      throw new Error("Invalid response:" + response);
      return;
    }
    if (result.error) {
      throw new Error("Error in response: " + error);
    }
    //assert.deepEqual(result, data.response, 'Output does not match reference content');
    console.log("<<<< Response (received)");
    dump(result);
    console.log("==== Response (expected)");
    dump(data.response);
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