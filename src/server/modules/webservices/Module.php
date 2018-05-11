<?php

namespace app\modules\webservices;

use lib\util\Executable;
use Yii;
use app\models\{
  Datasource, Role, Schema, User
};
use app\modules\webservices\models\{
  Datasource as WebservicesDatasource, Search
};
use Exception;
use lib\exceptions\RecordExistsException;
use yii\web\UserEvent;


/**
 * webservices module definition class
 * @property WebservicesDatasource[] $datasources
 */
class Module extends \lib\Module
{
  /**
   * The version of the module
   * @var string
   */
  protected $version = "0.0.4";

  /**
   * A string constant defining the category for logging and translation
   */
  const CATEGORY="webservices";

  /**
   * @inheritdoc
   */
  public $controllerNamespace = 'app\modules\webservices\controllers';


  /**
   * Installs the plugin.
   * @param boolean $enabled
   *    Whether the module should be enabled after installation (defaults to false)
   * @return boolean
   * @throws Exception
   */
  public function install($enabled = false)
  {

    // register datasource
    try {
      Schema::register("webservices", WebservicesDatasource::class);
    } catch (RecordExistsException $e) {
      // ignore
    }

    // preferences and permissions
    $app = Yii::$app;
    $this->addPreference("lastDatasource", "", true);
    try {
      $app->accessManager->addPermissions(["webservices.manage"], [
        Role::findByNamedId("admin")
      ]);
    } catch (\Exception $e) {
      //ignore
    }
    // create datasources
    $this->createDatasources();

    // register module
    return parent::install(true);
  }

  protected function getConnectors()
  {
    $connectors = [];
    foreach (scandir(__DIR__ . "/connectors") as $file ){
      if ( $file[0]==='.' ) continue;
      if ( ends_with($file,'.php' ) ) {
        $class = __NAMESPACE__ . "\\connectors\\" . basename( $file, ".php");
        $connectors[] = new $class();
      }
    }
    return $connectors;
  }

  /**
   * Create datasources
   * @throws Exception
   */
  public function createDatasources()
  {
    $manager = Yii::$app->datasourceManager;
    foreach ($this->getConnectors() as $connector) {
      $repoNamedId = self::CATEGORY . '_' . $connector->id;
      $repoModel = Datasource::findByNamedId($repoNamedId);
      if( ! $repoModel ){
        $repoModel = $manager->create($repoNamedId, "webservices");
      }
      $repoModel->setAttributes([
        'title'       => $connector->name,
        'description' => $connector->description,
        'hidden'      => 1 // should not show up as selectable datasource
      ]);
      $repoModel->save();
      Yii::info("Added webservice '{$connector->name}'", self::CATEGORY);
    }
  }


  /**
   * Returns the named ids of the all datasources with 'webservices' schema
   * @return array
   */
  public function getDatasourceNames()
  {
    return Datasource::find()
      ->select("namedId")
      ->where(['schema' => 'webservices'])
      ->column();
  }

  /**
   * Returns all datasources that have the 'webservices' schema
   * @return WebservicesDatasource[]
   */
  public function getDatasources()
  {
    return Datasource::find()
      ->select("namedId")
      ->where(['schema' => 'webservices'])
      ->all();
  }

  /**
   * Returns the query as a string constructed from the
   * query data object
   * @param object $queryData
   * @return string
   */
  public function getQueryString($queryData)
  {
    return self::fixQuery( $queryData->query->cql );
  }

  /**
   * Used to transform query before it is converted to a CQL object
   * @param string $query
   * @return string
   */
  public static function fixQuery(string $query) : string
  {
    if (substr($query, 0, 3) == "978") {
      $query = 'isbn=' . $query;
    } if (substr($query, 0, 3) == "10.") {
    $query = 'doi=' . $query;
  }
    return $query;
  }

  /**
   * Called when a user logs out
   * @param User $user
   */
  public function clearSearchData(User $user)
  { 
    if( count($this->datasources )){
      try{
        $datasource = Datasource::getInstanceFor($this->datasources[0]->namedId);
        Search::setDatasource($datasource);
        $searches = Search::find()->where(['UserId'=>$user->id])->all();
        foreach ($searches as $search) $search->delete();
        Yii::debug("Deleted search data.",self::CATEGORY);
      } catch (\Error $e) {
        Yii::error($e->getMessage());
      }
    }
  }

  /**
   * Deletes all tables that belong to this module (i.e., start with webservices)
   */
  public function dropAllWebservicesTables()
  {
    $names = $this->getDatasourceNames();
    foreach ($names as $namedId) {
      Yii::debug("Deleting webservices datasource '$namedId'...", self::CATEGORY);
      try {
        Yii::$app->datasourceManager->delete($namedId, true);
      } catch (Exception $e) {
        Yii::error("Problem deleting datasource '$namedId':" . $e, self::CATEGORY );
      }
    }
  }

  /**
   * Event handler for logout event
   * @param UserEvent $e
   */
  public static function on_after_logout ( UserEvent $e) {
    /** @var \app\models\User|null $user */
    $user = $e->identity;
    if( ! $user ) return;
    /** @var Module $module */
    $module = \Yii::$app->getModule('webservices');
    try{
      $module->clearSearchData($user);
    } catch (\Throwable $e){
      \Yii::warning($e->getMessage());
    }
  }

  /**
   * Event handler for delete event
   * @param \yii\base\Event $e
   */
  public static function on_after_delete ( \yii\base\Event $e) {
    /** @var \app\models\User|null $user */
    $user = $e->sender;
    if( ! $user ) return;
    /** @var Module $module */
    $module = \Yii::$app->getModule('webservices');
    try{
      $module->clearSearchData($user);
    } catch (\Throwable $e){
      \Yii::warning($e->getMessage());
    }
  }
}
