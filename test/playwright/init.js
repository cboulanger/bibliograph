const process = require("process");
const playwright = require("playwright");

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

const browsers = {};

/**
 * Sets up the browser context and optionally waits for a
 * console message that indicates that tests can start.
 * @param {String?} readyConsoleMessage If provided, the function waits for the
 * @param {Number?} timeout Time in milliseconds to wait for the console message. Defaults to 60 seconds
 * @return {Promise<{browser: *, context: *, page: *}>}
 */
async function init(readyConsoleMessage, timeout=60000) {
  // reuse the browser instance
  if (!browsers[browserType]) {
    browsers[browserType] = await playwright[browserType].launch(launchArgs);
  }
  const browser = browsers[browserType];
  const context = await browser.newContext();
  context.clearCookies();
  const page = await context.newPage();
  page.on("pageerror", e => {
    console.error(`Error on page ${page.url()}: ${e.message}`);
    process.exit(1);
  });
  
  // add helpers
  addQxPageMethods(page);
  
  // wait for app to be ready
  //page.logConsole(true);
  await page.goto(app_url);
  await page.waitForConsoleMessage(readyConsoleMessage, {timeout});
  console.info("### Bibliograph ready");
  return {
    browser,
    context,
    page
  };
}

/**
 * Returns the selector string that will select the HTML node with the given qxObjectId
 * @return {String}
 * @param id
 * @param childQxClass
 */
function qxSelector(id, childQxClass) {
  let selector = `[data-qx-object-id="${id}"]`;
  if (childQxClass) {
    selector += ` >> [qxclass="${childQxClass}"]`;
  }
  return selector;
}


/**
 * Adds some helpers and qooxdoo-specific methods
 * @param {Page} page
 */
function addQxPageMethods(page) {
  /**
   * Waits for a console message
   * @param {String|Function} message A string, which is the message to check console messages against,
   * or a function, to which the console message is passed and which must return true or false.
   * @param {Object} options
   * @return {Promise<{String>>}
   */
  page.waitForConsoleMessage = async function(message, options={}) {
    if (!["string", "function"].includes(typeof message)) {
      throw new Error("Invalid message argument, must be string or function");
    }
    return new Promise((resolve, reject) => {
      /**
 * @param consoleMsg
 */
function handler(consoleMsg) {
        let msg =consoleMsg.text();
        switch (typeof message) {
          case "string":
            if (msg === message) {
              page.off("console", handler);
              resolve(msg);
            }
            break;
          case "function":
            if (message(msg)) {
              page.off("console", handler);
              resolve(msg);
            }
            break;
        }
      }
      page.on("console", handler);
      if (options.timeout) {
        let error = new Error(`Timeout of ${options.timeout} reached when waiting for console message '${message}.'`);
        setTimeout(() => reject(error), options.timeout);
      }
    });
  };
  
  page.logConsole = (() => {
    let handler = consoleMsg => console.log(consoleMsg.text());
    return val => val ? page.on("console", handler) : page.off("console", handler);
  })();
  
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
   * @param options
   * @return {Promise<*>}
   */
  page.waitForWidgetByQxId = async function(qxId, options={}) {
    let selector = qxSelector(qxId);
    return page.waitForSelector(selector, options);
  };
  
  /**
   * @param {String} qxId
   * @param {String} text
   * @param {Object} options Options to pass to waitForSelector
   * @return {Promise<*>}
   */
  page.waitForTextByQxId = async function(qxId, text, options={}) {
    text = text.replace(/"/g, "&apos;").replace(/"/g, "&quot;");
    let selector = qxSelector(qxId) + ` >> text="${text}"`;
    return page.waitForSelector(selector, options);
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
