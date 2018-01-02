<?php

namespace app\controllers\dto;

use JsonRpc2\Dto;

class AuthResult extends Dto
{
    /** @var string */
    public $message;

    /** @var string */
    public $token;

    /** @var string */
    public $sessionId;
}