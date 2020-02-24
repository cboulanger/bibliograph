<?php

namespace lib\filters\auth;

use yii\filters\auth\AuthMethod;

/**
 * JsonRpcPayloadTokenAuth is an action filter that supports the authentication based on the access token
 * passed as a (non-standard) key in the jsonrpc payload. The use case is situations where authentication
 * via http header doesn't work or is not available
 */
class JsonRpcPayloadTokenAuth extends AuthMethod
{
  /**
   * @var string the parameter name for passing the access token
   */
  public $tokenParam = 'access-token';


  /**
   * {@inheritdoc}
   */
  public function authenticate($user, $request, $response)
  {
    $jsonPayload = \json_decode($request->getRawBody(),true);
    $accessToken = is_array($jsonPayload) ? $jsonPayload[$this->tokenParam] : null;
    if (is_string($accessToken)) {
      $identity = $user->loginByAccessToken($accessToken, get_class($this));
      if ($identity !== null) {
        return $identity;
      }
    }
    if ($accessToken !== null) {
      $this->handleFailure($response);
    }

    return null;
  }
}
