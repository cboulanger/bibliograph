/* global describe, it */
const 
  config = require('./config'),
  assert = require('assert'),
  raptor = require('raptor-client'),
  equals = require('array-equal');

  // does not work
describe('The channel controller', async function() {
  this.timeout(config.timeout);
  const accessClient = raptor(config.url + "access");
  const channelClient = raptor(config.url + "channel");
  var token = null;

  it('After "user" and "admin" have logged in, should broadcasts admin\'s message to user' , async () => {
    let response = await accessClient.send('authenticate',['user','user']);
    console.log(response);
    let usertoken = response.token;
    response = await accessClient.send('authenticate',['admin','admin']);
    console.log(response);
    let admintoken = response.token;
    response = await channelClient.send('broadcast',['important announcement',{'foo':'bar'}], admintoken);
    console.log(response);
    response = await accessClient.send('fetch',null, usertoken);
    console.log(response);
  });
});