<?php
/**
 * Created by PhpStorm.
 * User: cboulanger
 * Date: 03.04.18
 * Time: 23:28
 */

namespace app\modules\zotero\models;

use app\modules\zotero\Module;
use Hedii\ZoteroApi\ZoteroApi;
use Illuminate\Support\Str;
use Yii;

/**
 * A Zotero Datasource. Connection details are stored as followed:
 * - database: path (users/xxxx or groups/xxxx)
 * - password: API Key
 * @package app\modules\zotero
 * @property ZoteroApi $zoteroApi
 */
class Datasource extends \app\models\Datasource
{
  /**
   * The named id of the datasource schema
   */
  const SCHEMA_ID = "zotero";

  /**
   * @inheritdoc
   * @var string
   */
  static $name = "Zotero library";

  /**
   * @inheritdoc
   * @var string
   */
  static $description = "A proxy for a library hosted at zotero.org ";

  /**
   * This datasource does not support migrations
   * @override
   */
  public function getMigrationNamespace()
  {
    return null;
  }

  /**
   * @inheritDoc
   * @return \lib\Module|\yii\base\Module|Module
   */
  public function getModule() {
    return Yii::$app->getModule("zotero");
  }

  /**
   * Make sure the datasource has a "zotero_" prefix
   * @param $datasourceName
   * @return string
   */
  public static function createTablePrefix($datasourceName){
    if (!Str::startsWith($datasourceName, "zotero_")) {
      $datasourceName = "zotero_" . $datasourceName;
    }
    return $datasourceName;
  }

  /**
   * @inheritdoc
   */
  public function attributeLabels()
  {
    $al = parent::attributeLabels();
    $al['database'] = Yii::t('app', 'Zotero Path');
    return $al;
  }


  /**
   * @inheritDoc
   */
  public function getFormData()
  {
    $fd = parent::getFormData();
    return [
      'title' => $fd['title'],
      'description' => $fd['description'],
      'database' => [
        'label' => Yii::t('app', "Zotero Path"),
        'placeholder' => "users/<id> or groups/<id>"
      ],
      'password' => [
        'type' => "passwordfield",
        'label' => Yii::t('app', "API Key")
      ],
      'active' => $fd['active']
    ];
  }

  /**
   * Initialize the datasource, registers the models
   * @throws \InvalidArgumentException
   */
  public function init()
  {
    parent::init();
    $this->addModel( 'folder',   Collection::class,   'zotero.collection');
    $this->addModel( 'reference',   Item::class,   'zotero.item');
  }

  /**
   * This will alwas return a new object!
   * @return ZoteroApi
   */
  public function getZoteroApi() : ZoteroApi {
    $zoteroApi = new ZoteroApi($this->password);
    $zoteroApi->setPath($this->database);
    return $zoteroApi;
  }

  /**
   * @inheritDoc
   * @param bool $insert
   * @return bool
   * @throws \yii\base\Exception
   */
  public function beforeSave($insert)
  {
    if ($this->namedId && !Str::startsWith($this->namedId, "zotero_")) {
      $this->namedId = "zotero_" . $this->namedId;
    }
    $this->type = "zotero";
    $this->readonly = true;
    return parent::beforeSave($insert);
  }
}
