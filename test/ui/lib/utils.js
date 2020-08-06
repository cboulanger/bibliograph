
/**
 * @param {Browser} browser
 * @param {Page} page
 * @param {Context} context
 */
function api(browser, page, context) {
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
    console.info("# - Shutting down ...");
    await page.close();
    page = context = null;
  }
  
  return {
    login,
    logout,
    shutdown
  };
}

module.exports = api;
