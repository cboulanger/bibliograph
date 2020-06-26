const process = require("process");
const playwright = require("playwright");

// configuration
const browserType = process.env.BROWSER_TYPE || "chromium";
const launchArgs = {
  args: [
    "--no-sandbox",
    "--disable-setuid-sandbox"
  ]
};
const app_url = `http://localhost:8080/`;

/**
 * Sets up the browser context
 * @return {Promise<{browser: *, context: *, page: *}>}
 */
async function init() {
  const browser = await playwright[browserType].launch(launchArgs);
  const context = await browser.newContext();
  const page = await context.newPage();
  page.on("pageerror", e => {
    console.error(`Error on page ${page.url()}: ${e.message}`);
    process.exit(1);
  });
  await page.goto(app_url);
  return {
    browser,
    context,
    page
  };
}

/**
 * Returns the selector string that will select the HTML node
 * with the given qxObjectId
 * @return {String}
 */
function qxSelector(id) {
  return `div[data-qx-object-id=${id}]`;
}

module.exports = {
  browserType,
  app_url,
  init,
  qxSelector
};
