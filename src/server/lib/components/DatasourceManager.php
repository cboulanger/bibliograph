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

use app\controllers\SetupController;
use app\models\Datasource;
use app\models\Schema;
use fourteenmeister\helpers\Dsn;
use lib\components\ConsoleAppHelper as Console;
use lib\exceptions\RecordExistsException;
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
   * Creates a new datasource and returns it. You should set at least the `title` property before saving the ActiveRecord
   * to the database. By default, it will use the database connection provided by the `db` component.
   * It returns the instance for the given schema class, not the \app\models\Datasource instance created.
   *
   * @param string $datasourceName
   *    The name of the new datasource
   * @param string $schemaName |null
   *    Optional name of a schema. If not given, the default schema is used.
   * @return \app\models\Datasource
   * @throws \Exception
   * @throws RecordExistsException
   */
  public function create(
    $datasourceName,
    $schemaName = null)
  {
    if (!$datasourceName or !is_string($datasourceName)) {
      throw new \InvalidArgumentException("Invalid datasource name");
    }

    if( ! $schemaName ){
      $schemaName = Yii::$app->config->getPreference('app.datasource.baseschema' );
    }

    $schema = Schema::findByNamedId($schemaName);
    if( ! $schema ){
      throw new \InvalidArgumentException("Schema '$schemaName' does not exist.");
    }

    $class = $schema->class;
    if (!is_subclass_of($class, Datasource::class)) {
      throw new \InvalidArgumentException("Invalid schema class '$class'. Must be subclass of " . Datasource::class);
    }

    if (Datasource::findByNamedId($datasourceName)) {
      throw new RecordExistsException("Datasource exists");
    }
    /** @noinspection MissedFieldInspection */
    $datasource = new Datasource([
      'namedId' => $datasourceName,
      'title'   => $datasourceName,
      'schema'  => $schemaName,
      'prefix'  => $class::createTablePrefix($datasourceName),
      'active'  => 1
    ]);
    $datasource->setAttributes($this->parseDsn());
    $datasource->save();

    // get the subclass instance and configure it
    $datasource = Datasource::getInstanceFor($datasourceName);

    $this->createModelTables($datasource);
    // @todo work with interface instead
    if ($datasource instanceof \app\models\BibliographicDatasource) {
      $datasource->addDefaultFolders();
      Yii::$app->config->createKey("datasource.$datasourceName.fields.exclude","list");
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
    /** @var \app\schema\AbstractReferenceSchema $schema */
    $schema = $datasource->getSchema()->one();
    $migrationNamespace = $schema->migrationNamespace;
    $params = [
      'all',
      'migrationNamespaces' => $migrationNamespace,
    ];
    Yii::debug("Creating model tables for '{$datasource->namedId}', using schema '{$schema->namedId}' and migration namespace '$migrationNamespace' ...");
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
    /** @var \app\schema\AbstractReferenceSchema $schema */
    $schema = $datasource->getSchema()->one();
    $migrationNamespace = $schema->migrationNamespace;
    $datasource->delete();
    if ($deleteData) {
      Yii::debug("Deleting model tables for '$namedId'...");
      $db = $datasource->getConnection();
      $params = [
        'all',
        'migrationNamespaces' => $migrationNamespace,
      ];
      Console::runAction('migrate/down', $params, null, $db);
      Yii::info("Deleted model tables for '$namedId''.");
    }
  }

  /**
   * Checks if new migrations exist for the tables of the given datasource
   * schema class
   *
   * @param Schema $schema
   * @return bool True if new migrations, false if up-to-date
   * @throws MigrationException
   * @throws \Exception
   */
  public function checkNewMigrations(Schema $schema)
  {
    $params = [
      'all',
      'migrationNamespaces' => $schema->migrationNamespace,
    ];
    /** @var \app\models\Datasource $datasource */
    $datasource = $schema->datasources[0];
    $db = $datasource->getConnection();
    $output = Console::runAction('migrate/new', $params, null, $db);
    return ! $output->contains("up-to-date");
  }

  /**
   * Migrates the tables of the datasources which are of the
   * given schema class to the newest version
   *
   * @param Schema $schema
   * @return void
   * @throws MigrationException
   * @throws \Exception
   */
  public function migrate(Schema $schema)
  {
    if (YII_ENV_PROD) {
      throw new UserErrorException(Yii::t('app', 'Datasource migrations are not allowed in production mode. Please contact the adminstrator'));
    };
    Yii::info("Migrating schema '{$schema->namedId}'...");
    $datasources = $schema->datasources;
    /** @var \app\models\BibliographicDatasource $datasource */
    foreach ($datasources as $datasource) {
      $params = [
        'all',
        'migrationNamespaces' => $schema->migrationNamespace,
      ];
      /** @var \yii\db\Connection $db */
      $db = $datasource->getConnection();
      // backwards compatibility
      if( $schema->namedId == SetupController::DATASOURCE_DEFAULT_SCHEMA ){
        $tables = $db->schema->getTableNames();
        $reference_table = "{$db->table_prefix}_data_Reference";
        $migration_table = "{$db->table_prefix}migrations";
        Yii::debug("Checking if table $reference_table exists but $migration_table is missing...");
        $isV2Datasource = in_array($reference_table, $tables) and !in_array($migration_table, $tables);
        if ($isV2Datasource) {
          Yii::info("Marking v2 datasource '{$datasource->namedId}'...");
          Console::runAction('migrate/mark', ["app\\migrations\\schema\\{$schema->namedId}\\m171219_230854"]);
        }
      }
      Yii::info("Migrating datasource '{$datasource->namedId}'...");
      Console::runAction('migrate/up', $params, null, $db);
    }
  }
}