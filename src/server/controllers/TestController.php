<?php
/* ************************************************************************

   Bibliograph: Collaborative Online Reference Management

   http://www.bibliograph.org

   Copyright:
   2007-2017 Christian Boulanger

   License:
   LGPL: http://www.gnu.org/licenses/lgpl.html
   EPL: http://www.eclipse.org/org/documents/epl-v10.php
   See the LICENSE file in the project's top-level directory for details.

   Authors:
   * Chritian Boulanger (cboulanger)

************************************************************************ */

namespace app\controllers;

use app\controllers\traits\PropertyPersistenceTrait;
use app\models\Datasource;
use app\models\Folder;
use app\models\Reference;
use app\modules\z3950\models\Search;
use lib\components\Configuration as Conf;
use lib\dialog\Alert;
use lib\exceptions\UserErrorException;
use Yii;
use lib\channel\Channel;
use yii\web\UnauthorizedHttpException;

/**
 * A controller for JSONRPC methods intended to test the application.
 */
class TestController extends AppController
{

  use PropertyPersistenceTrait;

  protected $foo;
  protected $bar;

  /**
   * @inheritDoc
   *
   * @var array
   */
  protected $noAuthActions = ["echo", "throw-error","notify-test-name"];

  public function actionThrowError()
  {
    $e = new \Exception("Exception thrown on purpose");
    Yii::error($e);
    throw $e;
  }

  public function actionEcho($message) {
    if (!YII_ENV_TEST) {
      throw new UnauthorizedHttpException("Unauthorized");
    }
    return $message;
  }

  public function actionNotifyTestName($testName) {
    if (!YII_ENV_TEST) {
      throw new UnauthorizedHttpException("Unauthorized");
    }
    Yii::info("Executing test '$testName'...");
  }

  public function actionTestPersistence($foo = null, $bar = null) {
    if ($foo) {
      $this->foo = $foo;
      $this->bar = $bar;
      $this->saveProperties();
      return "OK";
    }
    $this->restoreProperties();
    return [$this->foo, $this->bar];
  }

  public function actionError()
  {
    $exception = Yii::$app->errorHandler->exception;
    return [ "message" => $exception ];
  }

  /**
   * Returns the times this action has been called. Only for testing session storage.
   */
  public function actionCount()
  {
    $session = Yii::$app->session;
    $count = $session->get("counter");
    $count = $count ? $count + 1 : 1;
    $session->set( "counter", $count );
    return $count;
  }

  /**
   * @param $result
   * @param $message
   */
  public function actionTest2($result, $message )
  {
    (new Alert)->setMessage($message)->sendToClient();
  }

  public function create_messages($sessionId)
  {
    $channel = new Channel('test', $sessionId);
    for ($i=0; $i < 10 ; $i++) {
      $channel->send( "The time is " . date('l, F jS, Y, h:i:s A'));
    }
    $channel->send("done");
  }

  public function actionAlert( $message )
  {
    (new Alert)->setMessage($message)->sendToClient();
  }

  public function actionSimpleEvent()
  {
    $this->dispatchClientMessage("foo","Hello World");
  }

  /**
   * @param string $json
   * @return mixed
   */
  public function actionShelve($json)
  {
    //$args = func_get_args();
    $args = \json_decode($json,true);
    return call_user_func_array( [$this,"shelve"], $args);
  }

  /**
   * @param $shelfId
   * @throws \Exception
   */
  public function actionUnshelve($shelfId)
  {
    if( ! $this->hasInShelf($shelfId) ){
      throw new \Exception("Shelf id '$shelfId' has no data");
    }
    return $this->unshelve($shelfId);
  }


  public function actionCreateSearch()
  {
    $datasource = Datasource::getInstanceFor('z3950_voyager');
    Search::setDatasource($datasource);
    $search = new Search(['query'=> 'foo', 'datasource' => 'bar', 'UserId' => Yii::$app->user->identity->id]);
    $search->save();
  }

  public function actionRetrieveSearch()
  {
    $datasource = Datasource::getInstanceFor('z3950_voyager');
    Search::setDatasource($datasource);
    $search = Search::findOne(['query'=> 'foo', 'datasource' => 'bar']);
    return $search ? $search->getAttributes() : null;
  }

  /**
   * @param string $datasource
   * @param int|null $number
   * @throws \yii\db\Exception
   * @return string
   */
  public function actionCreateFakeData(string $datasource, int $number = 100)
  {
    if( ! ( YII_ENV_TEST || Yii::$app->request->isConsoleRequest ) ){
      throw new \BadMethodCallException("Not allowed.");
    }
    $faker = \Faker\Factory::create();
    Yii::debug("Creating fake data in '$datasource'", __METHOD__);
    $folderClass = $this->getModelClass($datasource,"folder");
    $referenceClass = $this->getModelClass($datasource, "reference");
    for ($i=1; $i<=$number; $i++){
      /** @var Folder $folder */
      $folder = new $folderClass([
        'label' => $faker->sentence(5, true),
      ]);
      $folder->save();
      for ($j=1; $j<=$number; $j++){
        /** @var Reference $reference */
        $reference = new $referenceClass([
          'reftype'   => "book",
          'author'    => $faker->name,
          'title'     => $faker->sentence(10, true),
          'abstract'  => $faker->text,
          'year'      => (string) rand(1970,date("Y"))
        ]);
        $reference->save();
        $folder->link("references", $reference);
      }
    }
    $msg = "Created $i folders and " . $i*$j . " references";
    Yii::debug($msg, __METHOD__);
    return $msg;
  }

  /**
   * @throws \yii\base\InvalidConfigException
   */
  public function actionSendEmail() {
    if (!Conf::get("email.transport", true)) {
      throw new UserErrorException("No email transport has been configured");
    }
    Yii::$app->mailer->compose()
      ->setFrom(Conf::get("email.errors_from"))
      ->setTo(Conf::get("email.errors_to"))
      ->setSubject(Conf::get("email.errors_subject"))
      ->setTextBody("This is a test")
      ->send();
    return "Successfully sent E-mail";
  }

  public function actionZoteroSchema(){
    //$schema = new \app\modules\zotero\Schema();
    //return json_decode(json_encode($schema));
    $api = new \Hedii\ZoteroApi\ZoteroApi($_SERVER['ZOTERO_API_KEY']);
    $response = $api->user($_SERVER['ZOTERO_USER_ID'])
      ->collections()
      ->limit(1)
      ->send();
    return $response->getHeaders()['Total-Results'][0];
  }

  public function actionZoteroVersions() {
    $api = new \Hedii\ZoteroApi\ZoteroApi($_SERVER['ZOTERO_API_KEY']);
    return $api
      ->user($_SERVER['ZOTERO_USER_ID'])
      ->collections()
      ->versions()
      ->send()
      ->getBody();
  }

  public function actionVersion() {
    return Yii::$app->version;
  }
}
