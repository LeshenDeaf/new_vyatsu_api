<?php

namespace Vyatsu\API\Handlers;

use Vyatsu\API\App\App;
use Vyatsu\API\Exception\JWTException;
use Vyatsu\API\JWT\JWTAuth;
use Vyatsu\API\JWT\RefreshToken;
use Vyatsu\API\Utils\Utils;

class JWTHandler extends Handler
{
    public function __construct()
    {
        parent::__construct();
    }

    protected function login(): void
    {
        $login = trim($this->request['login']);
        $password = trim($this->request['password']);

        if (!$login) {
            $this->requestError('The given data is invalid.', [
                'email' => 'Login not provided',
            ]);
            return;
        }
        if (!$password) {
            $this->requestError('The given data is invalid.', [
                'password' => 'Password not provided',
            ]);
            return;
        }

        try {
            $res = JWTAuth::login($login, $password);

            $this->response->code()->json($res);
        } catch (JWTException $exception) {
            $this->response->error($exception);
        }
    }

    protected function logout(): void
    {
        try {
            $res = JWTAuth::logout();
            $this->response->code()->json($res);
        } catch (JWTException $exception) {
            $this->response->error($exception);
        }
    }

    protected function check(): void
    {
        try {
            $res = JWTAuth::check();
            $this->response->code()->json($res);
        } catch (JWTException $exception) {
            $this->response->error($exception);
        }
    }

    protected function refresh(): void
    {
        if (!($refreshToken = $this->request['refresh_token'])) {
            $this->requestError('The given data is invalid.', [
                'refresh_token' => 'Refresh token is not provided',
            ]);
            return;
        }

        try {
            $res = RefreshToken::refresh($refreshToken);

            $this->response->code()->json($res);
        } catch (JWTException|\Exception $exception) {
            $this->response->error($exception);
        }
    }

    protected function loginAs(): void
    {
        try {
            if (!($user = App::getUser())
                || !$this->checkLogin()
                || !$this->checkWantedId()
            ) {
                return;
            }

            if (!$user->getRights()->isIsAdmin()) {
                throw new JWTException(
                    'You have no rights to do this operation',
                    403,
                    ['rights' => 'Not admin']
                );
            }

            $res = JWTAuth::loginAs($this->request['wanted_user_id']);
            $this->response->code()->json($res);
        } catch (JWTException $exception) {
            $this->response->error($exception);
        }
    }

    protected function changeAccount(): void
    {
        try {
            if (!($user = App::getUser())
                ||!$this->checkWantedId()
            ) {
                return;
            }

            if (!$user->getRights()->isIsAdmin()) {
                throw new JWTException(
                    'You have no rights to do this operation',
                    403,
                    ['rights' => 'Not admin']
                );
            }

            $res = JWTAuth::changeAccount($this->request['wanted_user_id']);
            $this->response->code()->json($res);
        } catch (JWTException $exception) {
            $this->response->error($exception);
        }
    }

    private function checkWantedId()
    {
        if (!$this->request['wanted_user_id']) {
            $this->requestError('No "wanted_user_id" provided', [
                'wanted_user_id' => 'Value is undefined',
            ]);
            return false;
        }

        if (!is_int($this->request['wanted_user_id'])) {
            $this->requestError('"wanted_user_id" is not an integer', [
                'wanted_user_id' => 'Value is not an integer',
            ]);
            return false;
        }

        return true;
    }

    private function checkLogin()
    {
        return !empty($this->request['login'])
            && ($this->request['wanted_user_id'] = Utils::getUserId($this->request['login']));
    }
}
