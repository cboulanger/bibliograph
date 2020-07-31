const test = require("tape");
const {init, shutdown, login, logout} = require("./init");

test("login users", async assert => {
  await init("bibliograph.setup.completed");
  await login("user2", "user");
  await logout();
  await login("admin", "admin");
  await logout();
  assert.ok(true);
  await shutdown();
});
