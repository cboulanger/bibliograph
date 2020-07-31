const test = require("tape");
const {init, shutdown, login} = require("./init");

test("start application and login as user2", async assert => {
  const {page} = await init("bibliograph.setup.completed");
  await login(page, "user2", "user");
  assert.ok(true);
});

test("create and delete reference", async assert => {
  const {page} = await init();
  console.log("# creating reference");
  // select folder
  await page.click(`[data-qx-object-id="folder-tree-panel"] > :nth-child(2) > .qx-table > :nth-child(1) > :nth-child(1) > :nth-child(2) > :nth-child(1) > :nth-child(1) > :nth-child(1) > .qooxdoo-table-cell`);
  await page.waitForApplicationIdle();
  // add reference
  await page.click(`[data-qx-object-id="table-view/footer/add"]`);
  await page.evaluate(() => qx.core.Id.getQxObject("table-view/footer/add").fireEvent("execute"));
  await page.click(`[data-qx-object-id="table-view/new-reference-window/list"] > :nth-child(1) > :nth-child(1) > :nth-child(2) > :nth-child(6)`);
  await page.click(`[data-qx-object-id="table-view/new-reference-window/ok"]`);
  await page.waitForApplicationIdle();
  // fill in details
  let form = [
    [`[data-qx-object-id="item-view/editor/forms/inbook/author"]`, "Boulanger, Christian"],
    [`[data-qx-object-id="item-view/editor/forms/inbook/title"]`, "Chapter Title"],
    [`[data-qx-object-id="item-view/editor/forms/inbook/booktitle"]`, "Book Title"],
    [`[data-qx-object-id="item-view/editor/forms/inbook/year"]`, "2020"],
    [`[data-qx-object-id="item-view/editor/forms/inbook/citekey"]`, "Boulanger-2020-Chapter"]
  ];
  for (let [id, text] of form) {
    await page.fill(id, text);
    await page.waitForTimeout(50);
    await page.waitForApplicationIdle();
  }
  assert.ok(true);
  console.log("# deleting reference");
  // select reference
  await page.click(`[data-qx-object-id="table-view/tables"] > .qx-table > :nth-child(1) > :nth-child(1) > :nth-child(2) > :nth-child(1) > :nth-child(1) > :nth-child(1) > :nth-child(1)`);
  // remove reference
  await page.click(`[data-qx-object-id="table-view/footer/remove"]`);
  await page.click(`[data-qx-object-id="application/confirm/buttons/yes"]`);
  await page.waitForApplicationIdle();

  assert.ok(true);
});


test("Shutdown", async () => {
  const {page, browser} = await init();
  shutdown(page, browser);
});
