<?php

namespace Vyatsu\API\Handlers;

use \Vyatsu\API\App\App;
use \Vyatsu\API\App\Response;
use \Vyatsu\API\Exception\APIException;
use \Vyatsu\API\Exception\RequestException;
use \Vyatsu\API\Exception\JWTException;
use \Vyatsu\API\JWT\JWTAuth;

class Handler
{
    protected array $request;
    protected Response $response;

    public function __construct()
    {
        $this->request
            = json_decode(file_get_contents('php://input'), true) ?? [];
        $this->response = App::getResponse();
    }

    public function handle(string $method)
    {
        if (!method_exists($this, $method) ) {
            $this->response->error(
                new APIException(
                    'Undefined method "' . $method . '"',
                    404,
                    [
                        'error' => 'Method "' . $method . '" does not exist'
                    ]
                )
            );

            return;
        }

        $this->{$method}();
    }

    protected function requestError(string $message, array $errors)
    {
        $this->response->error(new RequestException(
            $message, $this->request, $errors
        ));
    }
}
