<?php

namespace app\controllers;

use lib\exceptions\Exception;
use yii\console\ExitCode;
use Yii;

class UtilsController extends \yii\console\Controller
{
  /**
   * Converts legacy databases to default encoding (utf8mb4). Works only with
   * MySql/MariaDB. All parameters except $database_name are optional and will be
   * taken from the application configuration if not provided
   * @param string $database_name The name of the database to convert
   * @param string $username The name of the user having sufficient access
   *   rights to do the conversion
   * @param string $password The password of that user
   * @param string|null $host Optional host
   * @param int|null $port Optional (non-standard) port
   * @return int
   */
  public function actionUpdateEncoding(string $database_name, string $username=null, string $password=null, string $host=null, int $port=null)
  {
    // code is adapted from https://stackoverflow.com/questions/105572/a-script-to-change-all-tables-and-fields-to-the-utf-8-bin-collation-in-mysql

    // config
    $db = $database_name;
    preg_match("/host=([^;]+)/", Yii::$app->db->dsn, $h);
    preg_match("/port=([0-9]+)/", Yii::$app->db->dsn, $p);
    $host = $host ?? $h[1];
    $port = $port ?? $p[1];
    $username = $username ?? Yii::$app->db->username;
    $password = $password ?? Yii::$app->db->password;
    // target
    $target_charset = "utf8mb4";
    $target_collation = "utf8mb4_unicode_ci";
    $target_bin_collation = "utf8mb4_bin";
    /**
     * @param $conn
     * @param $query
     * @return bool|\mysqli_result
     */
    function query($conn, $query)
    {
      $res = mysqli_query($conn, $query);
      if (mysqli_errno($conn)) {
        $error_message = mysqli_error($conn);
        throw new \RuntimeException("Mysql Error: " . $error_message . PHP_EOL . "Query was: $query" . PHP_EOL);
      }
      return $res;
    }

    /**
     * @param $type
     * @return string
     */
    function binary_typename($type)
    {
      $mysql_type_to_binary_type_map = array(
        "VARCHAR" => "VARBINARY",
        "CHAR" => "BINARY(1)",
        "TINYTEXT" => "TINYBLOB",
        "MEDIUMTEXT" => "MEDIUMBLOB",
        "LONGTEXT" => "LONGBLOB",
        "TEXT" => "BLOB"
      );
      $typename = "";
      if (preg_match("/^varchar\((\d+)\)$/i", $type, $mat)){
        $typename = $mysql_type_to_binary_type_map["VARCHAR"] . "(" . (2 * $mat[1]) . ")";
      } else if (!strcasecmp($type, "CHAR")) {
        $typename = $mysql_type_to_binary_type_map["CHAR"] . "(1)";
      } else if (array_key_exists(strtoupper($type), $mysql_type_to_binary_type_map)) {
        $typename = $mysql_type_to_binary_type_map[strtoupper($type)];
      }
      return $typename;
    }

    try {
      // Connect to database
      $conn = mysqli_connect($host, $username, $password, $port);
      mysqli_select_db($conn, $db);
      query($conn, "USE `$database_name`;");

      // Get list of tables
      $tabs = array();
      $query = "SHOW TABLES";
      $res = query($conn, $query);
      while (($row = mysqli_fetch_row($res)) != null)
        $tabs[] = $row[0];

      // Now fix tables
      foreach ($tabs as $tab) {
        $indices = [];
        $res = query($conn, "SHOW INDEX FROM `{$tab}`");
        while (($row = mysqli_fetch_array($res)) != null) {
          if ($row['Key_name'] != "PRIMARY") {
            $append = true;
            foreach ($indices as $i => $index) {
              if ($index["name"] == $row['Key_name']) {
                $indices[$i]["col"][] = $row['Column_name'];
                $append = false;
              }
            }
            if ($append) {
              $indices[] = [
                "name" => $row['Key_name'],
                "unique" => !($row['Non_unique'] == "1"),
                "col" => [$row['Column_name']]
              ];
            }
          }
        }
        // drop index
        foreach ($indices as $index) {
          query($conn, "ALTER TABLE `{$tab}` DROP INDEX `{$index["name"]}`");
          echo "Dropped index {$index["name"]} of {$tab}. Unique: {$index["unique"]}\n";
        }
        // analyze columns
        $res = query($conn, "SHOW FULL COLUMNS FROM `{$tab}`");
        while (($row = mysqli_fetch_array($res)) != null) {
          $name = $row[0];
          $type = $row[1];
          // add length information to index
          if (preg_match("/varchar\(([0-9]+)\)/", $type, $m)) {
            foreach($indices as $i => $index) {
              if (array_search($name, $index['col'])) {
                $indices[$i]['length'] = $m[1];
              }
            }
          }
          $current_collation = $row[2];
          $target_collation_bak = $target_collation;
          if (!strcasecmp($current_collation, "latin1_bin"))
            $target_collation = $target_bin_collation;
          $set = false;
          $binary_typename = binary_typename($type);
          if ($binary_typename != "") {
            query($conn, "ALTER TABLE `{$tab}` MODIFY `{$name}` {$binary_typename}");
            query($conn, "ALTER TABLE `{$tab}` MODIFY `{$name}` {$type} CHARACTER SET '{$target_charset}' COLLATE '{$target_collation}'");
            $set = true;
            echo "Altered field {$name} on {$tab} of type {$type}\n";
          }
          $target_collation = $target_collation_bak;
        }
        // Rebuild indices
        foreach ($indices as $index) {
          // Handle multi-column indices
          $joined_col_str = "";
          foreach ($index["col"] as $col) {
            if ($index['name'] == "fulltext" ){
              $joined_col_str = $joined_col_str . ", `" . $col . "` ({$index['length']})";
            } else {
              $joined_col_str = $joined_col_str . ", `" . $col . "`";
            }
          }
          $joined_col_str = substr($joined_col_str, 2);
          $query = "";
          if ($index["unique"]) {
            $query = "CREATE UNIQUE INDEX `{$index["name"]}` ON `{$tab}` ({$joined_col_str})";
          } else {
            $query = "CREATE INDEX `{$index["name"]}` ON `{$tab}` ({$joined_col_str})";
          }
          query($conn, $query);
          echo "Created index {$index["name"]} on {$tab}. Unique: {$index["unique"]}\n";
        }
        // Set default character set and collation for table
        query($conn, "ALTER TABLE `{$tab}`  DEFAULT CHARACTER SET '{$target_charset}' COLLATE '{$target_collation}'");
        $indices = null;
      }
      // Set default character set and collation for database
      query($conn, "ALTER DATABASE `{$db}` DEFAULT CHARACTER SET '{$target_charset}' COLLATE '{$target_collation}'");
      // finish
      mysqli_close($conn);
    } catch (\Exception $e) {
      $this->stderr("Error: " . $e->getMessage() . PHP_EOL);
      return ExitCode::UNSPECIFIED_ERROR;
    }
    return ExitCode::OK;
  }
}
