const test = require("tape");
const app = require("../app");


test("Import references", async assert => {
  await app.init();
  await app.login("admin", "test1234");
  await app.click(`windows/datasources/list/baer`);
  await app.waitForIdle();
  app.log("Select folder");
  await app.page.click(`[data-qx-object-id="folder-tree-panel"] > div:nth-child(2) > div > div > div > div:nth-child(2) > div > div > div > div`);
  await app.waitForIdle();
  app.log("Search for Hamlet...");
  await app.click(`toolbar/import`);
  await app.waitForWidget("windows/plugin-z3950-import");
  await app.click(`windows/plugin-z3950-import/view/selectbox`);
  
  assert.ok(true);
  await app.logout();
});

