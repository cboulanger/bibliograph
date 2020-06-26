const test = require("tape");
const process = require("process");
const {init, qxSelector} = require("init");

test("login user", async assert => {
  try {
    const {page, browser} = await init();
    await page.click(qxSelector("toolbar/login"));
    await page.click(qxSelector("windows/login/form/username"));
    await page.keyboard.press("Tab");
    await page.waitForTimeout(500);
    assert.notEqual(page.url(), url);
    await browser.close();
  } catch (e) {
    console.error(`Error: ${e.message}`);
    process.exit(1);
  }
});
