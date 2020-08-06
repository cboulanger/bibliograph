const process = require("process");
const playwright = require("playwright");
const {api} = require("../playwright/api");

// configuration
const browserType = process.env.BROWSER_TYPE || "chromium";
const app_url = process.env.APP_URL;

if (!app_url) {
  throw new Error("Missing APP_URL environment variable");
}

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
let qxPage;

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
 * @param {Boolean} verbose Verbose output
 * @return {Promise<{browser: *, context: *, page: *}>}
 */
async function init(readyConsoleMessage, timeout=60000, verbose = true) {
  // reuse the browser instance
  if (!browser) {
    verbose && console.log(`# - Launching new browser (${browserType}) ...`);
    browser = await playwright[browserType].launch(launchArgs);
  }
  if (!context) {
    verbose && console.log(`# - Creating new context...`);
    context = await browser.newContext();
  }
  if (!page) {
    page = await context.newPage();
    page.on("pageerror", e => {
      console.error(`Error on page ${page.url()}: ${e.message}`);
      throw e;
    });
    page.on("response", response => {
      if (!response.ok()) {
        let e = new Error(response.statusText());
        try {
          console.error(response.json());
        } catch (e) {
          console.error(response.body());
        }
        throw e;
      }
    });
    // add helpers
    qxPage = api(page, verbose);
    // open URL and optionally wait for a console message
    verbose && console.log(`# - Opening new page at ${app_url}...`);
    await page.goto(app_url);
    if (readyConsoleMessage) {
      verbose && console.log(`# - Waiting for console message "${readyConsoleMessage}"...`);
      await page.waitForConsoleMessage(readyConsoleMessage, {timeout});
      await page.waitForTimeout(500);
    }
  }
  return {
    browser,
    context,
    page,
    qxPage
  };
}

module.exports = {
  browserType,
  app_url,
  init
};
