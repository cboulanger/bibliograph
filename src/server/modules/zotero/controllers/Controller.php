<?php

namespace app\modules\zotero\controllers;

use app\controllers\AppController;
use Hedii\ZoteroApi\ZoteroApi;

abstract class Controller extends AppController {

  /**
   * @param string $datasourceName
   * @return ZoteroApi
   * @throws \lib\exceptions\UserErrorException
   */
  protected function getZoteroApi(string $datasourceName) : ZoteroApi{
    return $this->datasource($datasourceName)->zoteroApi;
  }
}
