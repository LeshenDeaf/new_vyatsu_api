<?php

namespace Vyatsu\API\App;

use Vyatsu\API\App\User\User;
use Vyatsu\API\Exception\APIException;
use Vyatsu\API\Exception\JWTException;
use Vyatsu\API\JWT\JWTAuth;

class App
{
    private static ?Response $response = null;
    private static ?User $user = null;

    public function __construct()
    {
        if (!self::$response) {
            self::$response = new Response();
        }
    }

    public static function getResponse(): Response
    {
        if (!self::$response) {
            self::$response = new Response();

            return self::$response;
        }

        return self::$response;
    }

    public static function getUser(): ?User
    {
        if (!self::$user) {
            try {
                self::$user = new User(JWTAuth::check());
            } catch (JWTException $exception) {
                self::$response->error($exception);
                return null;
            }
        }

        return self::$user;
    }

    public function work()
    {
        try {
            Router::route();
        } catch (APIException $exception) {
            self::$response->error($exception);
        }
    }
}
