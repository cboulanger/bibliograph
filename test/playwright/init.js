const process = require("process");
const playwright = require("playwright");
const {addQxPageHelpers} = require("./playwright-qx");

// configuration
const browserType = process.env.BROWSER_TYPE || "chromium";
const app_url = process.env.APP_URL || `http://localhost/bibliograph/`;

const launchArgs = {
  args: [
    "--no-sandbox",
    "--disable-setuid-sandbox",
    "--allow-external-pages"
  ],
  headless: false
};

let browser;
let context;
let page;

/**
 * Sets up the browser context and optionally waits for a
 * console message that indicates that tests can start.
 * Method can be called repeatedly and will return cached objects.
 *
 * @param {String?} readyConsoleMessage If provided, the function waits for the
 * message to be printed to the console after loading the URL. This will be
 * ignored on subsequent calls.
 * @param {Number?} timeout Time in milliseconds to wait for the console message.
 * Defaults to 60 seconds
 * @return {Promise<{browser: *, context: *, page: *}>}
 */
async function init(readyConsoleMessage, timeout=60000) {
  // reuse the browser instance
  if (!browser) {
    console.log(`# - Launching new browser (${browserType}) ...`);
    browser = await playwright[browserType].launch(launchArgs);
  }
  if (!context) {
    console.log(`# - Creating new context...`);
    context = await browser.newContext();
  }
  if (!page) {
    page = await context.newPage();
    page.on("pageerror", e => {
      console.error(`Error on page ${page.url()}: ${e.message}`);
      throw e;
    });
    // add helpers
    addQxPageHelpers(page);
    // open URL and optionally wait for a console message
    console.log(`# - Opening new page at ${app_url}...`);
    await page.goto(app_url);
    if (readyConsoleMessage) {
      console.log(`# - Waiting for console message "${readyConsoleMessage}"...`);
      await page.waitForConsoleMessage(readyConsoleMessage, {timeout});
      await page.waitForTimeout(500);
    }
  }
  return {
    browser,
    context,
    page
  };
}

/**
 * Logs into the application
 * @param user
 * @param password
 * @return {Promise<void>}
 */
async function login(user, password) {
  console.info(`# - Authenticating user '${user}'...`);
  await page.clickByQxId("toolbar/login");
  await page.waitForWidgetByQxId("windows/login");
  await page.fillByQxId("windows/login/form/username", user);
  await page.fillByQxId("windows/login/form/password", password);
  await page.clickByQxId("windows/login/buttons/login");
  await page.waitForWidgetByQxId("toolbar/user", {timeout:60000, state:"visible" });
  console.info(`# - User '${user}' authenticated. Waiting for tasks to finish ...`);
  await page.waitForFunction("!qx.core.Init.getApplication().getTaskMonitor().getBusy()", {polling: 100});
  await page.waitForTimeout(500);
}

/**
 * Logs out of the application
 * @return {Promise<void>}
 */
async function logout() {
  console.info(`# - Logging out ...`);
  await page.clickByQxId("toolbar/logout");
  await page.waitForWidgetByQxId("toolbar/user", {timeout:60000, state:"hidden" });
  console.info(`# - Logged out. Waiting for tasks to finish ...`);
  await page.waitForFunction("!qx.core.Init.getApplication().getTaskMonitor().getBusy()", {polling: 100});
  await page.waitForTimeout(500);
}

/**
 * Shutdown tests: close page/tab
 * @return {Promise<void>}
 */
async function shutdown() {
  console.log("# - Shutting down ...");
  await page.close();
  page = context = null;
}


module.exports = {
  browserType,
  app_url,
  init,
  login,
  logout,
  shutdown
};
