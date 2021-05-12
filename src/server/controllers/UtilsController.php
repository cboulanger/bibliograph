<?php

namespace app\controllers;

use lib\exceptions\Exception;
use yii\console\ExitCode;
use Yii;
use function GuzzleHttp\default_ca_bundle;

class UtilsController extends \yii\console\Controller
{

  /**
   * @var string The name of the user, having sufficient privileges for the appropriate action
   */
  public $username;

  /**
   * @var string The passsword for the database
   */
  public $password;

  /**
   * @var string The host on which the database server is running
   */
  public $host;

  /**
   * @var string Any non-standard port number for the database server
   */
  public $port;

  /**
   * @var int The maximal index key length supported by the database engine
   */
  public $maxKeyLength = 3072;

  /**
   * Command line options
   * @param string $actionID
   * @return array|string[]
   */
  public function options($actionID)
  {
    // to do switch on action id
    return ['username', 'password', 'host', 'port'];
  }

  /**
   * Converts legacy databases to default encoding (utf8mb4). Works only with
   * MySql/MariaDB. All options are optional, if not provided, the information will be
   * taken from the application configuration
   * @param string $database_name The name of the database to convert
   * @return int
   */
  public function actionUpdateEncoding(string $database_name)
  {
    // code is adapted from https://stackoverflow.com/questions/105572/a-script-to-change-all-tables-and-fields-to-the-utf-8-bin-collation-in-mysql

    // config
    $db = $database_name;
    preg_match("/host=([^;]+)/", Yii::$app->db->dsn, $h);
    preg_match("/port=([0-9]+)/", Yii::$app->db->dsn, $p);
    $host = $this->host ?? $h[1];
    $port = $this->port ?? $p[1];
    $username = $this->username ?? Yii::$app->db->username;
    $password = $this->password ?? Yii::$app->db->password;

    // target encoding
    $target_charset = "utf8mb4";
    $target_collation = "utf8mb4_unicode_ci";
    $target_bin_collation = "utf8mb4_bin";
    $target_number_bytes=4;
    $target_engine="InnoDB";

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
      $conn = mysqli_connect($host, $username, $password, $database_name, $port);

      // Get list of tables
      $tabs = array();
      $query = "SHOW TABLES";
      $res = query($conn, $query);
      while (($row = mysqli_fetch_row($res)) != null)
        $tabs[] = $row[0];

      // Now fix tables
      foreach ($tabs as $table) {
        // change database engine
        query($conn, "ALTER TABLE `$table` ENGINE = $target_engine;");
        echo "Changed database engine of `$table` to $target_engine\n";
        // indexes
        $indices = [];
        $res = query($conn, "SHOW INDEX FROM `{$table}`");
        while (($row = mysqli_fetch_array($res)) != null) {
          if ($row['Key_name'] != "PRIMARY") {
            $append = true;
            foreach ($indices as $i => $index) {
              if ($index["name"] == $row['Key_name']) {
                $indices[$i]["col"][] = $row['Column_name'];
                $indices[$i]["length"][$row['Column_name']] = 0;
                $append = false;
              }
            }
            if ($append) {
              $type = "";
              if ($row['Non_unique'] != "1") {
                $type = "UNIQUE";
              } else if ($row['Index_type'] === "FULLTEXT") {
                $type = "FULLTEXT";
              }
              $indices[] = [
                "name" => $row['Key_name'],
                "type" => $type,
                "typeLabel" => $type ? strtolower($type) . " " : "",
                "col" => [$row['Column_name']],
                "length" => [$row['Column_name'] => 0]
              ];
            }
          }
        }
        // drop index
        foreach ($indices as $index) {
          query($conn, "ALTER TABLE `{$table}` DROP INDEX `{$index["name"]}`");
          echo "Dropped {$index["typeLabel"]}index `{$index["name"]}` of `{$table}`\n";
        }
        // analyze columns
        $res = query($conn, "SHOW FULL COLUMNS FROM `{$table}`");
        while (($row = mysqli_fetch_array($res)) != null) {
          $name = $row[0];
          $type = $row[1];
          // add length information to index
          foreach($indices as $i => $index) {
            $pos = array_search($name, $index['col']);
            if ($pos !== false && $indices[$i]['type'] !== "FULLTEXT") {
              if (preg_match("/varchar\(([0-9]+)\)/i", $type, $m)) {
                $indices[$i]['length'][$name] = $m[1];
              } else if (stripos("text", $type) !== false) {
                // convert to fulltext index
                $indices[$i]['type'] = "FULLTEXT";
                $indices[$i]['typeLabel'] = "fulltext ";
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
            query($conn, "ALTER TABLE `{$table}` MODIFY `{$name}` {$binary_typename}");
            query($conn, "ALTER TABLE `{$table}` MODIFY `{$name}` {$type} CHARACTER SET '{$target_charset}' COLLATE '{$target_collation}'");
            $set = true;
            echo "Altered field `{$name}` on `{$table}` of type {$type}\n";
          }
          $target_collation = $target_collation_bak;
        }
        // Rebuild indices
        foreach ($indices as $index) {
          // Handle multi-column indices
          $joined_col_str = "";
          $total_length = 0;
          foreach ($index['length'] as $col => $length) {
            $total_length += $length;
          }
          foreach ($index["col"] as $col) {
            if (isset($index['length'][$col]) and $index['length'][$col] !== 0){
              // decrease key length proportional to the available length
              $fraction = min(1,$this->maxKeyLength/($total_length*$target_number_bytes));
              $length = floor( $index['length'][$col] * $fraction);
              $joined_col_str = $joined_col_str . ", `" . $col . "`($length)";
            } else {
              $joined_col_str = $joined_col_str . ", `" . $col . "`";
            }
          }
          $joined_col_str = substr($joined_col_str, 2);
          $query = "CREATE {$index['type']} INDEX `{$index["name"]}` ON `{$table}` ({$joined_col_str})";
          query($conn, $query);
          echo "Created {$index["typeLabel"]}index `{$index["name"]}` on `{$table}` with columns {$joined_col_str}. \n";
        }
        // Set default character set and collation for table
        query($conn, "ALTER TABLE `{$table}`  DEFAULT CHARACTER SET '{$target_charset}' COLLATE '{$target_collation}'");
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
