const Application = require("../playwright/Application");

class Bibliograph extends Application {
  /**
   * Logs into the application
   * @param user
   * @param password
   * @return {Promise<void>}
   */
  async login(user, password) {
    this.verbose && console.info(`# - Authenticating user '${user}'...`);
    await this.click("toolbar/login");
    await this.waitFor("windows/login");
    await this.fill("windows/login/form/username", user);
    await this.fill("windows/login/form/password", password);
    await this.click("windows/login/buttons/login");
    await this.waitFor("toolbar/user", {timeout:60000, state:"visible" });
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
    await this.waitFor("toolbar/user", {timeout:60000, state:"hidden" });
    this.verbose && console.info(`# - Logged out. Waiting for tasks to finish ...`);
    await this.waitForApplicationIdle();
    await this.page.waitForTimeout(500);
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
  }
}

module.exports = Bibliograph;
