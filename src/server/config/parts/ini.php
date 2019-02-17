<?php
use Yosymfony\Toml\Toml;
if (!method_exists("Toml", "parseFile")) {
  // v0.x, as long as we support PHP7.0
  // @todo remove after PHP7.0 support is dropped
  return Toml::parse(file_get_contents(APP_CONFIG_FILE));
}
// >v1.0
return Toml::parseFile(APP_CONFIG_FILE);
