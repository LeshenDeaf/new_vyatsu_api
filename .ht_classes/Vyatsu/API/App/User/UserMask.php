<?php

namespace Vyatsu\API\App\User;

use Vyatsu\API\Exception\APIException;
use Vyatsu\API\Utils\IArrayable;
use Vyatsu\API\Utils\Utils;

class UserMask implements IArrayable
{
    protected int $id;
    protected string $login;
    protected string $loginId;
    protected array $groups = [];

    protected Rights $rights;
    protected array $uf = [];
    protected array $api = [];

    public function __construct(int $userId)
    {
        $this->id = $userId;

        $this->groups = array_map('intval', \CUser::GetUserGroup($this->id));
        $this->rights = new Rights($this->groups);
        $this->uf = \CUser::GetByID($this->id)->Fetch();

        if (!$this->uf) {
            throw new APIException(
                'User not found',
                404, [
                    'authorization' => 'Provided user does not exist'
                ]
            );
        }

        $this->login = $this->uf['LOGIN'];
        $this->loginId = (int)preg_replace('/[^0-9]/', '', $this->login);
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getLogin(): string
    {
        return $this->login;
    }

    public function getGroups(): array
    {
        return $this->groups;
    }

    public function getRights(): Rights
    {
        return $this->rights;
    }

    public function getUf(): array
    {
        return $this->uf;
    }

    public function getApi(): array
    {
        if (!$this->api) {
            if ($this->rights->isIsStudent()) {
                $results = \curl_get_array(\ApiLinks::PROGRAMMER_API . "/public/api/studentinfo_v3/", [
                    'student_id' => $this->loginId,
                    'login' => $this->login,
                ]);

                $this->api = [
                    'fio' => $results['info_service']['fio'],

                    'last_name' => $results['info_service']['fam'],
                    'first_name' => $results['info_service']['nam'],
                    'middle_name' => $results['info_service']['otch'],
                    'age' => Utils::getAge($results['info_service']['birthdate'] ?? ''),

                    'group_name' => $results['info_service']['group_name'],
                    'faculty_short' => $results['info_service']['podr_code'],
                    'faculty_full'  => $results['info_service']['podr_name'],

                    'course'=> $results['info_service']['kurs'],
                    'is_last_course' => $results['portal']['prop_last_kurs'],

                    'email' => $results['info_service']['stud_email'],
                    'phone' => trim(explode(
                        "Телефон мобильный:",
                        $results['info_service']['str_phone']
                    )[1]),

                    'direction_code' => $results['info_service']['direction_code'],
                    'direction_name' => $results['info_service']['direction_name'],

                    'profile_name'  => $results['info_service']['profile_name'],
                    'edu_form'  => $results['portal']['form_ob_type_name'],
                    'form_ob'   => $results['portal']['form_ob_type_name'],
                    'level_name'    => $results['info_service']['level_name'],
                    'tech_name' => $results['portal']['form_ob_tech_name'],

                    'stud_type' => $results['portal']['stud_type'],
                    'is_pvz' => $results['portal']['stud_type'] === 'Полное возмещение затрат',

                    'contract' => $results['info_service']['dogovor'],

                    'is_in_hostel' => $results['portal']['obch_num'] > 0,

                    'citizenship' => $results['info_service']['citizen_name'],

                    'pasp' => [
                        'ser' => $results['info_service']['pasp_ser'],
                        'number' => $results['info_service']['pasp_num'],
                        'date' => $results['info_service']['pasp_date'],
                        'podr' => $results['info_service']['pasp_podr'],
                        'podr_code' => $results['info_service']['pasp_podr_code'],
                    ],
                ];
            } else {
                $results = \curl_get_array(\ApiLinks::PROGRAMMER_API . "/public/api/sotrudnikinfo_v1/", [
                    'login' => $this->login . '@vyatsu.ru',
                ]);

                $this->api = [
                    'tabnum' => $results['tabnum'],
                    'fio' => $results['fio'],
                    'fio_small' => $results['fio_small'],
                    'birthday' => $results['birthday'],
                    'pasp' => [
                        'ser' => $results['pasp_ser'],
                        'number' => $results['pasp_num'],
                    ],
                    'snils' => $results['snils'],
                    'inn' => $results['inn'],
                    'age' => Utils::getEmployeeAge($results['birthday']),
                ];
            }
        }

        return $this->api;
    }

    public function toArray(): array
    {
        return [
            'login' => $this->login,
            'id' => $this->id,
            'groups' => $this->groups,
            'fio' => [
                'first_name' => $this->getApi()['first_name'] ?? $this->uf['NAME'],
                'last_name' => $this->getApi()['last_name'] ?? $this->uf['LAST_NAME'],
                'second_name' => $this->getApi()['middle_name'] ?? trim(str_replace([$this->uf['NAME'], $this->uf['LAST_NAME']], ['', ''], $this->uf['SECOND_NAME'])),
                'full' => $this->getApi()['fio'] ?? $this->uf['SECOND_NAME'],
            ],
            'rights' => $this->rights->toArray(),
            'info' => $this->getApi(),
        ];
    }
}
