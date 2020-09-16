<?php

namespace app\modules\zotero\controllers;

use app\controllers\AppController;
use Hedii\ZoteroApi\ZoteroApi;
use lib\exceptions\UserErrorException;
use Yii;

abstract class Controller extends AppController {

  /**
   * @param string $type
   * @param string $datasourceId
   * @return mixed
   */
  static function getCached(string $type, string $datasourceId){
    return Yii::$app->cache->get("zotero-$datasourceId-$type");
  }

  /**
   * @param string $type
   * @param string $datasourceId
   * @param mixed $value
   */
  static function setCached(string $type, string $datasourceId, $value){
    Yii::$app->cache->set("zotero-$datasourceId-$type", $value);
  }

  /**
   * @param string|array $types
   * @param string $datasourceId
   * @param mixed $value
   */
  static function deleteCached($types, string $datasourceId){
    if (!is_array($types)) {
      $types = [$types];
    }
    foreach ($types as $type) {
      Yii::$app->cache->delete("zotero-$datasourceId-$type");
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
