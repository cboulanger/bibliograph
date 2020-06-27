const test = require("tape");
const {init, shutdown} = require("./init");

test("login user", async assert => {
  const {page, browser} = await init("bibliograph.setup.completed");
  console.log("Bibliograph ready");
  await page.clickByQxId("toolbar/login");
  await page.waitForWidgetByQxId("windows/login");
  await page.fillByQxId("windows/login/form/username", "user2");
  await page.fillByQxId("windows/login/form/password", "user");
  await page.clickByQxId("windows/login/buttons/login");
  await page.waitForTextByQxId("toolbar/user", "Normal User");
  await page.waitForTimeout(10000);
  shutdown(page, browser);
});
