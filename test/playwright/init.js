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
    browser = await playwright[browserType].launch(launchArgs);
    context = await browser.newContext();
  }
  if (!page) {
    page = await context.newPage();
    page.on("pageerror", e => {
      console.error(`Error on page ${page.url()}: ${e.message}`);
      process.exit(1);
    });
    // add helpers
    addQxPageMethods(page);
    // open URL and optionally wait for a console message
    await page.goto(app_url);
    if (readyConsoleMessage) {
      await page.waitForConsoleMessage(readyConsoleMessage, {timeout});
      console.info("### Bibliograph ready");
    }
  }
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
  
  
  /**
   * Waits for all running tasks to finish
   * @return {Promise<*>}
   */
  page.waitForApplicationIdle = async function() {
    await page.waitForFunction("!qx.core.Init.getApplication().getTaskMonitor().getBusy()", {polling: 100});
  };
}

/**
 * Logs into the application
 * @param page
 * @param user
 * @param password
 * @return {Promise<void>}
 */
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

/**
 * Logs out of the application
 * @param page
 * @return {Promise<void>}
 */
async function logout(page) {
  console.info(`## Logging out ...`);
  await page.clickByQxId("toolbar/logout");
  await page.waitForWidgetByQxId("toolbar/user", {timeout:60000, state:"hidden" });
  console.info(`## Logged out. Waiting for tasks to finish ...`);
  await page.waitForFunction("!qx.core.Init.getApplication().getTaskMonitor().getBusy()", {polling: 100});
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
  login,
  logout,
  qxSelector,
  shutdown
};
