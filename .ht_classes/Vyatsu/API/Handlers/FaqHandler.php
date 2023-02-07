<?php

namespace Vyatsu\API\Handlers;

use Vyatsu\API\App\App;
use \Vyatsu\API\Services\FaqService;
use \Vyatsu\API\Exception\APIException;

class FaqHandler extends Handler
{
    private FaqService $service;

    public function __construct()
    {
        $this->service = new FaqService();

        parent::__construct();
    }

    protected function get(): void
    {
        $url = trim($this->request['url']);

        try {
            $res = $this->service->read(0, $url);

            $this->response->code()->json($res);
        } catch (APIException $exception) {
            $this->response->error($exception);
        }
    }
}
