<?php
namespace Helper;

class Api extends \Codeception\Module
{

  public function _beforeSuite($settings = []) {
    codecept_debug("Called before suite runs!");
  }

  /**
   * Get current url
   * @return mixed
   * @throws \Codeception\Exception\ModuleException
   */
  public function getBaseUrl()
  {
    return $this->getModule('PhpBrowser')->_getConfig('url');
  }
}
