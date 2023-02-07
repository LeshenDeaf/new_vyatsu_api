<?php

namespace Vyatsu\API\App\User;

use Vyatsu\API\Exception\APIException;
use Vyatsu\API\Utils\IArrayable;

class User extends UserMask
{
    private UserMask $loggedAs;

    public function __construct(\stdClass $decodedToken)
    {
        parent::__construct($decodedToken->user_id);
        $this->loggedAs = new UserMask((int)$decodedToken->logged_as);
    }

    public function getLoggedAs(): UserMask
    {
        return $this->loggedAs;
    }

    public function toArray(): array
    {
        return [
            'login' => $this->login,
            'id' => $this->id,
            'logged_as' => $this->loggedAs->toArray(),
            'groups' => $this->groups,
            'fio' => [
                'first_name' => $this->uf['NAME'],
                'last_name' => $this->uf['LAST_NAME'],
                'second_name' => trim(str_replace([$this->uf['NAME'], $this->uf['LAST_NAME']], ['', ''], $this->uf['SECOND_NAME'])),
                'full' => $this->uf['SECOND_NAME'],
            ],
            'rights' => $this->rights->toArray(),
        ];
    }

}
