<?php

namespace App\Services\Sms\Events;

use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Collection;
use GuzzleHttp\Psr7\Response;

class SmsSent
{
    use SerializesModels;

    public $sms;

    public $response;

    public $HTTPResponseBody;

    public function __construct(Collection $sms, Response $response, $HTTPResponseBody)
    {
        $this->sms = $sms;
        $this->response = $response;
        $this->HTTPResponseBody = $HTTPResponseBody;
    }
}
