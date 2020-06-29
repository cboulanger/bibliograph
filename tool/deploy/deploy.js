require("dotenv").config();
const fs = require("fs");
const SSH2 = require("ssh2-promise");
const process = require("process");

// parse environment variables
const configs = [];
// eslint-disable-next-line no-constant-condition
while (true) {
  let hosts = [];
  // eslint-disable-next-line no-constant-condition
  while (true) {
    let deploy_number = configs.length+1;
    let host_number = hosts.length+1;
    let target = process.env[`DEPLOY${deploy_number}_HOST${host_number}_TARGET`];
    let identity = process.env[`DEPLOY${deploy_number}_HOST${host_number}_IDENTITY`];
    if (!target) {
      break;
    }
    if (!identity) {
      throw new Error("No identity information given.");
    } else if (!fs.existsSync(identity)) {
      throw new Error(`Identity file ${identity} does not exist.`);
    }
    let [username, host, port] = target.match(/^([^@]+)@([^:]+):?([0-9]+)?$/).slice(1);
    hosts.push({
      host,
      username,
      port,
      identity
    });
  }
  if (hosts.length === 0) {
    break;
  }
  configs.push(hosts);
}

var ssh = new SSH2(configs[0]);

(async function() {
  try {
    await ssh.connect();
    console.log("Connection established");
    var sftp = ssh.sftp();
    var data = await sftp.readdir("/");
    console.log(data.map(file => file.longname).join("\n")); //file listing
    ssh.close();
  } catch (e) {
    console.error(e);
  }
})();

