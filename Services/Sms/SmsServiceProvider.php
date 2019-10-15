<?php

namespace App\Services\Sms;

use Illuminate\Support\ServiceProvider;

class SmsServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     *
     * @return void
     */
    public function register()
    {
        $this->app->singleton(Sms::class, function () {
            return new Sms();
        });

        app()->singleton('nexmo', function () {
            return new \App\Services\Sms\Drivers\NexmoProvider(
                config('services.nexmo.api_key'),
                config('services.nexmo.api_secret'),
                config('services.nexmo.switch')
            );
        });

        app()->singleton('twilio', function () {
            return new \App\Services\Sms\Drivers\TwilioProvider(
                config('services.twilio.account_sid'),
                config('services.twilio.auth_token'),
                config('services.twilio.switch')
            );
        });

        app()->singleton('yunpian', function () {
            return new \App\Services\Sms\Drivers\YunpianProvider(
                config('services.yunpian.api_key'),
                config('services.yunpian.switch')
            );
        });
    }

    /**
     * Bootstrap services.
     *
     * @return void
     */
    public function boot()
    {
        //
    }
}
