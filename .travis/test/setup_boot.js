/* global describe, it */
const 
  assert = require('assert'),
  replay = require('./lib/replay');

describe('Bibliograph', () => {
  it('should boot and setup the application', async () => {
    await replay(__dirname + "/setup_boot.json");
  });
});