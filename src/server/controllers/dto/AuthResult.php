<?php

namespace app\controllers\dto;


class AuthResult extends Base
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
