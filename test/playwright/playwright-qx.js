

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
 * Monkey-patches the playwright page object to add some
 * helpers and qooxdoo-specific methods
 *
 * @param {Page} page
 */
function addQxPageHelpers(page) {
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
   * Click on a node identified by its qx id.
   * @param {String} qxId
   * @param {Object?} options Options object
   * @return {Promise<*>}
   */
  page.clickByQxId = async function(qxId, options={}) {
    console.log(`# - Clicking on node with qx object id '${qxId}'`);
    let selector = qxSelector(qxId);
    await page.click(selector, options);
  };
  
  /**
   * @param {String} qxId
   * @param {String} text
   * @return {Promise<*>}
   */
  page.fillByQxId = async function(qxId, text) {
    console.log(`# - Typing '${text}' into node with qx object id '${qxId}'`);
    let selector = qxSelector(qxId);
    await page.fill(selector, text);
  };
  
  /**
   * Populates a qooxdoo form identified by its qx object id. The form elements
   * must have individual ids and must be owned by the form.
   * @param {String} qxId
   * @param {Map} data of key-value pairs - the key is the id of the form element,
   * the value is to be entered into the form.
   * @param {Number} timeout Timeout in microseconds that should be waited between
   * filling the form fields. Default is 0ms.
   * @param {Function?} fn Optional async function that is run after filling out a form field;
   * the function is called with the CSS selector of the form field.
   * @return {Promise<*>}
   */
  page.populateQxForm = async function(qxId, data, timeout = 0, fn) {
    console.log(`# - Populating form '${qxId}':`);
    for (let [key, value] of Object.entries(data)) {
      console.log(`#   ${key}: "${value}"`);
      let selector = qxSelector(qxId + "/" + key);
      await page.fill(selector, value);
      if (typeof fn == "function") {
        await fn(selector);
      }
    }
  };
  
  /**
   * @param {String} qxId
   * @param options
   * @return {Promise<*>}
   */
  page.waitForWidgetByQxId = async function(qxId, options={}) {
    console.log(`# - Waiting for node with qx object id '${qxId}'`);
    let selector = qxSelector(qxId);
    return page.waitForSelector(selector, options);
  };
  
  /**
   * Wait for a specific text to appear in a child text node of the node identified
   * by the qx object id.
   * @param {String} qxId
   * @param {String} text
   * @param {Object} options Options to pass to waitForSelector
   * @return {Promise<*>}
   */
  page.waitForTextByQxId = async function(qxId, text, options={}) {
    console.log(`# - Waiting for '${text}' to appear in node with qx object id '${qxId}'`);
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
    await page.waitForTimeout(100);
  };
}

module.exports = { addQxPageHelpers };
