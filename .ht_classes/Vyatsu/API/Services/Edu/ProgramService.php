<?php

namespace Vyatsu\API\Services\Edu;

use Vyatsu\API\JWT\JWTAuth;
use Vyatsu\API\Services\Service;

class ProgramService extends Service
{
    private const DB_SERVER = "10.0.1.3";
    private const DB_LOGIN = "info_service";
    private const DB_PASSWD = "byajhvfwbz";
    private const DB_DBASE = "www_vuz";

    private array $numType = [
        1 => 'universal',
        2 => 'common',
        3 => 'prof',
        4 => 'other',
    ];

    private array $typeName = [
        'universal' => 'Дисциплины, формирующие общие(универсальные) компетенции',
        'common' => 'Дисциплины, формирующие общепрофессиональные компетенции',
        'prof' => 'Дисциплины, формирующие профессиональные компетенции',
        'other' => 'Дисциплины, формирующие специальные профессиональные компетенции',
    ];

    public function __construct()
    {
        parent::__construct(161, [
            'direction_code', 'year', 'education_plan', 'programm_id',
        ], [], true);
    }

    /**
     * @throws \Exception
     */
    public function read(int $id = 0, $profileIdDo = ''): array
    {
        if (!($conn = $this->connect())) {
            throw new \Exception(json_encode(sqlsrv_errors()));
        }

        $query = "
            EXEC
                proc_OOP_detail_priem_on_site
                @profile_id=?
        ";
        $params = [formatGET(addslashes($profileIdDo))];
        $stmp = sqlsrv_query($conn, $query, $params);

        $res = [];
        while ($row = sqlsrv_fetch_array($stmp, SQLSRV_FETCH_ASSOC)) {
            $res[] = $row;
        }

        sqlsrv_close($conn);

        $program = [];
        $arKursCount = [];
        $arColorsHere = [];

        foreach ($res as $el) {
            for ($iKurs = 1; $iKurs <= 6; $iKurs++) {
                // какой курс
                $kurs = '';
                $zet = '';
                $cp = '';
                if (!empty($el['kurs' . $iKurs . '_zet'])) {
                    $kurs = $iKurs;
                    $zet = trim($el['kurs' . $iKurs . '_zet']);
                }
                if (!empty($el['kurs' . $iKurs . '_cp'])) {
                    $kurs = $iKurs;
                    $cp = trim($el['kurs' . $iKurs . '_cp']);
                }

                if (empty($zet) && empty($cp)) {
                    continue;
                }

                $arKursCount[$kurs] = true;

                $program[$kurs] ??= [
                    'course' => $kurs,
                    'disciplines' => [],
                ];

                $program[$kurs]['disciplines'][] = [
                    'zet' => $zet,
                    'cp' => $cp,
                    'color' => $this->numType[$el['comp_type_id']],
                    'subject_id' => $el['subject_id'],
                    'op_id' => $el['status_id'] == 3 ? $el['op_id'] : '',
                    'name' => $el['subjectName'],
                ];

                $arColorsHere[$this->numType[$el['comp_type_id']]] = true;
            }
        }

        return [
            'program' => array_values($program),
            'colors' => [
                'used' => array_keys($arColorsHere),
                'descriptions' => $this->typeName,
            ],
            'courses' => count($arKursCount),
        ];
    }

    public function create(array $data): int
    {
        // TODO: Implement create() method.
        return 1;
    }

    public function update(array $data): array
    {
        // TODO: Implement update() method.
        return [];
    }

    public function delete(array $ids): bool
    {
        // TODO: Implement delete() method.
        return false;
    }

    private function connect()
    {
        $connectionInfo = [
            'Database' => static::DB_DBASE,
            'UID' => static::DB_LOGIN,
            'PWD' => static::DB_PASSWD,
            'LoginTimeout' => 3,
            'CharacterSet' => 'UTF-8',
            'ReturnDatesAsStrings' => true,
        ];
        return sqlsrv_connect(static::DB_SERVER, $connectionInfo);
    }
}
