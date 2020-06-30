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
    /**
     * Called when the compiler loads the configuration
     * @return {Promise<void>}
     */
    async load () {
      let config = await this.base(arguments);
      this.addListenerOnce("changeCommand", () => {
        let command = this.getCommand();
        command.addListener("writtenApplication", async evt => {
          // make sure this is run only once
          let app = evt.getData();
          if (app.getName() === "bibliograph") {
            switch (true) {
              case (command instanceof qx.tool.cli.commands.Test):
                await this.__runTests(command);
                break;
              case (command instanceof qx.tool.cli.commands.Deploy):
                await this.__deploy(command);
                break;
            }
          }
        });
      });
      return config;
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
    },
  
    /**
     * Deploy the application
     * @param command
     */
    async __deploy(command) {
      console.log("*************  Deploy!");
    }
  }
});

module.exports = {
  CompilerApi: bibliograph.CompilerApi
};
