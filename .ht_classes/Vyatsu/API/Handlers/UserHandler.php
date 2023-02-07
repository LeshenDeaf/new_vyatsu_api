<?php

namespace Vyatsu\API\Handlers;

use Vyatsu\API\App\App;
use Vyatsu\API\Exception\APIException;
use Vyatsu\API\Exception\JWTException;
use Vyatsu\API\JWT\JWTAuth;

class UserHandler extends Handler
{
    public function __construct()
    {
        parent::__construct();
    }

    protected function me(): void
    {
        if (!$user = App::getUser()) {
            return;
        }

        $this->response->code()->json($user->toArray());
    }

}
