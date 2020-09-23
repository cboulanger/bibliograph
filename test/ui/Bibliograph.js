const Application = require("../playwright/Application");

class Bibliograph extends Application {
  /**
   * Overridden to pass a default "ready" console message
   * @override
   * @return {Promise<void>}
   */
  async init(readyConsoleMessage="bibliograph.setup.completed") {
    await super.init(readyConsoleMessage);
  }
  
  /**
   * Logs into the application
   * @param user
   * @param password
   * @return {Promise<void>}
   */
  async login(user, password) {
    this.verbose && console.info(`# - Authenticating user '${user}'...`);
    await this.click("toolbar/login");
    await this.waitForWidget("windows/login");
    await this.fill("windows/login/form/username", user);
    await this.fill("windows/login/form/password", password);
    await this.click("windows/login/buttons/login");
    await this.waitForWidget("toolbar/user", {timeout:60000, state:"visible" });
    this.verbose && console.info(`# - User '${user}' authenticated. Waiting for tasks to finish ...`);
    await this.waitForIdle();
    await this.page.waitForTimeout(500);
  }
  
  /**
   * Logs out of the application
   * @return {Promise<void>}
   */
  async logout() {
    this.verbose && console.info(`# - Logging out ...`);
    await this.click("toolbar/logout");
    await this.waitForWidget("toolbar/user", {timeout:60000, state:"hidden" });
    this.verbose && console.info(`# - Logged out. Waiting for tasks to finish ...`);
    await this.waitForIdle();
    await this.page.waitForTimeout(500);
  }
  
  /**
   * Waits for all running tasks to finish
   * @return {Promise<*>}
   */
  async waitForIdle () {
    await this.page.waitForFunction("!qx.core.Init.getApplication().getTaskMonitor().getBusy()", {polling: 100});
    await this.page.waitForTimeout(100);
  }
  
  /**
   * Shutdown tests: close page/tab
   * @return {Promise<void>}
   */
  async shutdown() {
    this.verbose && console.info("# - Shutting down ...");
    await this.page.close();
    this.page = null;
    this.context = null;
    this.browser.close();
    this.browser = null;
  }
}

module.exports = Bibliograph;
