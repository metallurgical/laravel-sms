<?php

namespace App\Services\Sms\Drivers;

use App\Services\Sms\Contracts\DriverContract;
use App\Services\Sms\Sms;

class ISmsProvider extends Sms implements DriverContract
{

    /**
     * Send ID of Isms.
     *
     * @var string
     */
    protected $send_id;

    /**
     * Username of Isms.
     *
     * @var string
     */
    protected $username;

    /**
     * Password of Isms.
     *
     * @var string
     */
    protected $password;

    /**
     * Provider's external URL.
     *
     * @var string
     */
    protected $url = 'http://www.isms.com.my/isms_send.php';

    /**
     * NexmoProvider constructor.
     *
     * @param string $username
     * @param string $password
     * @param string $send_id
     */
    public function __construct(string $username, string $password, string $send_id, bool $enableSwitching)
    {
        $this->username = $username;
        $this->password = $password;
        $this->send_id = $send_id;
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
                'un'   => $this->username,
                'pwd'  => $this->password,
//                1 - ASCII (English, Bahasa Melayu, etc) 153 characters
//                2 - Unicode (Chinese, Japanese, etc) 63 Characters
                'type' => 1,
//                Mobile number that you wish to send a message
//                (Append 00 for international numbers).
                'dstno'   => $to[0],
                'msg' => rawurlencode($message),
                'sendid' => $this->send_id,
                'agreedterm' => 'YES',
            ]
        ];

        $this->setAdditionalHeaders($data);

        return $this;
    }
}