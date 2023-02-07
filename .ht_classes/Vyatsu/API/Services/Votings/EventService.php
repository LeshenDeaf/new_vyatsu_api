<?php

namespace Vyatsu\API\Services\Votings;

use Bitrix\Vote\EventTable;
use Bitrix\Vote\EventQuestionTable;
use Bitrix\Vote\EventAnswerTable;
use Bitrix\Vote\VoteTable;
use Bitrix\Vote\QuestionTable;
use Bitrix\Vote\AnswerTable;
use Bitrix\Vote\UserTable;
use Vyatsu\API\Exception\APIException;
use Vyatsu\API\Exception\RequestException;

class EventService
{
	private array $votingData;
	private int $voteId;
	private int $userId;


	public function __construct(array $votingData, int $userId)
	{
		$this->votingData = $votingData;
		$this->voteId = $votingData['id'];

		$this->userId = $userId;
	}

	public function vote(): array
    {
		$eventId = $this->generateEventId();

		$ids = [];
		$answerIdsForCounter = [];

		foreach ($this->votingData['questions'] as $question) {
			$questionId = $question['id'];

			if (!$question['answers']) {
				continue;
			}

			$eventQId = $this->generateEventQId($eventId, $questionId);

			$ids[$questionId] = [
				"EVENT_ID" => $eventQId,
				"ANSWERS" => []
			];

			foreach ($question['answers'] as $answer) {
				if (!$answer['answer'] && !$answer['is_select']) {
					continue;
				}

				$answerMessage =
					$answer['is_select'] && !$answer['answer']
					? mb_substr($answer['message'], 0, 1024)
					: mb_substr($answer['answer'], 0, 1024);

				$eventAId = $this->generateEventAId($eventQId, $answer['id'], $answerMessage);

				$ids[$questionId]["ANSWERS"][$answer['id']] = [
					"EVENT_ID" => $eventAId,
					"EVENT_QUESTION_ID" => $eventQId,
					"ANSWER_ID" => $answer['id'],
					"MESSAGE" => $answerMessage
				];

				$answerIdsForCounter[] = $answer['id'];
			}

			if (empty($ids[$questionId]['ANSWERS'])
				|| empty($ids[$questionId])
			) {
				EventQuestionTable::delete($eventQId);
				unset($ids[$questionId]);
			}
		}

		if (empty($ids)) {
			EventTable::delete($eventId);

			throw new RequestException(
                'No answers were provided',
                $this->votingData,
                ['answers' => 'No answers provided']
            );
		}

		$this->setCounter($ids, $answerIdsForCounter);

		return ['added' => !empty($ids)];
	}

	private function generateEventId()
	{
		$eventFields = [
			"VOTE_ID"			=> $this->voteId,
			"VOTE_USER_ID"		=> $this->addUser(),
			"DATE_VOTE"			=> new \Bitrix\Main\Type\DateTime(),
			"STAT_SESSION_ID"	=> intval($_COOKIE["BITRIX_SM_GUEST_ID"]),
			"IP"				=> $_SERVER['REMOTE_ADDR'],
			"VALID"				=> "Y",
			"VISIBLE" 			=> "Y",
		];

		$eventId = EventTable::add($eventFields)->getId();

		if (!$eventId) {
			throw new \RuntimeException('Unable to register submitted data');
		}

		return $eventId;
	}

	private function addUser()
	{
		$fields = [
			"STAT_GUEST_ID"	=> intval($_COOKIE["BITRIX_SM_GUEST_ID"]),
			"DATE_LAST"		=> new \Bitrix\Main\Type\DateTime(),
			"LAST_IP"		=> $_SERVER["REMOTE_ADDR"],
			"AUTH_USER_ID"	=> $this->userId,
			"DATE_FIRST"	=> new \Bitrix\Main\Type\DateTime(),
			"COUNTER"       => 1,
		];

		$connection = \Bitrix\Main\Application::getInstance()->getConnection();
		$insert = $connection
			->getSqlHelper()
			->prepareInsert(
				UserTable::getTableName(),
				$fields
			);

		$connection->queryExecute(
			"INSERT INTO " . UserTable::getTableName() . "(`COOKIE_ID`, " . $insert[0] . ") "
			. "SELECT MAX(`COOKIE_ID`) + 1, " . $insert[1]
			. " FROM " . UserTable::getTableName()
		);

		$dbRes = new \Bitrix\Main\ORM\Data\AddResult();
		$dbRes->setId($connection->getInsertedId());
		$dbRes->setData(UserTable::getById($dbRes->getId())->fetch());

		return $dbRes->getId();
	}

	private function generateEventQId(int $eventId, int $questionId)
	{
		$eventQId = EventQuestionTable::add(
			["EVENT_ID" => $eventId, "QUESTION_ID" => $questionId]
		)->getId();

		if (!$eventQId || $eventQId <= 0) {
            throw new APIException(
                'Unable to create event question ' . $eventQId, 500, []
            );
		}

		return $eventQId;
	}

	private function generateEventAId(int $eventQId, int $answerId, string $answerMessage)
	{
		$eventAId = EventAnswerTable::add(
			[
				"EVENT_QUESTION_ID" => $eventQId,
				"ANSWER_ID" => $answerId,
				"MESSAGE" => $answerMessage
			]
		)->getId();

		if (!$eventAId || $eventAId <= 0) {
			throw new APIException(
				'Unable to create event answer ' . $answerId, 500, []
			);
		}

		return $eventAId;
	}

	private function setCounter($ids, $answerIdsForCounter)
	{
		VoteTable::setCounter([$this->voteId], true);
		QuestionTable::setCounter(array_keys($ids), true);
		AnswerTable::setCounter($answerIdsForCounter, true);
	}

}
