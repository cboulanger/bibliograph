
/**
 * @param {Object} config A map containing all needed objects as properties
 */
function api(config) {
  
  let {page, qxPage, context, verbose} = config;
  
  /**
   * Logs into the application
   * @param user
   * @param password
   * @return {Promise<void>}
   */
  async function login(user, password) {
    verbose && console.info(`# - Authenticating user '${user}'...`);
    await qxPage.click("toolbar/login");
    await qxPage.waitFor("windows/login");
    await qxPage.fill("windows/login/form/username", user);
    await qxPage.fill("windows/login/form/password", password);
    await qxPage.click("windows/login/buttons/login");
    await qxPage.waitFor("toolbar/user", {timeout:60000, state:"visible" });
    verbose && console.info(`# - User '${user}' authenticated. Waiting for tasks to finish ...`);
    await qxPage.waitForApplicationIdle();
    await page.waitForTimeout(500);
  }
  
  /**
   * Logs out of the application
   * @return {Promise<void>}
   */
  async function logout() {
    verbose && console.info(`# - Logging out ...`);
    await qxPage.click("toolbar/logout");
    await qxPage.waitFor("toolbar/user", {timeout:60000, state:"hidden" });
    verbose && console.info(`# - Logged out. Waiting for tasks to finish ...`);
    await qxPage.waitForApplicationIdle();
    await page.waitForTimeout(500);
  }
  
  /**
   * Shutdown tests: close page/tab
   * @return {Promise<void>}
   */
  async function shutdown() {
    verbose && console.info("# - Shutting down ...");
    await page.close();
    page = null;
    context = null;
  }
  
  return {
    login,
    logout,
    shutdown
  };
}

module.exports = api;
