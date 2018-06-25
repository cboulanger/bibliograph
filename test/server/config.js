let config = {};
// build env
if (process.env.IP && process.env.PORT){
  // we're in C9
  // @todo
} else {
  config.host = "127.0.0.1";
  config.port = 8080;
  config.url = `http://${config.host}:${config.port}/?r=`;
}
config.timeout = 20000;
module.exports = config;