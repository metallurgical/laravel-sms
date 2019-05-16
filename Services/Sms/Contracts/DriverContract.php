<?php

namespace App\Services\Sms\Contracts;

interface DriverContract
{
    /**
     * Set base url for each defined driver.
     *
     * @return mixed
     */
    public function setBaseUrl();

    /**
     * Set headers to send along with the HTTP request.
     *
     * @param array $headers
     * @return mixed
     */
    public function setHeaders(array $headers = []);

    /**
     * Set Form Data that need to send along with the HTTP request.
     *
     * @param array $to
     * @param string $message
     * @param array $additionalFormData
     * @return mixed
     */
    public function setFormData(array $to, string $message, array $additionalFormData = []);
}