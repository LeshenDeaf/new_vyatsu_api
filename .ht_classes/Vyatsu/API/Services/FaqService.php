<?php

namespace Vyatsu\API\Services;

use \Vyatsu\API\Exception\APIException;

class FaqService extends Service
{
    public function __construct()
    {
        parent::__construct(91, ['answer', 'url',], [], true);
    }

    public function read(int $id = 0, $url = ''): array
    {
        $res = $this->getRes($id, ['PROPERTY_URL' => $url]);

        if (!$res) {
            throw new APIException(
                'No faq found',
                404,
                [
                    'data' => 'No faq found'
                ]
            );
        }

        return $this->toArray($res);
    }

    public function create(array $data): int
    {
        return 1;
    }

    public function update(array $data): array
    {
        return [];
    }

    public function delete(array $ids): bool
    {
        return false;
    }
}
