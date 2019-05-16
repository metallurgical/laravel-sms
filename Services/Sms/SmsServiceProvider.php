<?php

namespace App\Services\Sms;

use App\Services\Sms\Drivers\NexmoProvider;
use App\Services\Sms\Drivers\TwilioProvider;
use App\Services\Sms\Drivers\YunpianProvider;
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
            return new NexmoProvider(
                config('services.nexmo.api_key'),
                config('services.nexmo.api_secret')
            );
        });

        app()->singleton('twilio', function () {
            return new TwilioProvider(
                config('services.twilio.account_sid'),
                config('services.twilio.auth_token')
            );
        });

        app()->singleton('yunpian', function () {
            return new YunpianProvider(config('services.yunpian.api_key'));
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
