<?php

namespace Vyatsu\API\Handlers\Votings;

use Vyatsu\API\App\App;
use Vyatsu\API\Exception\APIException;
use Vyatsu\API\Exception\RequestException;
use Vyatsu\API\Services\Votings\QuestionService;
use Vyatsu\API\Services\Votings\VotingService;

class VotingsHandler extends \Vyatsu\API\Handlers\Handler
{
    public function __construct()
    {
        parent::__construct();
    }

    protected function hasVoted(): void
    {
        if (!$user = App::getUser()) {
            return;
        }

        if (!$this->checkVoteId()) {
            return;
        }

        try {
            $vs = new VotingService($user->getLoggedAs()->getId());

            $this->response
                ->code()
                ->json([
                    'has_voted' => (bool)$vs->canVote($this->request['vote_id']),
                    'vote_id' => $this->request['vote_id']
                ]);
        } catch (APIException $exception) {
            $this->response->error($exception);
        }
    }

    protected function list()
    {
        if (!$user = App::getUser()) {
            return;
        }

        try {
            $vs = new VotingService($user->getLoggedAs()->getId());

            if (!$this->request['vote_id']) {
                $this->response->code()->json($vs->getVotingList());
                return;
            }

            if (!$this->checkVoteId()) {
                return;
            }

            $voting = $vs->getVote($this->request['vote_id']);

            if (!$voting) {
                $this->response->error(
                    new APIException(
                        'Voting not found',
                        404,
                        ['vote_id' => 'No voting exists with this id']
                    )
                );

                return;
            }

            $this->response->code()->json($voting);
        } catch (APIException $exception) {
            $this->response->error($exception);
        }
    }

    protected function questions()
    {
        if (!$user = App::getUser()) {
            return;
        }

        if (!$this->checkVoteId()) {
            return;
        }

        try {
            $qs = new QuestionService($user->getLoggedAs()->getId());

            $this->response->code()->json($qs->getQuestions($this->request['vote_id']));
        } catch (APIException $exception) {
            $this->response->error($exception);
        }
    }

    protected function vote()
    {
        if (!$user = App::getUser()) {
            return;
        }

        if (!$this->checkVoteId()) {
            return;
        }

        try {
            $vs = new VotingService($user->getId());

            $voted = $vs->vote($this->request);

            $this->response
                ->code()
                ->json([
                    'voted' => $voted,
                    'vote_id' => $this->request['vote_id']
                ]);
        } catch (RequestException $requestException) {
            $this->response->error($requestException);
        } catch (APIException $exception) {
            $this->response->error($exception);
        }
    }

    private function checkVoteId(): bool
    {
        if (!$this->request['vote_id']) {
            $this->requestError(
                'Vote id ("vote_id") is not provided',
                ['vote_id' => "Vote id in not provided '{$this->request['vote_id']}'"]
            );
            return false;
        }

        if ($this->request['vote_id'] < 0) {
            $this->requestError(
                'Vote id ("vote_id") is unacceptable',
                ['vote_id' => "Vote id must be bigger than zero"]
            );
            return false;
        }

        return true;
    }
}
