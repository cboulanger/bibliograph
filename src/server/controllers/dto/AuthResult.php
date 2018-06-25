<?php

namespace app\controllers\dto;

use JsonRpc2\Dto;

// @todo this should really extend Service result
class AuthResult extends Dto
{
    /** @var string */
    public $message = null;

    /** @var string */
    public $token = null;

    /** @var string */
    public $sessionId = null;

    /** @var string */
    public $error = null;
}