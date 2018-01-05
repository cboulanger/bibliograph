/* global describe, it */
const 
  config = require('./config'),
  assert = require('assert'),
  EventSource = require('eventsource'),
  raptor = require('raptor-client'),
  equals = require('array-equal');

describe('The EventSource target', async function() {
  this.timeout(config.timeout);
  const client = raptor(config.url + "access");
  let options;

  // get a token
  it('needs authentication credentials that we will get from a login as Administrator', async () => {
    let { token, sessionId } = await client.send('authenticate',['admin','admin']);
    options = { headers: {
        'X-Auth-Token': token,
        'X-Auth-Session-Id': sessionId
    }};
  }); 
  
  it('should not allow access without authentication', async () => {
    const es_without_auth = new EventSource( config.url + "sse");
    await new Promise((resolve,reject)=>{
      // this should result in an error
      es_without_auth.onmessage = (e) =>{
        es_without_auth.close();
        if( e.data.startsWith("No auth token") ){
          resolve();
        }
        reject(new Error("Did not receive the expected error message"));
      };      
    });
  }); 

  // it('should allow access with authentication', async () => {
  //   const es = new EventSource( config.url + "sse", options);
  //   await new Promise((resolve,reject)=>{
  //     // this should result in an error
  //     es.onmessage = (e) =>{
  //       console.log("Message!");
  //       console.log(e);
  //       es.close();
  //       resolve();
  //     };
  //     es.onerror = (e) => {
  //       console.log("Unexpected error!");
  //       es.close();
  //       resolve();
  //     };
  //   });
  // }); 
});