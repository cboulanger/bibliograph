/**
 * Runs UI tests for Bibliograph
 */

const fs = require("fs");
const path = require("path");
const COMPILER_PATH = path.join(__dirname, "..", "qooxdoo-compiler"); // todo
const testUtils = require(path.join(COMPILER_PATH, "test", "utils.js"));

qx.Class.define("qx.compiler.LibraryApi", {
  extend: qx.tool.cli.api.LibraryApi,
  members: {
    async load () {
      const TEST_PATH = path.join("test", "uitests");
      let command = this.getCompilerApi().getCommand();
      if (command instanceof qx.tool.cli.commands.Test) {
        command.addListener("writtenApplication", async evt => {
          let app = evt.getData();
          if (app.getName() !== "bibliograph") {
            return;
          }
          let files = fs.readdirSync(TEST_PATH)
            .filter(file => fs.statSync(path.join(TEST_PATH, file)).isFile());
          for (let file of files) {
            let test = path.changeExt(path.basename(file), "");
            command.addTest(new qx.tool.cli.api.Test(test, async function () {
              let result = await testUtils.runCommand(TEST_PATH, "node", test + ".js");
              this.setExitCode(result.exitCode);
            })).setNeedsServer(false);
          }
        });
      }
    }
  }
});

module.exports = {
  LibraryApi: qx.compiler.LibraryApi
};
