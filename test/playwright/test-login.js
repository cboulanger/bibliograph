const test = require("tape");
const {init, shutdown} = require("./init");

async function login(page, user, password) {
  console.info(`### Authenticating user '${user}'...`);
  await page.clickByQxId("toolbar/login");
  await page.waitForWidgetByQxId("windows/login");
  await page.fillByQxId("windows/login/form/username", user);
  await page.fillByQxId("windows/login/form/password", password);
  await page.clickByQxId("windows/login/buttons/login");
  await page.waitForWidgetByQxId("toolbar/user", {timeout:60000, state:"visible" });
  console.info(`### User '${user}' authenticated. Waiting for tasks to finish ...`);
  await page.waitForFunction("!qx.core.Init.getApplication().getTaskMonitor().getBusy()", {polling: 100});
}

async function logout(page) {
  console.info(`## Logging out ...`);
  await page.clickByQxId("toolbar/logout");
  await page.waitForWidgetByQxId("toolbar/user", {timeout:60000, state:"hidden" });
  console.info(`## Logged out. Waiting for tasks to finish ...`);
  await page.waitForFunction("!qx.core.Init.getApplication().getTaskMonitor().getBusy()", {polling: 100});
}

test("login users", async assert => {
  const {page, browser} = await init("bibliograph.setup.completed");
  await login(page, "user2", "user");
  await logout(page);
  await login(page, "admin", "admin");
  assert.ok(true);
  shutdown(page, browser);
});
