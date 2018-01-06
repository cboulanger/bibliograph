<?php

namespace app\controllers\dto;

use JsonRpc2\Dto;

// @todo this should really extend Service result
class AuthResult extends Dto
{
    /** @var string */
    public $message;

    /** @var string */
    public $token;

    /** @var string */
    public $sessionId;
}