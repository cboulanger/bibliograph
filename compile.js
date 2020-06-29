/**
 * Runs UI tests for Bibliograph
 */
const fs = require("fs");
const path = require("path");
const TEST_PATH = path.join("test", "uitests");

qx.Class.define("bibliograph.LibraryApi", {
  extend: qx.tool.cli.api.LibraryApi,
  statics: {
    TEST_PATH
  },
  members: {
    /**
     * Called when the compiler loads the configuration
     * @return {Promise<void>}
     */
    async load () {
      let command = this.getCompilerApi().getCommand();
      command.addListener("writtenApplication", async evt => {
        // make sure this is run only once
        let app = evt.getData();
        console.log("*** Written app: " + app.getName());
        console.log(command);
        if (app.getName() === "bibliograph") {
          switch (true) {
            case (command instanceof qx.tool.cli.commands.Test):
              await this.runTests(command);
              break;
            case (command instanceof qx.tool.cli.commands.Deploy):
              await this.deploy(command);
              break;
          }
        }
      });
    },
  
    /**
     * Run the javascript-based tests
     * @param command
     * @return {Promise<void>}
     */
    async runTests(command) {
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
    },
  
    /**
     * Deploy the application
     * @param command
     */
    deploy(command) {
      console.log("*************  Deploy!");
    }
  }
});

module.exports = {
  LibraryApi: bibliograph.LibraryApi
};
