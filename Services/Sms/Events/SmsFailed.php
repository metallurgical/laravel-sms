<?php

namespace App\Services\Sms\Events;

use Exception;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Collection;

class SmsFailed
{
    use SerializesModels;

    public $sms;

    public $exception;

    public $HTTPResponseBody;

    public function __construct(Collection $sms, Exception $e, $HTTPResponseBody)
    {
        $this->sms = $sms;
        $this->HTTPResponseBody = $HTTPResponseBody;
        $this->exception = $e;
    }
}