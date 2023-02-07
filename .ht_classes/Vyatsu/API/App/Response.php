<?php

namespace Vyatsu\API\App;

use Vyatsu\API\Exception\APIException;
use Vyatsu\API\Exception\RequestException;
use Vyatsu\API\Exception\JWTException;

class Response
{
    /**
     * @param array<string> $headers
     * @return Response
     */
    public function setHeaders(array $headers): Response
    {
        foreach ($headers as $header) {
            header($header);
        }

        return $this;
    }

    public function error(APIException $error): Response
    {
        $this->code($error->getCode())->json($error->toArray());
        return $this;
    }

    public function json($data): Response
    {
        $GLOBALS['APPLICATION']->RestartBuffer();

        header('Content-Type: application/json');

        if ($data instanceof \RuntimeException) {
            echo json_encode($data->toArray());
        } else {
            echo json_encode($data);
        }

        return $this;
    }

    public function code(int $code = 200): Response
    {
        \http_response_code($code);

        return $this;
    }
}
