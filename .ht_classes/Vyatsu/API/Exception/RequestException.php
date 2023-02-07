<?php

namespace Vyatsu\API\Exception;

use Throwable;

class RequestException extends APIException
{
    protected $request;

    public function __construct(
        $message = "", $request = [], $errors = []
    ) {
        parent::__construct($message, 422, $errors);
        $this->request = $request;
        $this->errors = $errors;
    }

    public function getRequest()
    {
        return $this->request;
    }

    public function setRequest($request): void
    {
        $this->request = $request;
    }

    public function toArray(): array
    {
        return [
            'message' => $this->message,
            'errors' => $this->errors,
            'request' => $this->request,
        ];
    }
}
