<?php

namespace App\Services\Sms;

use App\Services\Sms\Contracts\DriverContract;
use App\Services\Sms\Contracts\SmsContract;
use App\Services\Sms\Events\SmsFailed;
use App\Services\Sms\Events\SmsSent;
use BadMethodCallException;
use GuzzleHttp;

class Sms
{
    use HasSms;

    /**
     * Guzzle Http Client Object.
     *
     * @var GuzzleHttp\Client
     */
    protected $client;

    /**
     * Base url for every providers.
     *
     * @var String
     */
    protected $baseURL = '';

    /**
     * Store response after make HTTP request.
     *
     * @var GuzzleHttp\Psr7\Response
     */
    protected $response;

    /**
     * Store array of headers.
     *
     * @var array
     */
    protected $headers = [];

    /**
     * Store form data to send along with headers.
     *
     * @var array
     */
    protected $data = [];

    /**
     * Underlying object that implement SmsContract.
     *
     * @var
     */
    protected $implementedClass;

    /**
     * @var array of providers
     */
    private $providers = [
        'twilio',
        'nexmo',
        'yunpian'
    ];

    /**
     * defaultProvider being used.
     *
     * @var string
     */
    protected $defaultProvider = 'nexmo';

    /**
     * Success status code.
     *
     * @var array
     */
    private $successCodes = [200, 201];

    /**
     * Abstract layer to utilize implemented SMS class.
     *
     * @param SmsContract $implementedClass
     * @param bool $switchProvider
     * @return mixed
     */
    public function send(SmsContract $implementedClass, bool $switchProvider = false)
    {
        if (!method_exists($implementedClass, 'handle')) {
            throw new BadMethodCallException("Method handle not found. Please implement \'handle\' method.");
        }

        // Assign raw object of implemented class to re-use later.
        $this->implementedClass = $implementedClass;

        $this->switchProvider = $switchProvider;

        return $this->initializeSmsProvider();
    }

    /**
     * Decide which provider need to use. Resolve with IoC container.
     *
     * @return mixed
     */
    private function initializeSmsProvider()
    {
        if (property_exists($this->implementedClass, 'driver')) {
            $driverName = $this->implementedClass->driver;
            $this->instance = $this->resolveProvider($driverName);

            $this->setCurrentIterateProvider($driverName);
        } else {
            $this->instance = app($this->defaultProvider);

            $this->setCurrentIterateProvider($this->defaultProvider);
        }

        $this->instance->switchProvider = $this->switchProvider;
        $this->instance->currentIterateProvider = $this->currentIterateProvider;

        return $this->callConcreteHandler();
    }

    /**
     * Set current iterate provider to SMS context.
     *
     * @param string $driver
     */
    private function setCurrentIterateProvider(string $driver)
    {
        $this->currentIterateProvider = $driver;
    }

    /**
     * Resolve provider and searching from IoC container.
     *
     * @param string $provider
     * @return \Illuminate\Contracts\Foundation\Application|mixed
     */
    private function resolveProvider(string $provider)
    {
        if (!$this->isRegisteredProvider($provider)) {
            return app($this->defaultProvider);
        }

        return app($provider);
    }

    /**
     * Check whether user choice's provider is registered provider.
     *
     * @param string $provider
     * @return bool
     */
    private function isRegisteredProvider(string $provider): bool
    {
        if (!in_array($provider, $this->providers)) {
            return false;
        }

        return true;
    }

    /**
     * Call the handle method from implemented class.
     *
     * @return mixed
     */
    private function callConcreteHandler()
    {
        return $this->handler($this->implementedClass);
    }

    /**
     * Finally call the HTTP request.
     *
     * @param SmsContract $implementedClass
     * @param DriverContract $provider
     * @return array|\Psr\Http\Message\StreamInterface
     *
     * @throws GuzzleHttp\Exception\GuzzleException
     */
    public function commit(SmsContract $implementedClass, DriverContract $provider)
    {
        // Re-assign implemented class for up to date object's changes.
        $this->implementedClass = $implementedClass;

        // Re-assign current provider object.
        $this->instance = $provider;

        return $this->callHTTPRequest();
    }

    /**
     * Make a HTTP call to the external provider.
     *
     * @return array|\Psr\Http\Message\StreamInterface
     * @throws GuzzleHttp\Exception\GuzzleException
     */
    private function callHTTPRequest()
    {
        // Add in current iterated driver.
        $this->addDriverToQueueProvider();

        $sms = collect([
            'to' => $this->getImplementedClass()->to,
            'message' => $this->getImplementedClass()->message,
            'from' => $this->getImplementedClass()->from,
        ]);

        try {
            $this->response = $this->client->request('POST', $this->baseURL, $this->headers);

            /**
             * Success make a call to external API which returned 200 status code.
             */
            if ($this->isRequestSuccess()) {
                $HTTPResponseBody = $this->response->getBody()->getContents();

                // May used by programmer to call the response body only.
                $this->data = $HTTPResponseBody;

                // Trigger `success` method inside `implemented class`
                if (method_exists($this->getImplementedClass(), 'success')) {
                    call_user_func([$this->getImplementedClass(), 'success'], $this->response, $HTTPResponseBody);
                }

                event(new SmsSent($sms, $this->response, $HTTPResponseBody));

                $this->shouldContinue = false;

                return $this->data;
            }

        } catch (GuzzleHttp\Exception\ClientException $e) {
            $HTTPResponseBody = $e->getResponse()->getBody()->getContents();

            // Trigger `error` method inside `implemented class`
            if (method_exists($this->getImplementedClass(), 'error')) {
                call_user_func([$this->getImplementedClass(), 'error'], $e, $HTTPResponseBody);
            }

            // Failed make a call to external API which returned status code other than 200
            event(new SmsFailed($sms, $e, $HTTPResponseBody));

            // call another SMS providers if one has failed
            if ($this->switchProvider) {
                $this->shouldSwitchInvokeOtherProvider();
            }
        }
    }

    /**
     * Switch provider to send SMS in case one is failing.
     */
    public function shouldSwitchInvokeOtherProvider()
    {
        if ($this->shouldContinue) {
            if (!$this->isAbleToContinueTheQueue()) {
                return false;
            }

            $nextDriver = $this->takeNextDriver();

            $this->setProviderDriver($nextDriver[0]);

            // Send if qualify to switch only.
            if ($this->isQualifiedToSwitch($nextDriver[0])) {
                return $this->send($this->getImplementedClass(), true);
            }

            // If not qualified, attempt to find the next driver and skip current driver.
            // Send along the current driver.
            return $this->getNextQualifiedDriverToPerformSwitching($nextDriver[0]);
        }

        return false;
    }

    /**
     * Take the driver that still not in queue.
     *
     * @return array
     */
    private function takeNextDriver() : array
    {
        $nextDriver = array_values(array_diff($this->providers, $this->queuedProviders));

        return $nextDriver;
    }

    /**
     * Check whether the driver is qualified to queue.
     *
     * @param string $driver
     * @return bool
     */
    private function isQualifiedToSwitch(string $driver) : bool
    {
        if (!app($driver)->enableSwitching) {
            return false;
        }

        return true;
    }

    /**
     * Overwrite implemented class's SMS driver.
     *
     * @param string $driver
     */
    private function setProviderDriver(string $driver) : void
    {
        $this->implementedClass->driver = $driver;
    }

    /**
     * Add current iterated driver to driver queue array for later use.
     */
    private function addDriverToQueueProvider() : void
    {
        $this->queuedProviders[] = $this->currentIterateProvider;
    }

    /**
     * Get next driver that qualified to do the switching. SKIP previously set driver.
     *
     * @param string $driver
     * @return bool|mixed
     */
    private function getNextQualifiedDriverToPerformSwitching(string $driver)
    {
        // Set iterate skipped driver onto SMS context.
        $this->setCurrentIterateProvider($driver);

        // Add into done queue array.
        $this->addDriverToQueueProvider();

        // Skip previously assigned driver and take next driver instead.
        $nextDriver = $this->takeNextDriver();

        // No more driver existed, then no more operation.
        if (empty($nextDriver)) {
            return false;
        }

        // Discard operation since all the drivers all were tested.
        if (!$this->isAbleToContinueTheQueue()) {
            return false;
        }

        // Overwrite existing chosen driver.
        $this->setProviderDriver($nextDriver[0]);

        // Get instance of driver for on the fly checking whether can do context switching or not.
        // If can do, then proceed with this provider.
        if (app($nextDriver[0])->enableSwitching) {
            return $this->send($this->getImplementedClass(), true);
        }

        // Recursive searching for the next available drivers.
        return $this->getNextQualifiedDriverToPerformSwitching($nextDriver[0]);
    }

    /**
     * Is able to continue switched the provider.
     *
     * @return bool
     */
    private function isAbleToContinueTheQueue() : bool
    {
        if (count($this->queuedProviders) >= $this->providers) {
            return false;
        }

        return true;
    }

    /**
     * Get implemented/concrete class object within abstract class.
     *
     * @return mixed
     */
    public function getImplementedClass() : SmsContract
    {
        return $this->implementedClass;
    }

    /**
     * Check whether the HTTP request is success or failed.
     *
     * @return bool
     */
    protected function isRequestSuccess(): bool
    {
        if (!in_array($this->response->getStatusCode(), $this->successCodes)) {
            return false;
        }

        return true;
    }

    /**
     * Returned HTTP response.
     *
     * @return GuzzleHttp\Psr7\Response
     */
    public function getRawResponse()
    {
        return $this->response;
    }

    /**
     * Returned response's data(If success only).
     *
     * @return array
     */
    public function getResponseData()
    {
        return $this->data;
    }

    /**
     * Initialize guzzle client object.
     *
     * @return $this
     */
    protected function initHTTPClient()
    {
        $this->client = new GuzzleHttp\Client();

        return $this;
    }

    /**
     * Adding + prefix.
     *
     * @param string $str
     * @return string
     */
    protected function addPlusPrefix(string $str): string
    {
        if (!$this->isGotPlusPrefix($str)) {
            return '+' . $str;
        }

        return $str;
    }

    /**
     * Checking whether got + prefix.
     *
     * @param string $to
     * @return bool
     */
    protected function isGotPlusPrefix(string $to): bool
    {
        if (substr($to, 0, 1) === '+') {
            return true;
        }

        return false;
    }

    /**
     * Set additional headers to the HTTP request.
     *
     * @param array $headers
     * @return $this
     */
    protected function setAdditionalHeaders(array $headers = [])
    {
        $this->headers = array_merge($this->headers, $headers);

        return $this;
    }
}
