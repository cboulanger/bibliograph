

/**
 * Returns the selector string that will select the HTML node with the given qxObjectId
 * @return {String}
 * @param {String} id
 */
function getSelector(id) {
  return `[data-qx-object-id="${id}"]`;
}

/**
 * create API
 * @param {Page} page
 * @param {Boolean} verbose Whether to be more verbose
 */
function api(page, verbose) {
  /**
   * Waits for a console message
   * @param {String|Function} message A string, which is the message to check console messages against,
   * or a function, to which the console message is passed and which must return true or false.
   * @param {Object} options
   * @return {Promise<String>}
   */
  async function waitForConsoleMessage (message, options = {}) {
    if (!["string", "function"].includes(typeof message)) {
      throw new Error("Invalid message argument, must be string or function");
    }
    return new Promise((resolve, reject) => {
      /**
       * @param consoleMsg
       */
      function handler (consoleMsg) {
        let msg = consoleMsg.text();
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
  }
  
  /**
   * Turn logging of browser console messages on or off
   * @param val
   */
  function logConsoleMessages (val) {
    if (!this.___logConsoleMessages) {
      this.___logConsoleMessages = consoleMsg => console.log(consoleMsg.text());
    }
    val ? page.on("console", this.___logConsoleMessages) : page.off("console", this.___logConsoleMessages);
  }
  
  /**
   * Click on a node identified by its qx id.
   * @param {String} qxId
   * @param {Object?} options Options object
   * @return {Promise<*>}
   */
  async function click (qxId, options = {}) {
    verbose && console.log(`# - Clicking on node with qx object id '${qxId}'`);
    let selector = getSelector(qxId);
    await page.click(selector, options);
  }
  
  /**
   * @param {String} qxId
   * @param {String} text
   * @return {Promise<*>}
   */
  async function fill (qxId, text) {
    verbose && console.log(`# - Typing '${text}' into node with qx object id '${qxId}'`);
    let selector = getSelector(qxId);
    await page.fill(selector, text);
  }
  
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
  async function populate (qxId, data, timeout = 0, fn) {
    verbose && console.log(`# - Populating form '${qxId}':`);
    for (let [key, value] of Object.entries(data)) {
      console.log(`#   ${key}: "${value}"`);
      let selector = getSelector(qxId + "/" + key);
      await page.fill(selector, value);
      if (typeof fn == "function") {
        await fn(selector);
      }
    }
  }
  
  /**
   * @param {String} qxId
   * @param options
   * @return {Promise<*>}
   */
  async function waitFor (qxId, options = {}) {
    verbose && console.log(`# - Waiting for node with qx object id '${qxId}'`);
    let selector = getSelector(qxId);
    return page.waitForSelector(selector, options);
  }
  
  /**
   * Wait for a specific text to appear in a child text node of the node identified
   * by the qx object id.
   * @param {String} qxId
   * @param {String} text
   * @param {Object} options Options to pass to waitForSelector
   * @return {Promise<*>}
   */
  async function waitForText (qxId, text, options = {}) {
    verbose && console.log(`# - Waiting for '${text}' to appear in node with qx object id '${qxId}'`);
    text = text.replace(/"/g, "&apos;").replace(/"/g, "&quot;");
    let selector = getSelector(qxId) + ` >> text="${text}"`;
    return page.waitForSelector(selector, options);
  }
  
  /**
   * Waits for all running tasks to finish
   * @return {Promise<*>}
   */
  async function waitForApplicationIdle () {
    await page.waitForFunction("!qx.core.Init.getApplication().getTaskMonitor().getBusy()", {polling: 100});
    await page.waitForTimeout(100);
  }
  
  return {
    click,
    fill,
    populate,
    waitFor,
    logConsoleMessages,
    waitForApplicationIdle,
    waitForConsoleMessage,
    waitForText
  };
}

module.exports = {
  getSelector,
  api
};
