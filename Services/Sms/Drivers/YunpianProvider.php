<?php

namespace App\Services\Sms\Drivers;

use App\Services\Sms\Contracts\DriverContract;
use App\Services\Sms\Sms;

class YunpianProvider extends Sms implements DriverContract
{
    /**
     * Api Key of Yunpian.
     *
     * @var string
     */
    protected $apiKey;

    /**
     * Provider's external URL.
     *
     * @var string
     */
    protected $url = 'https://yunpian.com/v2/sms/single_send.json';

    /**
     * NexmoProvider constructor.
     *
     * @param string $apiKey
     * @param string $apiSecret
     */
    public function __construct(string $apiKey)
    {
        $this->apiKey = $apiKey;

        $this
            ->setBaseURL()
            ->initHTTPClient()
            ->setHeaders();
    }

    /**
     * Set yunpian based URL.
     *
     * @return $this
     */
    public function setBaseURL()
    {
        $this->baseURL = $this->url;

        return $this;
    }

    /**
     * Pass default headers for yunpian.
     *
     * @param array $headers
     * @return $this
     */
    public function setHeaders(array $headers = [])
    {
        return $this;
    }

    /**
     * Set form data that need to be send along with the headers.
     *
     * @param array $to
     * @param string $message
     * @return $this
     */
    public function setFormData(array $to, string $message)
    {
        $data = [
            'form_params' => [
                'apikey' => $this->apiKey,
                'uid' => config('services.yunpian.from'),
                'mobile' => $to[0],
                'text' => $message,
            ]
        ];

        $this->setAdditionalHeaders($data);

        return $this;
    }
}