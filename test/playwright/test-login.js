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
  console.info(`### User '${user}' authenticated. Waiting for folder to reload...`);
  await page.waitForTimeout(10000); // loading folders
}

async function logout(page) {
  console.info(`## Logging out ...`);
  await page.clickByQxId("toolbar/logout");
  await page.waitForWidgetByQxId("toolbar/user", {timeout:60000, state:"hidden" });
  console.info(`## Logged out. Waiting for folder to reload...`);
  await page.waitForTimeout(10000); // loading folders
}

test("login users", async assert => {
  const {page, browser} = await init("bibliograph.setup.completed");
  await login(page, "user2", "user");
  await logout(page);
  await login(page, "admin", "admin");
  assert.ok(true);
  shutdown(page, browser);
});
