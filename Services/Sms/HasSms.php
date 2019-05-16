<?php

namespace App\Services\Sms;

use App\Services\Sms\Contracts\SmsContract;
use InvalidArgumentException;

trait HasSms
{
    /**
     * Recipients's phone number.
     *
     * @var array
     */
    public $to = [];

    /**
     * From number.
     *
     * @var string
     */
    public $from = '';

    /**
     * Message string send to recipient.
     *
     * @var string
     */
    public $message = '';

    /**
     * Store template ID. **yunpian** provider only.
     *
     * @var string
     */
    public $template_id = '';

    /**
     * Key value array matched with template ID. **yunpian** provider only
     *
     * @var string
     */
    public $template_value = '';

    /**
     * Message's signature as required by MCMC(Malaysia)
     *
     * @var string
     */
    public $signature = '';

    /**
     * Switch provider if one fail to sending SMS.
     *
     * @var bool
     */
    public $switchProvider = false;

    /**
     * Done executed providers.
     *
     * @var array
     */
    private $queuedProviders = [];

    /**
     * Should continue switching provider if failed. No more switching if out of provider's left.
     *
     * @var bool
     */
    private $shouldContinue = true;

    /**
     * Current iterate provider after switch to another.
     *
     * @var string
     */
    protected $currentIterateProvider = '';

    /**
     * Current driver being iterated.
     *
     * @var
     */
    private $currentDriver;

    /**
     * Current provider returned.
     *
     * @var $this
     */
    public $instance;

    /**
     * Populate recipient's phone numbers.
     *
     * @param mixed ...$phones
     * @return $this
     */
    public function to(...$phones)
    {
        if (empty($phones)) {
            throw new InvalidArgumentException('Phone number is required.');
        }

        if (count($phones[0]) > 1) {
            $this->to = $phones[0];
        } else {
            $this->to = $phones;
        }

        return $this;
    }

    /**
     * Populate recipient's message.
     *
     * @param string $message
     * @return $this
     */
    public function message(string $message)
    {
        if (empty($message)) {
            throw new InvalidArgumentException('Message body is required.');
        }

        $this->message = $message;

        return $this;
    }

    /**
     * Set message's signature in front of message's text.
     *
     * @param string $signature
     * @return $this
     */
    public function signature(string $signature)
    {
        if (empty($signature)) {
            throw new InvalidArgumentException('Message signature is required.');
        }

        $this->signature = $signature;

        return $this;
    }

    /**
     * Set message's template ID that need to be used to replace message.
     *
     * @param string $templateId
     * @return $this
     */
    public function template_id(string $templateId)
    {
        if (empty($templateId)) {
            throw new InvalidArgumentException('Template ID is required.');
        }

        $this->template_id = $templateId;

        return $this;
    }

    /**
     * Set message's template value that need to be used to inside template.
     *
     * @param string $templateId
     * @return $this
     */
    public function template_value(array $templateValues)
    {
        if (empty($templateValues)) {
            throw new InvalidArgumentException('Template value is required.');
        }

        $str = '';

        foreach ($templateValues as $key => $templateValue) {
            if (substr($key, 0, 1) !== '#') {
                $key = '#' . $key;
            }
            if (substr($key, -1, 1) !== '#') {
                $key = $key . '#';
            }
            if ($templateValue == '') {
                $templateValue = sprintf("%s%s", "\"", "\"");
            }
            $str .= sprintf('%s=%s&', $key, $templateValue);
        }

        $this->template_value = substr($str, 0, -1);

        return $this;
    }

    /**
     * Call the implemented handle method.
     *
     * @param SmsContract $implementedClass
     * @return mixed
     */
    public function handler(SmsContract $implementedClass)
    {
        call_user_func([$implementedClass, 'handle']);

        if ($implementedClass->signature !== '') {
            $implementedClass->message = sprintf('%s %s', $implementedClass->signature, $implementedClass->message);
        }

        $additionalFormData = [];

        if ($implementedClass->template_id !== '' && $implementedClass->template_value !== '') {
            $additionalFormData['tpl_id'] = $implementedClass->template_id;
            $additionalFormData['tpl_value'] = $implementedClass->template_value;
        }

        $this->instance->setFormData($implementedClass->to, $implementedClass->message, $additionalFormData);

        $this->instance->commit($implementedClass, $this->instance);

        return $this->getResponse();
    }

    /**
     * In case to get the raw response from provider.
     *
     * @return mixed
     */
    public function getResponse()
    {
        return $this->instance->getRawResponse();
    }

    /**
     * In case to the data from response(if success).
     *
     * @return mixed
     */
    public function getData()
    {
        return $this->instance->data;
    }

    /**
     * Set default driver being used.
     *
     * @return string
     */
    public function driver()
    {
        return 'nexmo';
    }

    /**
     * Returned HTTP response body along with exception.
     *
     * @param $exception
     * @param $HTTPResponseBody
     */
    public function error($exception, $HTTPResponseBody)
    {
        // do nothing, user might implement this method to get the exception and httpresponse body
    }

    /**
     * Returned HTTP response body along with successful response.
     *
     * @param $response
     * @param $HTTPResponseBody
     */
    public function success($response, $HTTPResponseBody)
    {
        // do nothing, user might implement this method to get the exception and httpresponse body
    }
}