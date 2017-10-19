/* global describe, it */
const 
  assert = require('assert'),
  replay = require('./lib/replay');

describe('Bibliograph', () => {
  it('should setup the application', async () => {
    await replay(__dirname + "data/setup.json");
  });
  it('should boot', async () => {
    await replay(__dirname + "data/boot.json");
  });  
});