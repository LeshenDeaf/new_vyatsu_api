<?php

namespace Vyatsu\API\Handlers\Edu;

use Vyatsu\API\App\App;
use Vyatsu\API\Exception\APIException;
use Vyatsu\API\Exception\JWTException;
use Vyatsu\API\Exception\RequestException;
use Vyatsu\API\JWT\JWTAuth;
use Vyatsu\API\JWT\RefreshToken;

require_once $_SERVER['DOCUMENT_ROOT']
    . '/account/obr/rasp/.ht_classes/RaspReader.php';
require_once $_SERVER['DOCUMENT_ROOT']
    . '/account/obr/rasp/.ht_classes/RaspReaderAPI.php';
require_once $_SERVER['DOCUMENT_ROOT']
    . '/account/obr/rasp/.ht_classes/StatusChanger.php';

class ScheduleHandler extends \Vyatsu\API\Handlers\Handler
{
    public function __construct()
    {
        parent::__construct();
    }

    protected function byTabnum(): void
    {
        $schedule = \RaspReaderAPI::readByFilter($this->request['tabnum'], '', '');
        if ($schedule['error']) {
            $this->requestError('No data provided', []);
            return;
        }
        $this->response->code()->json(
            static::reformatSchedule($schedule)
        );
    }

    protected function personal(): void
    {
        if (!$user = App::getUser()) {
            return;
        }

        if ($user->getLoggedAs()->getRights()->isIsStudent()) {
            $this->response->code()->json(
                static::reformatSchedule(\RaspReaderAPI::read(
                    $user->getLoggedAs()->getLogin()
                ))
            );
            return;
        }

        $this->response->code()->json(
            static::reformatSchedule(\RaspReaderAPI::readForTeacher(
                $user->getLoggedAs()->getLogin()
            ))
        );
    }

    protected function studentList(): void
    {
        if (!$user = App::getUser()) {
            return;
        }

        if (!$user->getLoggedAs()->getRights()->isIsEmployee()) {
            $this->response->error(new APIException(
                'You are not teacher',
                403,
                ['rights' => 'You must be logged as teacher to see student list']
            ));
            return;
        }

        if (!$this->checkTechInfo()) {
            return;
        }

        $this->response->code()->json(\RaspReaderAPI::ListStudNew($this->request));
    }

    private function checkTechInfo(): bool
    {
        if (!$this->request['profile_id']) {
            $this->requestError('profile_id is undefined', ['profile_id' => 'Property is undefined']);
            return false;
        }
        if (!$this->request['group_id']) {
            $this->requestError('group_id is undefined', ['group_id' => 'Property is undefined']);
            return false;
        }
        if (!$this->request['subject_id']) {
            $this->requestError('subject_id is undefined', ['subject_id' => 'Property is undefined']);
            return false;
        }
        if (!$this->request['pair_number']) {
            $this->requestError('pair_number is undefined', ['pair_number' => 'Property is undefined']);
            return false;
        }
        if (!$this->request['timestamp']) {
            $this->requestError('timestamp is undefined', ['timestamp' => 'Property is undefined']);
            return false;
        }

        return true;
    }

    public static function reformatSchedule(array $schedule): array
    {
        foreach ($schedule as $date => $pairGroups) {
            $dayOfWeek = array_values(array_values($pairGroups)[0])[0]['day_of_week'];

            foreach ($pairGroups as $number => $pairs) {
                $time = $pairs[0]['time'];

                foreach ($pairs as $index => $pair) {
                    unset($pair['time']);
                    unset($pair['day_of_week']);
                    unset($pair['date']);
                    unset($pair['number']);
                    $pair['tech_info'] = [
                        'profile_id' => $pair['profile_id'],
                        'group_id' => $pair['group_id'],
                        'subgroup_id' => $pair['subgroup_id'],
                        'subject_id' => $pair['subject_id'],
                        'timestamp' => $pair['timestamp'],
                        'pair_number' => $number,
                    ];

                    unset($pair['profile_id']);
                    unset($pair['group_id']);
                    unset($pair['subgroup_id']);
                    unset($pair['subject_id']);
                    unset($pair['timestamp']);

                    $schedule[$date][$number][$index] = $pair;
                }

                $schedule[$date][$number] = [
                    'number' => $number,
                    'time' => $time,
                    'pairs' => $schedule[$date][$number]
                ];
            }

            $schedule[$date] = [
                'date' => $date,
                'day_of_week' => $dayOfWeek,
                'pairs' => array_values($schedule[$date])
            ];
        }

        return array_values($schedule) ?? [];
    }

}
