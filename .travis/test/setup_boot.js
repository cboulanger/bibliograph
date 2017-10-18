/* global describe, it */
const replay  = require('lib/replay');

describe('Bibliograph', () => {
  it('should boot and setup the application', async () => {
    replay(path.join(__dirname, "setup_boot.json"));
  });
});