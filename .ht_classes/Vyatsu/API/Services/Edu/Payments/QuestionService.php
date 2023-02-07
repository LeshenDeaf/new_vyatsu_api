<?php


namespace Vyatsu\API\Services\Edu\Payments;

//use Vyatsu\API\JWT\JWTAuth;
use PHPUnit\Util\Type;
use Vyatsu\API\Exception\APIException;
use Vyatsu\API\Services\Service;
use Vyatsu\API\Utils\Utils;

class QuestionService extends Service
{
    public function read(int $id = 0, int $userId = 0): array
    {
        $res = $this->getRes(0, ['CREATED_BY' => $userId]);

//        $arSelect = [
//            'IBLOCK_ID', 'ACTIVE','ID',
//            'created_date', 'PREVIEW_TEXT', 'CREATED_BY',
//            'PROPERTY_ANSWER'
//        ];

        $result = [];
        while ($ob = $res->GetNextElement()) {
            $arFields = $ob->GetFields();

            $result[$arFields["ID"]] = [
                'text' => $arFields['PREVIEW_TEXT'],
                'answer' => $arFields['~PROPERTY_ANSWER_VALUE']['TEXT'],
                'created_date' => \DateTime::createFromFormat('Y.m.d', $arFields['CREATED_DATE'])->format('d.m.Y'),
            ];
        }

        return $result;
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

    private function makePaymentSum($payments, bool $ignoreDates = false)
    {
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
