const test = require("tape");
const {init, shutdown, login} = require("./init");

test("login as user2", async assert => {
  const {page} = await init("bibliograph.setup.completed");
  await login(page, "user2", "user");
  assert.ok(true);
});

test("create reference", async assert => {
  const {page} = await init();
  // select folder
  await page.click(`[data-qx-object-id="folder-tree-panel"] > :nth-child(2) > [qxdroppable="on"] > :nth-child(1) > :nth-child(1) > :nth-child(2) > :nth-child(1) > :nth-child(1) > :nth-child(1) > .qooxdoo-table-cell`);
  await page.waitForApplicationIdle();
  // add reference
  await page.click(`[data-qx-object-id="table-view/footer/add"]`);
  await page.click(`[data-qx-object-id="table-view/new-reference-window/list"] > :nth-child(1) > :nth-child(1) > :nth-child(2) > :nth-child(6)`);
  await page.click(`[data-qx-object-id="table-view/new-reference-window/ok"]`);
  // fill in details
  await page.click(`[data-qx-object-id="item-view/editor/forms/inbook/author"]`);
  await page.fill(`[data-qx-object-id="item-view/editor/forms/inbook/author"]`, "Boulanger, Christian");
  await page.waitForApplicationIdle();
  await page.fill(`[data-qx-object-id="item-view/editor/forms/inbook/title"]`, "Chapter Title");
  await page.waitForApplicationIdle();
  await page.fill(`[data-qx-object-id="item-view/editor/forms/inbook/booktitle"]`, "Book Title");
  await page.waitForApplicationIdle();
  await page.fill(`[data-qx-object-id="item-view/editor/forms/inbook/year"]`, "2020");
  await page.waitForApplicationIdle();
  await page.fill(`[data-qx-object-id="item-view/editor/forms/inbook/citekey"]`, "Boulanger-2020-Chapter");
  await page.waitForApplicationIdle();
  assert.ok(true);
});

test("delete reference", async assert => {
  const {page} = await init();
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
