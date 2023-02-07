<?php

namespace Vyatsu\API\Services\Votings;

use Bitrix\Vote\EventTable;
use Bitrix\Vote\EventQuestionTable;
use Bitrix\Vote\EventAnswerTable;
use Bitrix\Vote\VoteTable;
use Bitrix\Vote\QuestionTable;
use Bitrix\Vote\AnswerTable;
use Bitrix\Vote\Vote;
use Bitrix\Vote\User;
use Vyatsu\API\Exception\APIException;

//require_once $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/prolog_before.php';


class VotingService
{
    use \Vyatsu\API\Utils\VotingUtils;

    private int $userId;
    private \CUser $user;

    /**
     * @throws APIException
     */
    public function __construct(int $userId)
    {
        global $USER;
        if (!$userId) {
            throw new APIException('UserId not provided!', 400, ['data' => 'No UserId provided']);
        }

        $this->userId = $userId;
        $this->user = $USER;
        $this->user->Authorize($this->userId);
    }

    public function getVote(int $voteId)
    {
        $voteListRaw = $this->getVoteList();

        while ($arVote = $voteListRaw->Fetch()) {
            if ($arVote['ID'] != $voteId) {
                continue;
            }

            $ans = $this->reformatVoting($arVote);

//	        $user = new \CUser();
            $this->user->Logout();

            return $ans;
        }

        return [];
    }

    private function getVoteList()
    {
        $this->includeModuleOrFail();

        return GetVoteList();
    }

    public function reformatVoting(array $votingRaw): array
    {
        return [
            'id' => (int)$votingRaw['ID'],
            'name' => $this->clearText($votingRaw['TITLE']),
            'description' => $this->clearText($votingRaw['DESCRIPTION']),
            'created_at' => strtotime($votingRaw['TIMESTAMP_X']),
            'has_voted' => (bool)$this->hasVoted((int)$votingRaw['ID']),
            'user_id' => $this->userId,
//            'can_vote' => $this->canVote((int)$votingRaw['ID']),
//            'is_suc' => $this->canVote((int)$votingRaw['ID'])->isSuccess(),
//            'active' => [
//                'from' => strtotime($votingRaw['DATE_START']),
//                'until' => strtotime($votingRaw['DATE_END']),
//            ],
        ];
    }

    public function hasVoted(int $voteId)
    {
        $vote = Vote::loadFromId($voteId);
        return $vote->isVotedFor($this->userId);
    }

    public function getVotingList(): array
    {
        //Построение списка актиных опросов для группы
        $votings = [];
        $voteListRaw = $this->getVoteList();
        $now = strtotime('now');

        while ($arVote = $voteListRaw->Fetch()) {
            if ($arVote["LAMP"] != "green" || $arVote['KEEP_IP_SEC']) {
                continue;
            }
            if ($arVote['DATE_START'] && strtotime($arVote['DATE_START']) > $now
                || $arVote['DATE_END'] && strtotime($arVote['DATE_END']) < $now
            ) {
                continue;
            }

            $votings[] = $this->reformatVoting($arVote);
        }

        $this->user->Logout();

        return $votings;
    }

    public function vote(array $votingData)
    {
        try {
            $event = new EventService($votingData, $this->userId);

            $res = $event->vote();

            $this->user->Logout();

            return $res;
        } catch (\RuntimeException $re) {
            $this->throwException($re->getMessage());
        }
    }

    private function throwException(string $message)
    {
        $this->user->Logout();

        throw new APIException($message);
    }

    public function canVote(int $voteId)
    {
        $vote = Vote::loadFromId($voteId);
        return $vote->canVote($this->userId);
    }

}
