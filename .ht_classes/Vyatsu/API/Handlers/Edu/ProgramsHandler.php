<?php

namespace Vyatsu\API\Handlers\Edu;

use Vyatsu\API\App\App;
use Vyatsu\API\Services\Edu\ProgramService;

class ProgramsHandler extends \Vyatsu\API\Handlers\Handler
{
    private ProgramService $service;

    public function __construct()
    {
        parent::__construct();
        $this->service = new ProgramService();
    }

    public function byUser()
    {
        if (!$user = App::getUser()) {
            return;
        }

        try {
            $program = $this->service->read(0, $user->getLoggedAs()->getUf()['UF_PROFILE_ID_DO']);

            $this->response->code()->json($program);
        } catch (\Exception $exception) {
            $this->response->code(500)->error($exception);
        }
    }

    private function readProgram(string $login)
    {
        $arSelect = [
            'ID', 'IBLOCK_ID', 'ACTIVE', 'NAME',
            'PROPERTY_DIRECTION_CODE', 'PROPERTY_YEAR', 'PROPERTY_EDUCATION_PLAN',
            'PROPERTY_PROGRAMM_ID',
        ];
        $arOrder  = ["sort" => "ASC", "ID" => "ASC"];
        $arFilter = [
            'IBLOCK_ID' => 161,
            'ACTIVE' => 'Y',
            'ACTIVE_DATE' => 'Y',
        ];
    }
}
