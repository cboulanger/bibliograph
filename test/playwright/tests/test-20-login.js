const test = require("tape");
const app = require("../app");

test("login users", async assert => {
  await app.init();
  console.log("# Login registered user");
  await app.login("test", "test1234");
  await app.logout();
  assert.ok(true);
  console.log("# Login LDAP user");
  await app.login("timtest", "cb-test");
  await app.logout();
  assert.ok(true);
  await app.shutdown();
});

