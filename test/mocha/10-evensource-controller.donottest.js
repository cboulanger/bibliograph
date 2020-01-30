/* global describe, it */
const 
  config = require('./config'),
  assert = require('assert'),
  EventSource = require('eventsource'),
  raptor = require('raptor-client'),
  process = require('process'),
  equals = require('array-equal');

describe('The EventSource target', async function() {
  this.timeout(config.timeout);
  const client = raptor(config.url + "access");
  let options, session_id;

  it('should send standard compliant messages', async () => {
    const timeserver = new EventSource( config.url + "sse/time" );
    let counter = 0;
    process.stdout.write( "      Waiting for time server..." );
    await new Promise((resolve,reject)=>{
      timeserver.onmessage = (e) =>{
        process.stdout.write("\r\x1b[K");
        process.stdout.write("\r      The time is " + e.data);
        if ( ++counter > 5 ){
          console.log();
          timeserver.close();
          resolve();
        }
      };
      timeserver.onerror = (e) => {
        timeserver.close();
        reject(new Error("Unexpected error from timeserver"));
      }; 
    });
  }); 

  // it('should not allow access without authentication', async () => {
  //   const es_without_auth = new EventSource( config.url + "sse");
  //   await new Promise((resolve,reject)=>{
  //     // this should result in an error
  //     es_without_auth.onmessage = (e) =>{
  //       es_without_auth.close();
  //       if( e.data.startsWith("No auth token") ){
  //         resolve();
  //       }
  //       reject(new Error("Did not receive the expected error message"));
  //     };      
  //   });
  // }); 

  // get a token
  // it('needs authentication credentials that we will get from a login as Administrator', async () => {
  //   let { token, sessionId } = await client.send('authenticate',['admin','admin']);
  //   session_id = sessionId;
  //   options = { headers: {
  //       'X-Auth-Token': token,
  //       'X-Auth-Session-Id': sessionId
  //   }};
  // });   

  // it('should allow to listen for messages when authenticated', async () => {
  //   const es = new EventSource( config.url + "sse", options);
  //   process.stdout.write( "      Waiting for messages..." );
  //   await client.send('test/create_messages',[session_id]);
  //   await new Promise((resolve,reject)=>{
  //     es.onmessage = (e) => {
  //       console.log("      Message:");
  //       console.log(e.data);
  //       if( e.data = "done"){
  //         es.close();
  //         resolve();  
  //       }
  //     };
  //     es.onerror = (e) => {
  //       console.log("Unexpected error!");
  //       es.close();
  //       resolve();
  //     };
  //   });
  // }); 
});