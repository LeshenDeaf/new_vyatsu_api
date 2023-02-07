<?php

namespace Vyatsu\API\Utils;

class Utils
{
    public static function getUserId(string $login)
    {
        return (int)\CUser::GetByLogin($login)->Fetch()['ID'];
    }

    public static function getToday(): int
    {
        return mktime(0, 0, 0, date("m"), date("d"), date("Y"));
    }

    public static function toCamelCase($str): string
    {
        return implode('', array_map(
            fn($element, $index) => implode('', array_map(
                fn($el, $i) => $index === 0 && $i === 0
                    ? $el
                    : ucfirst($el),
                explode('_', $element),
                array_keys(explode('_', $element))
            )),
            explode('-', $str),
            array_keys(explode('-', $str)),
        ));
    }

    public static function getAge(string $date): int
    {
        if (!$date) {
            return 0;
        }

        $birthDate = explode('-', explode('T', $date)[0]);

        return (date("md", date("U", mktime(0, 0, 0, $birthDate[1], $birthDate[2], $birthDate[0]))) > date("md")
            ? ((date("Y") - $birthDate[0]) - 1)
            : (date("Y") - $birthDate[0]));
    }

    public static function getEmployeeAge(string $date): int
    {
        if (!$date) {
            return 0;
        }

        $date = explode('.', $date);

        return (date("md", date("U", mktime(0, 0, 0, $date[1], $date[0], $date[2]))) > date("md")
            ? ((date("Y") - $date[2]) - 1)
            : (date("Y") - $date[2]));
    }
}
