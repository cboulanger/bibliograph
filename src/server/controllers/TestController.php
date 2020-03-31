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

use app\models\Datasource;
use app\models\Folder;
use app\models\Reference;
use app\modules\z3950\models\Search;
use lib\dialog\Alert;
use lib\exceptions\UserErrorException;
use Yii;

use app\controllers\AppController;

use app\models\User;
use app\models\Role;
use app\models\Permission;
use app\models\Group;
use app\models\Session;
use app\models\Message;
use lib\channel\Channel;

/**
 * A controller for JSONRPC methods intended to test the application.
 */
class TestController extends AppController
{
  /**
   * @inheritDoc
   *
   * @var array
   */
  protected $noAuthActions = ["echo", "throw-error"];

  /**
   * Returns the first argument passed unchanged
   * @param $msg
   * @return mixed
   */
  public function actionEcho($msg) {
    return $msg;
  }

  public function actionThrowError()
  {
    throw new \InvalidArgumentException("Testing invalid argument exception");
  }


  public function actionError()
  {
    $exception = Yii::$app->errorHandler->exception;
    return [ "message" => $exception ];
  }

  /**
   * @throws UserErrorException
   */
  public function actionTest()
  {
    throw new UserErrorException("This is a user error");
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
    return "Created $i folders and " . $i*$j . " references";
  }
}
