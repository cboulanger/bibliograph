<?php

namespace app\modules\zotero\controllers;

use app\controllers\AppController;
use Hedii\ZoteroApi\ZoteroApi;
use lib\exceptions\UserErrorException;
use Yii;

abstract class Controller extends AppController {

  /**
   * @param string $cacheId
   * @param string $datasourceId
   * @return mixed
   */
  static function getCached(string $cacheId, string $datasourceId){
    return Yii::$app->cache->get("zotero-$datasourceId-$cacheId");
  }

  /**
   * @param string $cacheId
   * @param string $datasourceId
   * @param mixed $value
   */
  static function setCached(string $cacheId, string $datasourceId, $value){
    Yii::$app->cache->set("zotero-$datasourceId-$cacheId", $value);
  }

  /**
   * @param string|array $cacheIds
   * @param string $datasourceId
   * @param mixed $value
   */
  static function deleteCached($cacheIds, string $datasourceId){
    if (!is_array($cacheIds)) {
      $cacheIds = [$cacheIds];
    }
    foreach ($cacheIds as $id) {
      Yii::$app->cache->delete("zotero-$datasourceId-$id");
    }
  }

  /**
   * @param string $datasourceName
   * @return ZoteroApi
   * @throws \lib\exceptions\UserErrorException
   */
  protected function getZoteroApi(string $datasourceName) : ZoteroApi{
    return $this->datasource($datasourceName)->zoteroApi;
  }

  protected function throwConnectionError() {
    throw new UserErrorException(Yii::t("plugin.zotero", "Cannot connect to Zotero server"));
  }
}
