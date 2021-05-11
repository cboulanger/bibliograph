const test = require("tape");
const app = require("../app");


test("Create & delete reference", async assert => {
  await app.init();
  await app.login("admin", "test1234");
  await app.click(`windows/datasources/list/baer`);
  await app.waitForIdle();
  app.log("Select folder");
  await app.page.click(`[data-qx-object-id="folder-tree-panel"] > div:nth-child(2) > div > div > div > div:nth-child(2) > div > div > div:nth-child(2) > div`);
  await app.waitForIdle();
  app.log("Add a reference");
  //await app.fireEvent("table-view/footer/add", "execute", true);
  await app.fireEvent("table-view/footer/add", "execute");
  await app.page.click(`[data-qx-object-id="table-view/new-reference-window/list"] > div > div > div:nth-child(2) > div:nth-child(6) > div`);
  await app.click("table-view/new-reference-window/ok");
  await app.waitForIdle();
  // fill in details
  await app.populate("item-view/editor/forms/inbook", {
    author: "Boulanger, Christian",
    title: "Chapter Title",
    booktitle: "Book Title",
    year: "2020"
  }, 500, () => app.waitForIdle());
  //await app.waitForText()["item-view/editor/forms/inbook/citekey", "Boulanger-2020-Chapter"]
  assert.ok(true);
  
  app.log("Select folder");
  await app.page.click(`[data-qx-object-id="folder-tree-panel"] > div:nth-child(2) > div > div > div > div:nth-child(2) > div > div > div:nth-child(2) > div`);
  await app.waitForIdle();
  app.log("Select reference");
  await app.page.click(`[data-qx-object-id="table-view/tables"] > .qx-table > :nth-child(1) > :nth-child(1) > :nth-child(2) > :nth-child(1) > :nth-child(1) > :nth-child(1) > :nth-child(1)`);
  await app.waitForIdle();
  
  app.log("Press remove button");
  await app.fireEvent("table-view/footer/remove", "execute");
  await app.wait(500);
  app.log("Press 'yes' button...");
  //doesn't work: await app.click("application/confirm/buttons/yes");
  await app.fireEvent("application/confirm/buttons/yes", "execute");
  await app.waitForIdle();
  assert.ok(true);
  await app.logout();
});

