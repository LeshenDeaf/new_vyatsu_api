<?php

namespace Vyatsu\API\App;

use Vyatsu\API\Exception\APIException;
use Vyatsu\API\Utils\Utils;

class Router
{
    /**
     * @var array<string>
     */
    public static array $handlers;

    public static function route()
    {
        $path = explode('/', $_SERVER[REQUEST_URI]);

        $class = $path[count($path) - 3];

        if (self::$handlers[$class]['secured']
            && !App::getUser()
        ) {
            return;
        }

        $handler = new self::$handlers[$class]['handler']();

        if (!$handler) {
            throw new APIException(
                "Unknown class '{$class}'",
                500, [
                    'error' => 'Attempt to call method of unknown class'
                ]
            );
        }

        $handler->handle(self::getMethod());
    }

    public static function add(string $urlPart, string $handler, bool $secured = true) {
        if (!trim($handler)) {
            throw new APIException(
                'No handler passed',
                500,
                ['error' => "No handler determined for root $urlPart"]
            );
        }
        self::$handlers[$urlPart] = compact('handler', 'secured');
    }

    public static function getMethod(): string
    {
        $path = explode('/', $_SERVER[REQUEST_URI]);

        return Utils::toCamelCase(explode('?', $path[count($path) - 2])[0]);
    }
}
