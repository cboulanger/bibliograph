<?php

namespace lib\plugin;

interface PluginInterface {
  /**
   * Installs the plugin
   * @param bool $enabled
   * @return mixed
   */
  public function install(bool $enabled);

  /**
   * Removeds the plugin
   * @return mixed
   */
  public function uninstall();

  /**
   * @return string
   */
  public function getVersion();

  /**
   * @return string
   */
  public function getInstalledVersion();
}
