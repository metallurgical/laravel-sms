<?php

namespace App\Services\Sms\Drivers;

use App\Services\Sms\Contracts\DriverContract;
use App\Services\Sms\Sms;

class TwilioProvider extends Sms implements DriverContract
{
    /**
     * Account SID used in twilio.
     *
     * @var string
     */
    protected $accountSid;

    /**
     * React as a password.
     *
     * @var string
     */
    protected $autToken;

    /**
     * Provider's external URL.
     *
     * @var string
     */
    protected $url = 'https://api.twilio.com/2010-04-01/Accounts';

    /**
     * TwilioProvider constructor.
     *
     * @param string $accountSid
     * @param string $authToken
     */
    public function __construct(string $accountSid, string $authToken, bool $enableSwitching)
    {
        $this->accountSid = $accountSid;
        $this->autToken = $authToken;
        $this->enableSwitching = $enableSwitching;

        $this
            ->setBaseURL()
            ->initHTTPClient()
            ->setHeaders();
    }

    /**
     * Set twilio based URL.
     *
     * @return $this
     */
    public function setBaseURL()
    {
        $this->baseURL = sprintf('%s/%s/Messages.json', $this->url, $this->accountSid);

        return $this;
    }

    /**
     * Pass default headers for twilio.
     *
     * @param array $headers
     * @return $this
     */
    public function setHeaders(array $headers = [])
    {
        $this->headers = array_merge([
            'auth' => [$this->accountSid, $this->autToken]
        ], $headers);

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
                'To' => $this->addPlusPrefix($to[0]),
                'From' => $this->addPlusPrefix(config('services.twilio.from')),
                'Body' => $message,
            ]
        ];

        $this->setAdditionalHeaders($data);

        return $this;
    }
}