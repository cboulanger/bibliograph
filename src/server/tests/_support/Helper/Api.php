<?php
namespace Helper;

// here you can define custom actions
// all public methods declared in helper class will be available in $I

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
