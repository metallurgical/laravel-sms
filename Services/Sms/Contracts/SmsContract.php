<?php

namespace App\Services\Sms\Contracts;

interface SmsContract
{
    /**
     * Define recipient and message.
     *
     * @return mixed
     */
    public function handle();

    /**
     * Define driver in use.
     *
     * @return mixed
     */
    public function driver();

    /**
     * Returned HTTP response body along with exception.
     *
     * @param $exception
     * @param $HTTPResponseBody
     * @return mixed
     */
    public function error($exception, $HTTPResponseBody);

    /**
     * Returned HTTP response body along with response.
     *
     * @param $response
     * @param $HTTPResponseBody
     * @return mixed
     */
    public function success($response, $HTTPResponseBody);
}