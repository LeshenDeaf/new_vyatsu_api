<?php

namespace Vyatsu\API\App\User;

use Vyatsu\API\Utils\IArrayable;

class Rights implements IArrayable
{
    private const ADMIN_GROUP = 1;
    private const EMPLOYEE_GROUP = 17;
    private const STUDENT_GROUPS = [15, 16];

    private bool $is_student = false;
    private bool $is_employee = false;
    private bool $is_admin = false;

    public function __construct(array $groups = [])
    {
        $this->is_student = !empty(array_intersect(Rights::STUDENT_GROUPS, $groups));
        $this->is_employee = in_array(Rights::EMPLOYEE_GROUP, $groups);
        $this->is_admin = in_array(Rights::ADMIN_GROUP, $groups);
    }

    public function toArray(): array
    {
        extract(get_object_vars($this));
        return compact('is_student', 'is_employee', 'is_admin');
    }

    public function isIsStudent(): bool
    {
        return $this->is_student;
    }

    public function isIsEmployee(): bool
    {
        return $this->is_employee;
    }

    public function isIsAdmin(): bool
    {
        return $this->is_admin;
    }
}
