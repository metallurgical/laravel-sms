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
    protected $url = 'https://yunpian.com/v2/sms/tpl_single_send.json';

    /**
     * NexmoProvider constructor.
     *
     * @param string $apiKey
     * @param string $apiSecret
     */
    public function __construct(string $apiKey, bool $enableSwitching)
    {
        $this->apiKey = $apiKey;
        $this->enableSwitching = $enableSwitching;

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
     * @param array $additionalFormData
     * @return $this|mixed
     */
    public function setFormData(array $to, string $message, array $additionalFormData = [])
    {
        $data = [
            'form_params' => [
                'apikey' => $this->apiKey,
                'uid' => config('services.yunpian.from'),
                'mobile' => $this->addPlusPrefix($to[0]),
            ]
        ];

        if (!empty($additionalFormData)) {
            $data['form_params'] = array_merge($data['form_params'], $additionalFormData);
        }

        $this->setAdditionalHeaders($data);

        return $this;
    }
}