const process = require("process");
const playwright = require("playwright");

// configuration
const browserType = process.env.BROWSER_TYPE || "chromium";
const app_url = process.env.APP_URL || `http://localhost:8073/compiled/source/bibliograph/`;

const launchArgs = {
  args: [
    "--no-sandbox",
    "--disable-setuid-sandbox"
  ],
  headless: false
};

const browsers = {};

/**
 * Returns the selector string that will select the HTML node
 * with the given qxObjectId
 * @return {String}
 */
function qxSelector(id, childQxClass) {
  let selector = `div[data-qx-object-id="${id}"]`;
  if (childQxClass) {
    selector += ` >> div[qxclass="${childQxClass}"]`;
  }
  return selector;
}

/**
 * Sets up the browser context
 * @return {Promise<{browser: *, context: *, page: *}>}
 */
async function init() {
  // reuse the browser instance
  if (!browsers[browserType]) {
    browsers[browserType] = await playwright[browserType].launch(launchArgs);
  }
  const browser = browsers[browserType];
  const context = await browser.newContext();
  const page = await context.newPage();
  page.on("pageerror", e => {
    console.error(`Error on page ${page.url()}: ${e.message}`);
    process.exit(1);
  });
  await page.goto(app_url);
  // add some qooxdoo-specific methods
  
  /**
   * @param {String} qxId
   * @return {Promise<*>}
   */
  page.clickByQxId = async function(qxId) {
    let selector = qxSelector(qxId);
    return page.click(selector);
  };
  
  /**
   * @param {String} qxId
   * @param {String} text
   * @return {Promise<*>}
   */
  page.fillByQxId = async function(qxId, text) {
    let selector = qxSelector(qxId);
    return page.fill(selector, text);
  };
  
  /**
   * @param {String} qxId
   * @return {Promise<*>}
   */
  page.waitForWidgetByQxId = async function(qxId) {
    let selector = qxSelector(qxId);
    return page.waitForSelector(selector);
  };
  
  /**
   * @param {String} qxId
   * @param {String} text
   * @return {Promise<*>}
   */
  page.waitForTextByQxId = async function(qxId, text) {
    text = text.replace(/"/g, "&apos;").replace(/"/g, "&quot;");
    let selector = qxSelector(qxId) + ` >> text="${text}"`;
    return page.fill(selector, text);
  };
  
  await page.waitForEvent("bibliograph-start", {timeout: 60000});
  return {
    browser,
    context,
    page
  };
}



/**
 * Logout and close browser
 * @param page
 * @param browser
 * @return {Promise<void>}
 */
async function shutdown(page, browser) {
  await page.click(qxSelector("toolbar/logout"));
  await page.waitForSelector(qxSelector("toolbar/login"));
  await browser.close();
}

module.exports = {
  browserType,
  app_url,
  init,
  qxSelector,
  shutdown
};
