const Bibliograph = require("./Bibliograph");
const process = require("process");
const test = require("tape");

const app = new Bibliograph({
  browserType: process.env.BROWSER_TYPE,
  url: process.env.APP_URL,
  verbose: true,
  headless: false
});

test.onFinish(async () => {
  await app.shutdown();
  process.exit(0);
});

module.exports = app;
