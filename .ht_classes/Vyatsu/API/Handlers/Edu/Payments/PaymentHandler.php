<?php

namespace Vyatsu\API\Handlers\Edu\Payments;

use Vyatsu\API\App\App;
use Vyatsu\API\Exception\APIException;
use Vyatsu\API\Handlers\Handler;
use Vyatsu\API\Services\Edu\Payments\PaymentService;
use Vyatsu\API\Services\Edu\Payments\TypesService;
use Vyatsu\API\Services\Edu\Payments\QuestionService;

class PaymentHandler extends Handler
{
    private PaymentService $service;
    private TypesService $typesService;
    private QuestionService $questionService;

    public function __construct()
    {
        parent::__construct();

        $this->service = new PaymentService(0, []);
        $this->typesService = new TypesService(0, []);
    }

    protected function graph(): void
    {
        if (!$user = App::getUser()) {
            return;
        }

        if (trim(!$this->request['payment_type'])) {
            $this->requestError(
                'No "payment_type" in request',
                ['payment_type' => 'Property is undefined']
            );
            return;
        }

        try {
            $res = $this->service->read(0, $user->getLoggedAs()->getLogin(), trim($this->request['payment_type']));
            $this->response->code()->json($res);
        } catch (APIException $APIException) {
            $this->response->error($APIException);
        }
    }

    protected function types(): void
    {
        if (!$user = App::getUser()) {
            return;
        }

        try {
            $res = $this->typesService->read(0, $user->getLoggedAs()->getLogin());

            $this->response->code()->json($res);
        } catch (APIException $APIException) {
            $this->response->error($APIException);
        }
    }

    protected function questions(): void
    {

    }
}
