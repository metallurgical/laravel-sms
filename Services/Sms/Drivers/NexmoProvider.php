<?php

namespace App\Services\Sms\Drivers;

use App\Services\Sms\Contracts\DriverContract;
use App\Services\Sms\Sms;

class NexmoProvider extends Sms implements DriverContract
{
    /**
     * Api Key of Nexmo.
     *
     * @var string
     */
    protected $apiKey;

    /**
     * Api Secret of Nexmo.
     *
     * @var string
     */
    protected $apiSecret;

    /**
     * Provider's external URL.
     *
     * @var string
     */
    protected $url = 'https://rest.nexmo.com/sms/json';

    /**
     * NexmoProvider constructor.
     *
     * @param string $apiKey
     * @param string $apiSecret
     */
    public function __construct(string $apiKey, string $apiSecret, bool $enableSwitching)
    {
        $this->apiKey = $apiKey;
        $this->apiSecret = $apiSecret;
        $this->enableSwitching = $enableSwitching;

        $this
            ->setBaseURL()
            ->initHTTPClient()
            ->setHeaders();
    }

    /**
     * Set nexmo based URL.
     *
     * @return $this
     */
    public function setBaseURL()
    {
        $this->baseURL = $this->url;

        return $this;
    }

    /**
     * Pass default headers for nexmo.
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
    public function setFormData(array $to, string $message, array $additionalFormData = [])
    {
        $data = [
            'form_params' => [
                'api_key' => $this->apiKey,
                'api_secret' => $this->apiSecret,
                'from' => config('services.nexmo.from'),
                'to' => $to[0],
                'text' => $message,
            ]
        ];

        $this->setAdditionalHeaders($data);

        return $this;
    }
}