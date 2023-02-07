<?php

namespace Vyatsu\API\JWT;

use \Bitrix\Main\Web\JWT;
use Vyatsu\API\Exception\JWTException;


class RefreshToken
{
    public const REFRESH_IBLOCK_ID = 233;
    public const EXPIRES_IN = 2419200;

    /**
     * @throws \Exception
     */
    public static function refresh(string $refreshToken)
    {
        global $DB;

        $DB->StartTransaction();

        try {
            $oldToken = static::find($refreshToken);
            $token = static::create((int)$oldToken['PROPERTY_USER_VALUE'], (int)$oldToken['PROPERTY_LOGGED_AS_VALUE']);

            static::delete($oldToken['ID']);

            $DB->Commit();
        } catch (JWTException|\Exception $exception) {
            $DB->Rollback();

            throw $exception;
        }

        return [
            'access_token' => JWTAuth::createAccessToken(
                '', (int)$oldToken['PROPERTY_USER_VALUE'], (int)$oldToken['PROPERTY_LOGGED_AS_VALUE']
            ),
            'expires_in' => JWTAuth::EXPIRES_IN,
            'refresh_token' => $token,
        ];
    }

    public static function find(string $refreshToken)
    {
        $res = \CIBlockElement::GetList(
            ["ID" => "DESC"],
            [
                'IBLOCK_ID' => RefreshToken::REFRESH_IBLOCK_ID,
                'ACTIVE' => 'Y',
                'PROPERTY_TOKEN' => $refreshToken,
            ],
            false, $arNavParams ?? [],
            ['ID', 'IBLOCK_ID', 'PROPERTY_TOKEN', 'PROPERTY_USER', 'PROPERTY_LOGGED_AS']
        );

        if (!$res->SelectedRowsCount()) {
            throw new JWTException(
                'Refresh token not found', 422, [
                    'refresh_token' => "Refresh token not found"
                ]
            );
        }

        return $res->Fetch();
    }

    public static function create(int $userId, ?int $loggedAs = null)
    {
        $token = JWTAuth::createAccessToken('', $userId, $loggedAs ?? $userId, self::EXPIRES_IN);
//        $token = bin2hex(random_bytes(32));

        $el = new \CIBlockElement;

        $arLoadProductArray = [
            "MODIFIED_BY" => $userId,
            "CREATED_BY" => $userId,
            "IBLOCK_SECTION_ID" => false,
            "IBLOCK_ID" => RefreshToken::REFRESH_IBLOCK_ID,
            "PROPERTY_VALUES" => [
                'TOKEN' => $token,
                'LOGGED_AS' => $loggedAs ?? $userId,
                'USER' => $userId,
            ],
            "NAME" => $userId . ' ' . $token,
            "ACTIVE" => 'Y',
            "PREVIEW_TEXT" => "",
            "DETAIL_TEXT" => "",
        ];

        if (!$el->Add($arLoadProductArray)) {
            throw new JWTException(
                'Refresh token creation error',
                500,
                ['server' => 'Cannot create token']
            );
        }

        return $token;
    }

    public static function createAndDeleteOld(int $userid, ?int $loggedAs = null)
    {
        static::remove($userid);

        return static::create($userid, $loggedAs ?? $userid);
    }

    public static function delete(int $elementId)
    {
        \CIBlockElement::Delete($elementId);
    }

    public static function remove(int $userId)
    {
        $res = \CIBlockElement::GetList(
            ["ID" => "DESC"],
            [
                'IBLOCK_ID' => RefreshToken::REFRESH_IBLOCK_ID,
                'PROPERTY_USER' => $userId,
            ],
            false, $arNavParams ?? [],
            ['ID']
        );

        while ($arF = $res->Fetch()) {
            static::delete($arF['ID']);
        }
    }


}
