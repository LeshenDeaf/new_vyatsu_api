<?php

namespace Vyatsu\API\Exception;

use Throwable;
use Vyatsu\API\Utils\IArrayable;

class JWTException extends APIException
{
	public function __construct($message = "", $code = 401, array $errors = [])
	{
		parent::__construct($message, $code, $errors);
	}

    public function toArray(): array
    {
        return [
            'message' => $this->message,
            'errors' => $this->errors,
        ];
    }
}
