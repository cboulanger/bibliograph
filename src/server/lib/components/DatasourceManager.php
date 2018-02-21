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

namespace lib\components;

use app\models\Datasource;
use fourteenmeister\helpers\Dsn;
use lib\components\ConsoleAppHelper as Console;
use lib\exceptions\UserErrorException;
use Sse\Data;
use Yii;

/**
 * Component class providing methods to create and migrate datasource tables,
 * i.e. model tables that have a common prefix and are meant to be used together to
 * form a complex source of data
 * values
 */
class DatasourceManager extends \yii\base\Component
{
  /**
   * For backward-compatibility with v2, this map translates old schema names to
   * new class names
   *
   * @var array
   */
  protected $legacySchema = [
    'bibliograph.schema.bibliograph2' => '\app\models\BibliographicDatasource'
  ];

  /**
   * Creates a new datasource and returns it. You should set at least the `title` property before saving the ActiveRecord
   * to the database. By default, it will use the database connection provided by the `db` component.
   * It returns the instance for the given schema class, not the \app\models\Datasource instance created.
   *
   * @param string $datasourceName
   * @param string $className |null
   *    Optional class name of the datasource, defaults to "\app\models\BibliographicDatasource"
   * @return \app\models\Datasource
   * @throws \Exception
   * @throws \yii\db\Exception If a datasource of that name already exists.
   * @todo Rename "schema" to "class"
   */
  public function create(
    $datasourceName,
    $className = null)
  {
    if (!$datasourceName or !is_string($datasourceName)) {
      throw new \InvalidArgumentException("Invalid datasource name");
    }

    // @todo reimplement datasource schemas
    if(!$className) {
      $className = \app\models\BibliographicDatasource::class;
    }

    if(!is_subclass_of($className, Datasource::class)) {
      throw new \InvalidArgumentException("Invalid class '$className'. Must be subclass of " . Datasource::class);
    }

    if( Datasource::find()->where(['namedId'=>$datasourceName])->exists() ){
      throw new \yii\db\Exception("Datasource exists");
    }
    $datasource = new Datasource([
      'namedId' => $datasourceName,
      'title' => $datasourceName,
      'schema' => $className,
      'prefix' => $className::createTablePrefix($datasourceName),
      'active' => 1
    ]);
    $datasource->setAttributes($this->parseDsn());
    $datasource->save();
    // get the subclass instance and configure it
    $datasource = Datasource::getInstanceFor($datasourceName);
    $this->createModelTables($datasource);
    // @todo work with interface instead
    if ($datasource instanceof \app\models\BibliographicDatasource) {
      $datasource->addDefaultFolders();
    }
    return $datasource;
  }

  /**
   * Parses a DSN string in a way that can be stored in the datasource db record.
   * If no DSN string is passed, the app default dsn is used.
   * @param string|null $dsn
   * @throws \Exception
   * @return array
   */
  public function parseDsn($dsn = null)
  {
    $dsn = ($dsn ? $dsn : Yii::$app->db->dsn);
    $dsn = Dsn::parse($dsn);
    $db = Yii::$app->db;
    return [
      'type' => $dsn->sheme,
      'host' => $dsn->host,
      'port' => $dsn->port,
      'database' => $dsn->database,
      'username' => $db->username,
      'password' => $db->password,
      'encoding' => $db->charset,
    ];
  }

  /**
   * Creates the tables necessary for a datasource, using migration files
   *
   * @param \app\models\Datasource $datasource
   * @return void
   * @throws \Exception if console action fails
   */
  public function createModelTables(\app\models\Datasource $datasource)
  {
    $params = [
      'all',
      'migrationNamespaces' => 'app\\migrations\\schema\\datasource',
    ];
    Yii::trace("Creating model tables for {$datasource->namedId}...");
    $db = $datasource->getConnection();
    Console::runAction('migrate/up', $params, null, $db);
    Yii::info("Created model tables for {$datasource->namedId}.");
  }


  /**
   * Deletes a datasource record and optionally all connected data
   * @param string $namedId
   * @param bool $deleteData
   *    Whether all connected models and their data should be deleted, too.
   * @throws \Exception|\Throwable in case delete or console action failed.
   */
  public function delete($namedId, $deleteData = false)
  {
    $datasource = Datasource::getInstanceFor($namedId);
    $datasource->delete();
    if( $deleteData ){
      Yii::trace("Deleting model tables for '$namedId'...");
      $db = $datasource->getConnection();
      $params = [
        'all',
        'migrationNamespaces' => 'app\\migrations\\schema\\datasource',
      ];
      Console::runAction('migrate/down', $params, null, $db);
      Yii::info("Deleted model tables for '$namedId''.");
    }
  }

  /**
   * Checks if new migrations exist for the tables of the given datasource
   * schema class
   *
   * @param string $class Datasource schema class
   * @return void
   */
  public function checkNewMigrations($class)
  {
    throw new \BadMethodCallException("Not implemented");
  }

  /**
   * Migrates the tables of the datasources which are of the
   * given schema class to the newest version
   *
   * NOT READY!
   *
   * @param string $class Datasource schema class
   * @return boolean Returns true if migration succeeded
   * @throws UserErrorException
   */
  public function migrate($class)
  {
    throw new \BadMethodCallException("Not ready!");
    if (YII_ENV_PROD) {
      throw new UserErrorException('Datasource migrations are not allowed in production mode. Please contact the adminstrator');
    };

    // backward compatibitly 
    if (isset($this->legacySchema[$class])) {
      $class = $this->legacySchema[$class];
    }

    if (!is_subclass_of($class, Datasource::class)) {
      throw new \InvalidArgumentException($class . " is not a subclass of " . Datasource::class);
    }

    $datasources = Datasource::find()->where(['schema' => $class])->all();
    foreach ($datasources as $datasource) {
      $params = [
        'all',
        'migrationNamespaces' => 'app\\migrations\\schema\\datasource',
      ];
      $db = $datasource->getConnection();
      $tables = $db->schema->getTableNames();
      if (!in_array("{$db->prefix}migrations", $tables)) {
        // need to migrate/mark tables
      }
      Console::runAction('migrate/up', $params, null, $db);
    }
    return true;
  }
}