<?php

namespace Vyatsu\API\Services\Edu\Payments;

use Vyatsu\API\Exception\APIException;
use Vyatsu\API\Services\Service;

class TypesService extends Service
{
    public function read(int $id = 0, string $login = ''): array
    {
        $paymentService = new PaymentService(0, []);

        $results = \curl_get_array(
            \ApiLinks::PROGRAMMER_API . "/public/api/payment_types",
            [
                "student_id" => (int)preg_replace('/[^0-9]/', '', $login),
                "login" => $login,
            ], []
        );

        if (!$results || $results['error']) {
            throw new APIException('No types for user', 404, ['types' => 'User have no any type']);
        }

        foreach ($results as $index => $result) {
            $results[$index]['payment_type'] = $result['pay_type_name'];
            $results[$index]['dept'] = array_sum(array_column(
                $paymentService->read(0, $login, $result['pay_type_name']),
                'dept'
            ));
            unset($results[$index]['pay_type_name']);
        }

        return $results;
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
}
