/**
 * Runs UI tests for Bibliograph
 */
const fs = require("fs");
const path = require("path");
const TEST_PATH = path.join("test", "uitests");

qx.Class.define("bibliograph.CompilerApi", {
  extend: qx.tool.cli.api.CompilerApi,
  statics: {
    TEST_PATH
  },
  members: {
  
    beforeTests(command) {
      command.addTest(new qx.tool.cli.api.Test("fake test", async function() {
        console.log("# This is a fake test");
        this.setExitCode(0);
      })).setNeedsServer(false);
    },

    afterDeploy(data) {
      //
    },
  
    /**
     * Run the javascript-based tests
     * @param command
     * @return {Promise<void>}
     */
    async __runTests(command) {
      let files =
            fs.readdirSync(this.constructor.TEST_PATH)
            .filter(file => fs.statSync(path.join(this.constructor.TEST_PATH, file)).isFile());
      for (let file of files) {
        let test = path.changeExt(path.basename(file), "");
        command.addTest(new qx.tool.cli.api.Test(test, async () => {
          let result = await qx.tool.utis.Utils.runCommand(this.constructor.TEST_PATH, "node", test + ".js");
          this.setExitCode(result.exitCode);
        })).setNeedsServer(false);
      }
    }
  }
});

module.exports = {
  CompilerApi: bibliograph.CompilerApi
};
