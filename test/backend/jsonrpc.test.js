/* global describe, it */
const 
  assert = require('assert'),
  jayson = require('jayson/promise'),
  raptor = require('raptor-client')
  //replay = require('../lib/replay')
  ;

// build env
let c9 = (process.env.IP && process.env.PORT);

describe('Bibliograph', async function() {
  this.timeout(20000);
  // if( ! c9 ) it ('should setup the application', async () => {
  //   await replay("setup");
  // });
  // it('should boot', async () => {
  //   await replay("boot");
  // }); 
  it('should say hello', async() =>{
    return true;
  });
});