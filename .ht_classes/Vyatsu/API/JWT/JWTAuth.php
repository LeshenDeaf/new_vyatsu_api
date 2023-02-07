<?php

namespace Vyatsu\API\JWT;

use \Bitrix\Main\Web\JWT;

use Vyatsu\API\App\App;
use Vyatsu\API\Exception;

class JWTAuth
{
    public const EXPIRES_IN = 900;

    private static string $secretKey
        = 'c895e2f3918ef3ef8748abcf28504eb6af92c5dd3aa9b9c75e15951499488028';

    /**
     * @param string $login
     * @param string $password
     * @return array
     */
    public static function login(string $login, string $password): array
    {
        if (!static::checkUserPassword($login, $password)) {
            throw new Exception\JWTException(
                'Incorrect login or password',
                401,
                [
                    'credentials' => 'Incorrect login or password'
                ]
            );
        }

        return [
            'access_token' => static::createAccessToken($login),
            'expires_in' => static::EXPIRES_IN,
            'refresh_token' => RefreshToken::createAndDeleteOld(
                (int)\CUser::GetByLogin($login)->Fetch()['ID']
            ),
        ];
    }

    public static function loginAs(int $wantedUserId): array
    {
        $user = App::getUser();

        if (!$user->getRights()->isIsAdmin()) {
            throw new Exception\JWTException(
                'You are not admin',
                403, [
                    'access' => 'Only admins have access to this method'
                ]
            );
        }

        return [
            'access_token' => static::createAccessToken($user->getLogin(), $user->getId(), $wantedUserId),
            'expires_in' => static::EXPIRES_IN,
            'refresh_token' => RefreshToken::createAndDeleteOld($user->getId(), $wantedUserId),
        ];
    }

    public static function changeAccount(int $wantedUserId): array
    {
        $user = App::getUser();

        if (!$user->getRights()->isIsAdmin()) {
            throw new Exception\JWTException(
                'You are not admin',
                403, [
                    'access' => 'Only admins have access to this method'
                ]
            );
        }

        RefreshToken::remove($user->getId());

        return [
            'access_token' => static::createAccessToken('', $wantedUserId),
            'expires_in' => static::EXPIRES_IN,
            'refresh_token' => RefreshToken::create($wantedUserId),
        ];
    }

    private static function checkUserPassword(
        string $login, string $password
    ): bool
    {
        $usr = new \CUser();

        return $usr->Login(
                $login, $password, 'N', 'Y'
            ) === true;
    }

    public static function createAccessToken(
        $login = '',
        $id = 0,
        int $loggedAs = 0,
        int $expiresIn = self::EXPIRES_IN
    ): string
    {
        if (!$login && !$id) {
            throw new Exception\JWTException(
                'Login and user_id are not defined',
                400,
                ['credentials' => 'Login and user_id are not defined']
            );
        }

        if (!$login) {
            $login = \CUser::GetByID($id)->Fetch()['LOGIN'];
        }
        if (!$id) {
            $id = (int)\CUser::GetByLogin($login)->Fetch()['ID'];
        }

        return static::encode([
            'user_id' => $id,
            'login' => $login,
            'logged_as' => $loggedAs ?: $id,
            'iat' => strtotime('now'),
            'exp' => strtotime("+$expiresIn seconds"),
        ]);
    }

    private static function encode($data): string
    {
        return JWT::encode($data, static::$secretKey, 'HS256');
    }

    /**
     * @return array
     */
    public static function logout(): array
    {
        $token = static::check();

        RefreshToken::remove($token->user_id);

        return [];
    }

    /**
     * @return mixed
     */
    public static function check()
    {
        if (!preg_match(
            '/Bearer\s(\S+)/',
            $_SERVER['HTTP_AUTHORIZATION'],
            $matches
        )) {
            throw new Exception\JWTException(
                'Token not found in request',
                400,
                ['authorization' => 'Access token is not defined']
            );
        }

        if (!($jwt = $matches[1])) {
            throw new Exception\JWTException(
                'No token was able to be extracted from the authorization header',
                400,
                ['authorization' => 'Access token is not defined']
            );
        }

        try {
            $decoded = static::checkToken($jwt);
        } catch (\Exception $exception) {
            throw new Exception\JWTException(
                'Incorrect token provided', 401, ['token' => 'Token expired']
            );
        }

        return $decoded;
    }

    /**
     * @param string $token
     * @return mixed
     */
    public static function checkToken(string $token)
    {
        $token = static::decode($token);
        $now = strtotime('now');

        if ($token->iat > $now || $token->exp < $now) {
            throw new Exception\JWTException(
                'Incorrect token provided', 401, ['token' => 'Token expired']
            );
        }

        return $token;
    }

    private static function decode($token)
    {
        return JWT::decode($token, static::$secretKey, ['HS256']);
    }

}
