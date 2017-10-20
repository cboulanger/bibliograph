/* global describe, it */
const 
  assert = require('assert'),
  replay = require('./lib/replay');

// build env
let c9 = (process.env.IP && process.env.PORT);

describe('Bibliograph', async function() {
  this.timeout(20000);
  if( ! c9 ) it ('should setup the application', async () => {
    await replay(__dirname + "/data/setup.json");
  });
  it("should wait 3 seconds to let setup finish", function(done){
    setTimeout(function() {
        done();
    }, 3000);
  });
  it('should boot', async () => {
    await replay(__dirname + "/data/boot.json");
  });  
});