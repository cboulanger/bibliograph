const test = require("tape");
const {init, shutdown, login, logout} = require("./init");

test("login users", async assert => {
  const {page, browser} = await init("bibliograph.setup.completed");
  await login(page, "user2", "user");
  await logout(page);
  await login(page, "admin", "admin");
  assert.ok(true);
  shutdown(page, browser);
});
