<?php

use React\EventLoop\Factory;
use React\HttpClient\Client;
use React\HttpClient\Response;

class BackupCest
{

  protected $token;

  protected $datasource = "datasource1";

  protected $dump_before;

  protected $dump_after;

  protected $backupFile;

  /**
   * Returns a mysql dump of the current database, removing comments and timestamps so that
   * dumps can be compared.
   * @return bool|string
   * @throws RuntimeException
   */
  protected function mysqldump(){
    $file = tempnam(sys_get_temp_dir(),"dump");
    $tables = ['data_Reference','data_Folder','data_Transaction','join_Folder_Reference'];
    $dump_tables = "";
    foreach($tables as $table){
      $dump_tables .= "{$this->datasource}_{$table} ";
    }
    $params = [
      "--user=root",
      "--password=" . $_SERVER['DB_ROOT_PASSWORD'],
      "--host=" . $_SERVER['DB_HOST'],
      "--result-file=" . $file,
      "tests $dump_tables"
    ];

    $cmd = "mysqldump " . implode(" ", $params) . " 2>&1";
    exec($cmd, $output);
    if(count($output)) throw new RuntimeException(implode("; ", $output));
    // remove comments
    return preg_replace( "/^--.*$/m","",
      preg_replace( "/'[0-9]{4}-[0-9]{2}-[0-9]{2} [0-9]{2}:[0-9]{2}:[0-9]{2}'/", "*removed*",
        file_get_contents($file)));
  }

  /**
   * @param array $params
   * @param ApiTester $I
   * @param int $debug Whether the stream chunks should be passed to codecept_debug()
   * @return string The stream content
   */
  protected function handleStreamResponse($route, array $params, ApiTester $I, $debug=false)
  {
    $url = $_SERVER['APP_SERVER_URL'] . "/src/server/test/$route?" . http_build_query($params) . "&access-token=".$this->token;
    codecept_debug("Requesting chunked response from $url");
    $loop = Factory::create();
    $content = "";
    $client = new Client($loop);
    $request = $client->request('GET', $url);
    $request->on('response', function ( Response $response) use(&$content, $debug, $I) {
      $response->on('data', function ($chunk) use (&$content, $debug, $I) {
        if ($chunk[0] == "{" ) {
          $err = json_decode($chunk);
          $I->fail("The server returned an error: " . $err->message);
        };
        $content .= $chunk;
        if ($debug) codecept_debug($chunk);
      });
      $response->on('end', function() use ($debug) {
        if ($debug) codecept_debug("Done.");
      });
    });
    $request->on('error', function (\Exception $e) use ($I) {
      $I->fail("An error occurred in handling the stream response: " . $e->getMessage());
    });
    $request->end();
    $loop->run();
    return $content;
  }

  /**
   * @param \ApiTester $I
   * @param \Codeception\Scenario $scenario
   */
  public function testBackupAndRestore(\ApiTester $I, \Codeception\Scenario $scenario)
  {
    $I->amGoingTo("setup the application and log in as admin'");
    $I->sendJsonRpcRequest("setup", "setup");
    $this->token = $I->loginAsAdmin();

    $I->amGoingTo("create data to backup'");
    $I->sendJsonRpcRequest("test", "create-fake-data", [$this->datasource,10]);

    $I->amGoingTo("save a mysql dump of the database");
    try{
      $this->dump_before = $this->mysqldump();
    } catch (RuntimeException $e){
      $I->fail($e->getMessage());
    }
    $I->amGoingTo("create a backup of '{$this->datasource}'");
    $params = [
      'datasource'    => $this->datasource,
      'id'            => 'dummy'
    ];
    $this->handleStreamResponse('backup/progress/create', $params, $I);

    $I->amGoingTo("change '{$this->datasource}' by adding more records");
    $I->sendJsonRpcRequest("test", "create-fake-data", [$this->datasource,5]);

    $I->amGoingTo("get a list of backup files for '{$this->datasource}'");
    $I->sendJsonRpcRequest("backup.service", "list", [$this->datasource]);
    $list = $I->grabJsonRpcResult();
    $I->assertTrue(count($list)>0,"List of Backups must contain at least one item.");
    $I->seeResponseMatchesJsonType(['result'=>[
      0 => [
        'timestamp' => 'integer',
        'label'     => 'string',
        'value'     => 'string'
      ]
    ]]);

    $I->amGoingTo("restore the latest backup of '{$this->datasource}' and compare the before/after mysql dumps...");
    $params = [
      'datasource'   => $this->datasource,
      'id'           => 'dummy',
      'file'         => $list[0]['value'] // latest backup is first in array
    ];
    $this->handleStreamResponse('backup/progress/restore', $params, $I);
    try{
      $this->dump_after = $this->mysqldump();
    } catch (RuntimeException $e){
      $I->fail($e->getMessage());
    }
    $I->assertEquals($this->dump_before, $this->dump_after);
  }
}
