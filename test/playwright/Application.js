const playwright = require("playwright");
const process = require("process");

/**
 * This class models a qooxdoo application running in a browser
 * @property {String} url Url of the Application
 * @property {Browser} browser Instance of Playwright Browser class
 * @property {Context} context Instance of Playwright Context class
 * @property {Page} page Instance of Playwright Page class
 * @property {Boolean} this.verbose Whether to log a messages on each action as a tape-conformant message
 * @property {Boolean} logConsole Whether to mirror the browser console messages
 * @property {String} browserType The type of browser. Defaults to "chromium"
 * @property {Object} browserOptions Options for launching the browser, see...
 */
class Application {
  constructor (props) {
    // defaults to be overridden
    this.browserType = "chromium";
    this.browserOptions = {
      args: [
        "--no-sandbox",
        "--disable-setuid-sandbox",
        "--allow-external-pages"
      ],
      headless: true
    };
    if (props && typeof props == "object") {
      this.set(props);
    }
    if (!this.url) {
      throw new Error("Missing application URL");
    }
    if (!this.browserType) {
      throw new Error("Missing browser type");
    }
  }
  
  /**
   * Returns the selector string that will select the HTML node with the given qxObjectId
   * @return {String}
   * @param {String} id
   */
  // eslint-disable-next-line class-methods-use-this
  getSelector(id) {
    return `[data-qx-object-id="${id}"]`;
  }
  
  /**
   * Given a map, sets the keys and values as properties of this instance.
   * Returns the instance.
   * @param {Object} props
   * @return {Application}
   */
  set(props) {
    for (let [key, value] of Object.entries(props)) {
      this[key] = value;
    }
    return this;
  }
  
  /**
   * Wether the application is to be run headless (i.e. without a browser window),
   * or not
   * @param val
   */
  set headless(val) {
    this.browserOptions.headless = val;
  }
  
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
   * @return {Promise}
   */
  async init(readyConsoleMessage, timeout=60000) {
    if (!this.browser) {
      this.verbose && console.log(`# - Launching new browser (${this.browserType}) ...`);
      try {
        this.browser = await playwright[this.browserType].launch(this.browserOptions);
      } catch (e) {
        console.error(`Error when trying to launch browser: ${e.message}`);
        process.exit(1);
      }
    }
    if (!this.context) {
      this.verbose && console.log(`# - Creating new context...`);
      this.context = await this.browser.newContext();
    }
    if (!this.page) {
      this.page = await this.context.newPage();
      this.page.on("pageerror", e => {
        console.error(`Error on page ${this.page.url()}: ${e.message}`);
        process.exit(1);
      });
      this.page.on("response", response => {
        if (response.status() >= 400) {
          this.error(response.statusText());
        }
      });
      this.page.on("console", consoleMsg => {
        let text = consoleMsg.text();
        if (text.match(/(AuthenticationError|JsonRpcError)/)) {
          this.error(text);
        }
      });
      // open URL and optionally wait for a console message
      this.verbose && console.log(`# - Opening new page at ${this.url}...`);
      try {
        await this.page.goto(this.url);
      } catch (e) {
        console.error(`Error when trying to open page: ${e.message}`);
      }
      if (readyConsoleMessage) {
        this.verbose && console.log(`# - Waiting for console message "${readyConsoleMessage}"...`);
        await this.waitForConsoleMessage(readyConsoleMessage, {timeout});
        await this.page.waitForTimeout(500);
      }
    }
  }
  
  /**
   * Turn logging of browser console messages on or off
   * @param val
   */
  set logConsole (val) {
    if (!this.___logConsoleMessages) {
      this.___logConsoleMessages = consoleMsg => console.log("# Browser Console: " + consoleMsg.text());
    }
    val ? this.page.on("console", this.___logConsoleMessages) : this.page.off("console", this.___logConsoleMessages);
  }
  
  /**
   * If verbose mode is turned on, log a tap-conformant message
   * to the console, i.e output the message as a TAP comment
   *
   * @param {String} message
   */
  log(message) {
    this.verbose && console.log(`# - ${message}`);
  }
  
  /**
   *Log a tap-conformant message to the console,
   *i.e output the message as a TAP comment
   *
   * @param {String} message
   */
  // eslint-disable-next-line class-methods-use-this
  info(message) {
    console.info(`# - ${message}`);
  }
  
  /**
   * Log a tap-conformant error message to the console and exits the process
   * @param {String} message
   */
  // eslint-disable-next-line class-methods-use-this
  error(message) {
    console.error(`not ok - ${message}`);
    process.exit(1);
  }
  
  /**
   * Click on a node identified by its qx id.
   * @param {String} qxId
   * @param {Object?} options Options object
   * @return {Promise<*>}
   */
  async click (qxId, options = {}) {
    this.log(`Click on node with qx object id '${qxId}'`);
    let selector = this.getSelector(qxId);
    await this.page.click(selector, options);
  }
  
  
  /**
   * Fire an event on an object identified by its qx id
   * @param {String} qxId
   * @param {String} eventType the event name
   * @return {Promise<void>}
   */
  async fireEvent (qxId, eventType) {
    this.log(`Fire '${eventType}' event on qx object id '${qxId}'.`);
    await this.waitForWidget(qxId);
    await this.page.evaluate(([qxId, eventType]) => {
      qx.core.Id.getQxObject(qxId).fireEvent(eventType);
    }, [qxId, eventType]);
  }
  
  /**
   * Fire a data event on an object identified by its qx id
   * @param {String} qxId
   * @param {String} eventType the event type
   * @param {*} data JSON-serializable data
   * @return {Promise<void>}
   */
  async fireDataEvent (qxId, eventType, data) {
    this.log(`Fire '${eventType}' data event on qx object id '${qxId}' with data ${JSON.stringify(data)}.`);
    await this.waitForWidget(qxId);
    await this.page.evaluate(([qxId, eventType, data]) => {
      qx.core.Id.getQxObject(qxId).fireDataEvent(eventType, data);
    }, [qxId, eventType, data]);
  }
  
  /**
   * @param {String} qxId
   * @param {String} text
   * @return {Promise<*>}
   */
  async fill (qxId, text) {
    this.log(`Type '${text}' into node with qx object id '${qxId}'`);
    let selector = this.getSelector(qxId);
    await this.page.fill(selector, text);
  }
  
  /**
   * Populates a qooxdoo form identified by its qx object id. The form elements
   * must have individual ids and must be owned by the form.
   * @param {String} qxId
   * @param {Map} data of key-value pairs - the key is the id of the form element,
   * the value is to be entered into the form.
   * @param {Number} timeout Timeout in microseconds that should be waited between
   * filling the form fields. Default is 0ms.
   * @param {Function?} fn Optional *async* function that is run after filling out a form field;
   * the function is called with the qx id of the form field.
   * @return {Promise<*>}
   */
  async populate (qxId, data, timeout = 0, fn) {
    this.log(`Populate form '${qxId}':`);
    for (let [key, value] of Object.entries(data)) {
      let fieldQxId = qxId + "/" + key;
      await this.fill(fieldQxId, value);
      if (typeof fn == "function") {
        await fn(fieldQxId);
      }
    }
  }
  
  /**
   * Shorthand for page.waitForTimeout()
   * @param {Number} timeout Timeout in milliseconds
   * @return {Promise<*>}
   */
  async wait (timeout) {
    this.log(`Wait for '${timeout} milliseconds'`);
    await this.page.waitForTimeout(timeout);
  }
  
  /**
   * Waits for a dom node with the given qx object id to appear in the DOM
   * @param {String} qxId
   * @param options
   * @return {Promise<*>}
   */
  async waitForWidget (qxId, options = {}) {
    this.log(`Wait for node with qx object id '${qxId}'`);
    let selector = this.getSelector(qxId);
    await this.page.waitForSelector(selector, options);
  }
  
  /**
   * Wait for a specific text to appear in a child text node of the node identified
   * by the qx object id.
   * @param {String} qxId
   * @param {String} text
   * @param {Object} options Options to pass to waitForSelector
   * @return {Promise<*>}
   */
  async waitForText (qxId, text, options = {}) {
    this.log(`Wait for '${text}' to appear in node with qx object id '${qxId}'`);
    text = text.replace(/"/g, "&apos;").replace(/"/g, "&quot;");
    let selector = this.getSelector(qxId) + ` >> text="${text}"`;
    await this.page.waitForSelector(selector, options);
  }
  
  /**
   * Waits for a console message
   * @param {String|Function} message A string, which is the message to check console messages against,
   * or a function, to which the console message is passed and which must return true or false.
   * @param {Object} options
   * @return {Promise<String>}
   */
  async waitForConsoleMessage (message, options = {}) {
    if (!["string", "function"].includes(typeof message)) {
      throw new Error("Invalid message argument, must be string or function");
    }
    return await new Promise((resolve, reject) => {
      let handler = consoleMsg => {
        let msg = consoleMsg.text();
        switch (typeof message) {
          case "string":
            if (msg === message) {
              this.page.off("console", handler);
              resolve(msg);
            }
            break;
          case "function":
            if (message(msg)) {
              this.page.off("console", handler);
              resolve(msg);
            }
            break;
        }
      };
      this.page.on("console", handler);
      if (options.timeout) {
        let error = new Error(`Timeout of ${options.timeout} reached when waiting for console message '${message}.'`);
        setTimeout(() => reject(error), options.timeout);
      }
    });
  }
}

module.exports = Application;
