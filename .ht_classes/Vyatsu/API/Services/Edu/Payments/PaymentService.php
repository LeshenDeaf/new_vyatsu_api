<?php


namespace Vyatsu\API\Services\Edu\Payments;

//use Vyatsu\API\JWT\JWTAuth;
use PHPUnit\Util\Type;
use Vyatsu\API\Exception\APIException;
use Vyatsu\API\Services\Service;
use Vyatsu\API\Utils\Utils;

class PaymentService extends Service
{
    public function read(int $id = 0, string $login = '', string $type = ''): array
    {
        $results = \curl_get_array(
            \ApiLinks::PROGRAMMER_API . "/public/api/moneygraph_v2",
            [
                'student_id' => (int)preg_replace('/[^0-9]/', '', $login),
                'pay_type_name' => $type
            ],
            [],
            PHP_QUERY_RFC3986
        );

        if (!$results || $results['error']) {
            throw new APIException('No graph', 404, ['types' => "User have no graph of type $type"]);
        }

        foreach ($results as $index => $result) {
            $results[$index]['dept'] = $this->makePaymentSum($result);
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

    private function makePaymentSum($payments, bool $ignoreDates = false){
        $paymentSum = 0;
        $today = Utils::getToday();

        foreach ($payments['grafic_pay'] as $payment) {
            if (!$ignoreDates && strtotime($payment['DataPay']) > $today) {
                continue;
            }

            $paymentSum += $payment['Summa'];
        }

        foreach ($payments['fact_pay'] as $payment) {
            $paymentSum -= $payment['SummaSt'];
        }

        return $paymentSum;
    }
}
