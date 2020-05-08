<?php
require __DIR__ . "/DataTypes.php";
use Codeception\Util\JsonArray;
use PHPUnit\Framework\AssertionFailedError;
use PHPUnit\Framework\ExpectationFailedException;

trait JsonRpcTrait
{
   public $SERVER_EVENT_JSONRPC_METHOD_NAME = "qcl.io.jsonrpc.MessageBus.dispatch";
   static protected $jsonrpc_id = 0;

  /*
   =========================================================================================
    Send JSONRPC requests
   =========================================================================================
   */

  /**
   * @return int
   */
  protected function getCurrentJsonRpcId() {
    return static::$jsonrpc_id;
  }

  /**
   * @return int
   */
  protected function getNextJsonRpcId() {
    return ++static::$jsonrpc_id;
  }

  /**
   * Cache the access token
   *
   * @param string|null $t If set, store the value as the current token
   * @return string The access token, if one has been set
   */
  protected function token($t=null)
  {
    static $token = null;
    if( ! is_null($t) ) {
      $token = $t;
      $this->amBearerAuthenticated($token);
    }
    return $token;
  }

  /**
   * Clears the token
   */
  public function clearToken()
  {
    $this->token(false);
    $this->deleteHeader("Authorization");
  }

  /**
   * Send a JSONRPC request.
   * Authentication is done with the "Authorization: Bearer <token>" header.
   * The token is retrieved from the token() method.
   * Throws if the transport layer or the RPC method return an error.
   *
   * @param string $serviceController
   *    The name of the service (=controller) to be called
   * @param string $method
   *    The name of the RPC method
   * @param array $params
   *    The parameters to be passed to the method
   * @param string|false $ignoreErrors Can be false (default) "user" (user errors) or "all" (all errors including exceptions).
   * @return void
   */
  public function sendJsonRpcRequest($service, $method, array $params=[], $ignoreErrors=false )
  {
    $I = $this;
    // headers
    $I->haveHttpHeader('Content-Type', 'application/json');
    $I->haveHttpHeader('Accept', 'application/json');

    // payload
    $json = [
      'jsonrpc' => '2.0',
      'method'  => "$service.$method",
      'params'  => $params,
      'id'      => $this->getNextJsonRpcId()
    ];

    $path = "/json-rpc";

    // enable xdebug
    if (strstr($this->getScenario()->current("env"),"xdebug")) {
      $this->setCookie("XDEBUG_SESSION", 1);
    }
    // pass token in json payload since Authorization: Bearer headers are broken in codeception
    $json['access-token'] = $this->token();

    // send request and validate response
    $this->sendPOST( $path, $json );
    $I->canSeeResponseCodeIs(200);
    $I->seeResponseIsJson();
    if ($ignoreErrors === "all" or $ignoreErrors === true) {
      return;
    }
    try {
      $I->dontSeeUserError();
    } catch (AssertionFailedError $e) {
      if ($ignoreErrors !== "user" ){
        throw $e;
      }
    }
    $I->dontSeeJsonRpcError();
  }

  /*
   =========================================================================================
    Check JSONRPC Responses
   =========================================================================================
   */

  /**
   * Shorthand method aliasing grabRequestedResponseByJsonPath($path)[0]
   *
   * @param string $path
   * @return mixed
   */
  public function getByJsonPath($path)
  {
    return $this->grabRequestedResponseByJsonPath($path)[0];
  }

  /**
   * Returns the json path to the data that is the response to the current remote procedure call.
   * In most cases, it is "$", i.e. the top level object. However, if the return datat consists of
   * batched responses, the path is the index of the response we are looking for.
   * @return mixed
   */
  public function getRequestedResponsePath()
  {
    $path= null;
    $data = json_decode($this->grabResponse(), false, 512, JSON_THROW_ON_ERROR);
    if (is_array($data)) {
      foreach ($data as $index => $rpc) {
        if (isset($rpc->id) and $rpc->id == $this->getCurrentJsonRpcId()) {
          $path = '$.' . $index . '.';
        }
      }
    } else if (is_object($data) && $data->id == $this->getCurrentJsonRpcId()) {
      $path = "$.";
    }
    if ($path===null) {
      throw new RuntimeException("JSONRPC response is not valid or does not contain an object with the request id.");
    }
    return $path;
  }


  /**
   * @param $jsonPath
   * @return mixed
   */
  public function grabRequestedResponseByJsonPath($jsonPath) {
    $jsonPath = str_replace("$.","", $jsonPath);
    return (new JsonArray($this->grabResponse()))->filterByJsonPath($this->getRequestedResponsePath() . $jsonPath);
  }

  /**
   * @param string $jsonPath
   * @throws AssertionFailedError
   */
  public function seeRequestedResponseMatchesJsonPath($jsonPath)
  {
    $response = $this->grabResponse();
    $jsonPath = $this->getRequestedResponsePath() . str_replace("$.", "", $jsonPath);
    $this->assertNotEmpty(
      (new JsonArray($response))->filterByJsonPath($jsonPath),
      "Received JSON response did not match the JsonPath `$jsonPath`.\nJson Response: \n" . $response
    );
  }

  /**
   * @param string $jsonPath
   * @throws AssertionFailedError
   */
  public function dontSeeRequestedResponseMatchesJsonPath($jsonPath)
  {
    $response = $this->grabResponse();
    $jsonPath = $this->getRequestedResponsePath() . $jsonPath;
    $this->assertEmpty(
      (new JsonArray($response))->filterByJsonPath($jsonPath),
      "Received JSON response matches the JsonPath `$jsonPath` but should not.\nJson Response: \n" . $response
    );
  }

  /**
   * Throws if no RPC result is in the response
   * @return void
   * @throws AssertionFailedError
   */
  public function seeJsonRpcResult()
  {
    $this->seeRequestedResponseMatchesJsonPath('result');
  }

  /**
   * Returns the result of the response of the current rpc request
   * @return mixed
   * @throws AssertionFailedError
   */
  public function grabJsonRpcResult()
  {
    $this->seeJsonRpcResult();
    return $this->grabRequestedResponseByJsonPath('result')[0];
  }

  /**
   * Throws if the RPC method does not return an error
   * @param string|null $message Optionally checks the message
   * @param int|null $code Optionally checks the error code
   * @return void
   * @throws AssertionFailedError
   */
  public function seeJsonRpcError($message=null, $code=null)
  {
    if ($message){
      $this->assertContains( $message, $this->grabRequestedResponseByJsonPath('error.message')[0]);
    }
    if ($code){
      $this->assertEquals( $code, $this->grabRequestedResponseByJsonPath('error.code')[0] );
    }
    if (is_null($message) and is_null($code)) {
      $this->assertEmpty($this->grabRequestedResponseByJsonPath('error'));
    }
  }

  /**
   * Throws if the RPC method does not return an error
   * @param int|null $code Optional error code to check
   * @return void
   * @throws AssertionFailedError
   */
  public function dontSeeJsonRpcError($code=null)
  {
    if ($code) {
      $responseErrorCode = $this->grabRequestedResponseByJsonPath('error.code');
      if ($responseErrorCode) {
        $this->assertNotEquals($code, $responseErrorCode[0]);
      }
    } else {
      $this->dontSeeRequestedResponseMatchesJsonPath('error');
    }
  }

  /**
   * Checks that no user error has occurred
   */
  public function dontSeeUserError(){
    $this->dontSeeJsonRpcError( 2);
  }

  /**
   * Checks that a user error has occurred
   */
  public function seeUserError($message=null){
    $this->seeJsonRpcError($message, 2);
  }

  /**
   * Returns the jsonrpc error
   *
   * @return mixed
   * @throws AssertionFailedError
   */
  public function grabJsonRpcError()
  {
    $this->seeJsonRpcError();
    return $this->grabRequestedResponseByJsonPath('error')[0];
  }

  /**
   * Expects a token in the json response.
   * @return string The access token
   * @throws AssertionFailedError
   */
  public function seeTokenInResponse()
  {
    $token = $this->grabRequestedResponseByJsonPath('result.token');
    if ($token) {
      return $this->token($token[0]);
    } else {
      throw new AssertionFailedError("No token in response data");
    }
  }



  /**
   * Compares the JSONRPC received with the given value as two pretty-printed
   * JSON strings and throws if differences exist. The result can be drilled into
   * using the key syntax from Yii's ArrayHelper
   *
   * @param mixed $result
   * @param string|\Closure|array $key
   * @param string|integer $path
   * @return void
   * @throws AssertionFailedError
   * @see \yii\helpers\ArrayHelper::getValue()
   */
  public function compareJsonRpcResultWith( $result, $path=null )
  {
    $expected = json_encode( $result, JSON_PRETTY_PRINT );
    $received = $this->grabJsonRpcResult();
    if( ! is_null( $path) ){
      if( is_numeric($path) and is_array($received) ){
        $received = $received[$path];
      } else {
        $received = \yii\helpers\ArrayHelper::getValue($received, $path);
      }
    }
    $received = json_encode( $received, JSON_PRETTY_PRINT );
    $this->assertEquals($expected, $received);
  }

  /**
   * Loads and returns expected data for a Cest method
   * @param string $method
   * @param string $suite Defaults to "api"
   * @param bool $asJson Whether to return the data as a PHP data type (false-default) or as a JSON string (true)
   * @return *
   */
  public function loadExpectedData($method, $suite = "api", $asJson=false) {
    $dataFile =
      realpath( __DIR__ . "/../tests/$suite/_expected") . "/" .
      str_replace(["\\","::"], ["/","."], $method) . ".json";
    $data = file_get_contents($dataFile);
    return $asJson ? $data : json_decode($data);
  }

  /**
   * Compares the current result data with the data saved for the
   * given method.
   * @param string $method
   * @param string|integer $path
   */
  public function expectDataforMethod($method, $path=null) {
    // normalize data to include result data only
    $data = $this->loadExpectedData($method);
    if (is_object($data) and isset($data->jsonrpc) and isset($data->result)) {
      $data = $data->result;
    }
    $this->compareJsonRpcResultWith($data, $path);
  }

  /**
   * Return notifications, if any.
   * @return array
   */
  public function grabJsonRpcNotifications()
  {
    $data = json_decode($this->grabResponse(), false, 512, JSON_THROW_ON_ERROR);
    $notifications = [];
    if (is_array($data)) {
      foreach ($data as $rpc) {
        if (!isset($rpc->id)) {
          $notifications[] = $rpc;
        }
      }
    }
    return $notifications;
  }

  /**
   * Checks if the jsonrpc response contains a notification with the given data
   * @param string $method
   * @param JsonExpressionType|JsonPathType|RegExType|null $params
   *  If string, the JSON representation of the parameters of the called method.
   *  If a RegExType, an expression to match the json. If JsonPathType, a JsonPath expression the parameters need to match.
   * @throws AssertionFailedError
   */
  public function seeJsonRpcNotification(string $method, $params = null) {
    $notifications = $this->grabJsonRpcNotifications();
    foreach ($notifications as $notification) {
      $seen = false;
      if ($notification->method === $method) {
        $seen = true;
      }
      if ($params) {
        $paramsAsJson = json_encode($notification->params);
        if ($params instanceof JsonExpressionType) {
          $seen = (string) $params === json_encode($notification->params);
        } elseif ($params instanceof JsonPathType) {
          $findPath = (new JsonArray($paramsAsJson))->filterByJsonPath((string)$params);
          $seen = count($findPath) > 0;
        } else if ($params instanceof RegExType) {
          $seen = (bool) preg_match((string)$params, $paramsAsJson);
        } else {
          throw new InvalidArgumentException("Second parameter must be JsonExpressionType, JsonPathType or RegExType");
        }
      }
      if ($seen) return;
    }
    $msg = "Reponse does not contain a notification with the given method";
    if ($params) {
      if (is_array($params)) {
        $msg .= " and parameters " . json_encode($params);
      }
      else {
        $msg .= " and parameters matching `$params`";
      }
    }
    throw new AssertionFailedError($msg);
  }

  /**
   * Checks if response contains server event with the given name and data.
   * The data can also be a jsonpath expression, a regular expression, or a json expression.
   * @param string $expectedName
   * @param null|mixed|JsonPathType|JsonExpressionType|RegExType $expectedData
   * @throws AssertionFailedError
   */
  public function seeServerEvent(string $expectedName, $expectedData=null) {
    foreach ($this->grabJsonRpcNotifications() as $notification) {
      if ($notification->method == $this->SERVER_EVENT_JSONRPC_METHOD_NAME) {
        if ($expectedData === null) return;
        $event = $notification->params[0];
        $receivedName = $event->name;
        if ($expectedName !== $receivedName ) continue;
        $receivedData = $event->data;
        $receivedDataAsJson = json_encode($receivedData);
        if ($expectedData instanceof JsonExpressionType) {
          if ((string) $expectedData !== $receivedDataAsJson) return;
        } else if ($expectedData instanceof RegExType) {
          if (preg_match((string)$expectedData, $receivedDataAsJson)) return;
        } else if ($expectedData instanceof JsonPathType) {
          $jsonPath = (string)$expectedData;
          $result = (new JsonArray($receivedDataAsJson))->filterByJsonPath($jsonPath);
          if (count($result) > 0) return;
        } else {
          if ($expectedData === $receivedData) return;
        }
      }
    }
    throw new AssertionFailedError("No server event with name '$expectedName' and data '$expectedData' has been received");
  }

  /*
   =========================================================================================
    Interact with JSONRPC API
   =========================================================================================
   */


  /**
   * Log in anonymously
   *
   * @return void
   */
  public function loginAnonymously()
  {
    $this->sendJsonRpcRequest( "access","authenticate", [] );
    $this->seeTokenInResponse();
  }

  /**
   * Log in as an Adminstrator
   *
   * @return string The Access token
   */
  public function loginAsAdmin()
  {
    return $this->loginWithPassword( "admin", "admin" );
  }

  /**
   * Log in with a username and password
   *
   * @return string The access token
   */
  public function loginWithPassword( $user, $password )
  {
    $this->sendJsonRpcRequest( "access","authenticate", [ $user, $password ] );
    return $this->seeTokenInResponse();
  }


  /**
   * Logs out current user
   *
   * @return void
   */
  public function logout()
  {
    $this->sendJsonRpcRequest('access','logout');
  }
}
