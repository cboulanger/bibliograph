const test = require("tape");
const {init, shutdown, login, logout} = require("./init");

test("References", async assert => {
  const {page} = await init("bibliograph.setup.completed");
  await login("user2", "user");
  console.log("# - Creating reference");
  // select folder
  await page.click(`[data-qx-object-id="folder-tree-panel"] > :nth-child(2) > .qx-table > :nth-child(1) > :nth-child(1) > :nth-child(2) > :nth-child(1) > :nth-child(1) > :nth-child(1) > .qooxdoo-table-cell`);
  await page.waitForApplicationIdle();
  // add reference
  await page.evaluate(() => qx.core.Id.getQxObject("table-view/footer/add").fireNonBubblingEvent("execute"));
  await page.click(`[data-qx-object-id="table-view/new-reference-window/list"] > :nth-child(1) > :nth-child(1) > :nth-child(2) > :nth-child(6)`);
  await page.clickByQxId("table-view/new-reference-window/ok");
  await page.waitForApplicationIdle();
  // fill in details
  await page.populateQxForm("item-view/editor/forms/inbook", {
    author: "Boulanger, Christian",
    title: "Chapter Title",
    booktitle: "Book Title",
    year: "2020"
  }, 500, page.waitForApplicationIdle);
  
  //  check for ["item-view/editor/forms/inbook/citekey", "Boulanger-2020-Chapter"]
  assert.ok(true);
  console.log("# - Deleting reference");
  // select reference
  await page.click(`[data-qx-object-id="folder-tree-panel"] > :nth-child(2) > .qx-table > :nth-child(1) > :nth-child(1) > :nth-child(2) > :nth-child(1) > :nth-child(1) > :nth-child(1) > .qooxdoo-table-cell`);
  await page.waitForApplicationIdle();
  await page.click(`[data-qx-object-id="table-view/tables"] > .qx-table > :nth-child(1) > :nth-child(1) > :nth-child(2) > :nth-child(1) > :nth-child(1) > :nth-child(1) > :nth-child(1)`);
  await page.waitForApplicationIdle();
  
  // remove reference
  await page.evaluate(() => qx.core.Id.getQxObject("table-view/footer/remove").fireNonBubblingEvent("execute"));
  await page.waitForTimeout(500);
  console.log("# Pressing 'yes' button...");
  await page.evaluate(() => qx.core.Id.getQxObject("application/confirm/buttons/yes").fireNonBubblingEvent("execute"));
  
  //await page.clickByQxId("application/confirm/buttons/yes");
  await page.waitForApplicationIdle();
  assert.ok(true);
  await logout();
  await shutdown();
});

