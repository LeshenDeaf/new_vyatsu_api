<?php

namespace Vyatsu\API\Services\Votings;

use Vyatsu\API\Exception\APIException;

define('STOP_STATISTICS', true);
require_once $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/prolog_before.php';


class QuestionService
{
    use \Vyatsu\API\Utils\VotingUtils;

    public static array $answerTypes = [
        0 => 'radio',
        1 => 'checkbox',
        2 => 'select',
        3 => 'multiselect',
        4 => 'text',
        5 => 'text_area'
    ];

    private int $userId;
    private \CUser $user;

    public function __construct(int $userId)
    {
        if (!$userId) {
            throw new APIException('UserId not provided!', 400, ['data' => 'UserId not provided']);
        }

        $this->userId = $userId;

        $this->user = new \CUser();
        $this->user->Authorize($this->userId);
    }

    public function getQuestions(int $votingId)
    {
        $this->includeModuleOrFail();

        if (!$votingId) {
            $this->user->Logout();

            throw new APIException('VotingId not provided!', 400, ['data' => 'VotingId not provided']);
        }

        $arQuestions = [];

        $db_res = \CVoteQuestion::GetList(
            $votingId, "s_c_sort", "asc", ["ACTIVE" => "Y"]
        );

        while ($res = $db_res->GetNext()) {
            $arQuestions[$res['ID']] = $res + ["ANSWERS" => []];
        }

        if (!$arQuestions) {
            $this->user->Logout();

            return [];
        }

        $db_res = \CVoteAnswer::GetListEx(
            ["C_SORT" => "ASC"],
            [
                "VOTE_ID" => $votingId,
                "ACTIVE" => "Y",
                "@QUESTION_ID" => array_keys($arQuestions)
            ]
        );

        while ($res = $db_res->GetNext()) {
            $arQuestions[$res["QUESTION_ID"]]["ANSWERS"][$res["ID"]] = $res;
        }

        $questions = [];
        foreach ($arQuestions as $question) {
            if ($question === null) {
                continue;
            }

            $questions[] = $this->reformatQuestion($question);
        }

        $this->user->Logout();

        return $questions;
    }

    private function reformatQuestion(array $question): array
    {
        return [
            'id' => (int)$question['ID'],
            'title' => $this->clearText($question['~QUESTION']),
            'is_required' => $question['REQUIRED'] === 'Y',
            'answers' => $this->reformatAnswers($question['ANSWERS']),
        ];
    }

    private function reformatAnswers(array $answers): array
    {
        $reformattedAnswers = [];

        foreach ($answers as $answer) {
            $reformattedAnswers[] = [
                'id' => (int)$answer['ID'],
                'message' => $this->clearText($answer['~MESSAGE']),
                'type' => self::$answerTypes[(int)$answer['FIELD_TYPE']],
                'params' => $answer['~FIELD_PARAM'],
            ];
        }

        return $reformattedAnswers;
    }

}
