# Introduction

Laravel services to send SMS from various providers. Support multiple drivers (nexmo, twilio, yunpian) and more coming in progress. Easy to use and easy to add custom driver to support other providers. 

The reason this package not published as a package because its only react as a service and intentionally to solve internal application which need more customization and of course i'm **TOO LAZY** to write it down into package. lol

## Installation
Download this repo as an archive and extract it somewhere. Then copy over `Services` folder into your existing laravel project. Folder structures should be like following:

```
|- Project-Root
   |- app
      |- Services
         |- Sms
|- composer.json
|- other files...         
```

Add sms service provider inside config file `config/app.php` under `providers` array :

```php
'providers' => [
    ......
    ......
    /*
     * Application Service Providers...
     */
    .....
    .....
    App\Services\Sms\SmsServiceProvider::class,
]    
```

On the same file, add class alias under `aliases` array :
```php
'aliases' => [
    ......
    ......
    'Sms' => App\Services\Sms\Facades\Sms::class,
]    
```

Since all the sms providers have their own credentials, this service store those information inside `config/services.php`, copy snippet below and paste into your config service file:

```
'nexmo' => [
    'api_key' => env('NEXMO_API_KEY'),
    'api_secret' => env('NEXMO_API_SECRET'),
    'from' => env('NEXMO_FROM_NUMBER'),
    'switch' => env('NEXMO_ENABLE_SWITCHING', 0)
],

'twilio' => [
    'account_sid' => env('TWILIO_ACCOUNT_SID'),
    'auth_token' => env('TWILIO_AUTH_TOKEN'),
    'from' => env('TWILIO_FROM_NUMBER'),
    'switch' => env('TWILIO_ENABLE_SWITCHING', 0)
],

'yunpian' => [
    'api_key' => env('YUNPIAN_API_KEY'),
    'from' => env('YUNPIAN_FROM_NUMBER'),
    'switch' => env('YUNPIAN_ENABLE_SWITCHING', 0)
],

'isms' => [
    'username' => env('ISMS_USERNAME'),
    'password' => env('ISMS_PASSWORD'),
    'user_id' => env('ISMS_USER_ID'),
    'switch' => env('ISMS_ENABLE_SWITCHING', 0)
],
``` 

After putting all the snippet above, put following environment variables into `.env` file:

```
NEXMO_API_KEY=<nexmo-api-key>
NEXMO_API_SECRET=<nexmo-api-secret>
NEXMO_FROM_NUMBER=<nexmo-from-number>
NEXMO_ENABLE_SWITCHING=1

TWILIO_ACCOUNT_SID=<twilio-account-sid>
TWILIO_AUTH_TOKEN=<twilio-auth-token>
TWILIO_FROM_NUMBER=<twilio-from-number>
TWILIO_ENABLE_SWITCHING=1

YUNPIAN_API_KEY=<yunpian-api-key>
YUNPIAN_FROM_NUMBER=<yunpian-from-number>
YUNPIAN_ENABLE_SWITCHING=1

ISMS_USERNAME=<isms-username>
ISMS_PASSWORD=<isms-password>
ISMS_USER_ID=<isms-user-id>
ISMS_ENABLE_SWITCHING=1
```

## Usage
There is nothing more clear than real world example, so lets give a shot. Assume we want to send sms after successful registration, you may create one file with the name `SmsUserAfterRegister.php` that should implement `App\Services\Sms\Contracts\SmsContract` and use trait `App\Services\Sms\HasSms`. You may put anywhere in your project. The file should be looks like following:

```php
<?php

namespace App\Sms;

use App\Services\Sms\HasSms;
use App\Services\Sms\Contracts\SmsContract;

class SmsUserAfterRegister implements SmsContract
{
    use HasSms;

    public $driver = 'twilio'; // if not supply this property, code will automatically take default SMS driver, in this case is `nexmo`. 

    public function handle()
    {
        $this
            ->to('60169344497') // No plus(+) symbol needed
            ->signature('[COMPANY NAME]') // International country require signature except yunpian
            ->template_id('123456') // For yunpian only
            ->template_value(['key' => rand(1000, 5000)]) // For yunpian only
            ->message('Thanks for register our application.'); // Except yunpian
    }

    public function error($exception, $HTTPResponseBody)
    {
        // dd($exception, $HTTPResponseBody);
    }

     
    public function success($response, $HTTPResponseBody)
    {
       // dd($response, $HTTPResponseBody);
    }
}
```

This service has its own default driver, in our case is `nexmo`. To use different driver other than `nexmo`, you can override it by adding class member `public $driver = 'twilio';`. Available drivers as for now are **nexmo, twilio and yunpian**. More drivers are coming in future.

Every successful and erroneous of sending sms, you'll be notified. To catch these information, override both `public function error($exception, $HTTPResponseBody)` and `public function success($response, $HTTPResponseBody)`.

Somewhere in your controller, sending SMS is easy as following:

```php
<?php

namespace App\Http\Controllers;

use Sms;

class RegisterController extends Controller
{
    public function register()
    {
        // do register operation
        Sms::send(new SmsUserAfterRegister());
    }
}
```

## Uniqueness

This service has its own advantage over existing implementation. The purpose of this creation is because to handle user experience in more convenient way by enabling `shouldSwitch` driver in case one driver is failed sending sms. Service will try to re-send sms to recipient if 1st attempt was not successful by using different drivers(switching) until all of the drivers are tested. Sounds great, right? 

To enable switching, pass in `boolean` value as following:

```php
Sms::send(new SmsUserAfterRegister(), true); // default to false
```

To exclude provider from participate in `Switching Mode`, set environment variable to false, like following:

```
NEXMO_ENABLE_SWITCHING=0
TWILIO_ENABLE_SWITCHING=0
YUNPIAN_ENABLE_SWITCHING=0
```

## Events

In case you're comfortable playing with event listener, you may listen to event published by this service out of the box for you. Register following events before used under `app/Providers/EventServiceProvider.php` and paste following event:

```php
namespace App\Providers;

use App\Services\Sms\Events\SmsFailed as SmsFailedEvent;
use App\Services\Sms\Events\SmsSent as SmsSentEvent;

protected $listen = [
    ......
    ......
    SmsSentEvent::class => [
        // you should create sent event listener by yourself
    ],
    SmsFailedEvent::class => [
        // you should create failed event listener by yourself
    ]
];
```

`App\Services\Sms\Events\SmsSent.php` event provided 3 parameters object to play with:

- `$sms` : Holding recipient information as well as message being sent
- `$response` : Guzzle HTTP client's response
- `$HTTPResponseBody` : Provider's HTTP response

`App\Services\Sms\Events\SmsFailed.php` event provided 3 parameters object to play with:

- `$sms` : Holding recipient information as well as message being sent
- `$e` : Exception object of Guzzle Client exception
- `$HTTPResponseBody` : Provider's HTTP response


### Sent event listener

Example of sent event listener:

```php
<?php

namespace App\Listeners\Sms;

use App\Services\Sms\Events\SmsSent as SmsSentEvent;

class SmsSent
{
    /**
     * Create the event listener.
     *
     * @return void
     */
    public function __construct()
    {
        //
    }

    /**
     * Handle the event.
     *
     * Notes:
     *  $event->sms = to get details information of recipient number, from and message
     *  $event->response = to get details information of successful response
     *  $event->HTTPResponseBody = HTTPResponse body returned by the external provider
     *
     * @param SmsSentEvent $event
     */
    public function handle(SmsSentEvent $event)
    {
        // dd($event);
    }
}
```

### Failed event listener
Example of failed event listener:

```php
<?php

namespace App\Listeners\Sms;

use App\Services\Sms\Events\SmsFailed as SmsFailedEvent;

class SmsFailed
{
    /**
     * Create the event listener.
     *
     * @return void
     */
    public function __construct()
    {
        //
    }

    /**
     * Handle the event.
     *
     * Notes:
     *  $event->sms = to get details information of recipient number, from and message
     *  $event->exception = to get details information of error status code, message, etc
     *  $event->HTTPResponseBody = to get details information of error sent by external provider
     *
     * @param SmsFailedEvent $event
     */
    public function handle(SmsFailedEvent $event)
    {
         // dd($event);
    }
}
```

Read laravel events and listeners on how to use that in more details [here](https://laravel.com/docs/5.8/events).

## Notes on Yunpian provider
This provider require sending along `tpl_id` and `tpl_value` to be used for international messaging. Fortunately, this service provide api to interact with those params. Call following method inside implemented class:

```php
public function handle()
{
    $this
        ->to('60169344497')
        ->signature('[COMPANY NAME]') // International country require signature 
        ->template_id('3333333') // template ID defined in yunpian account
        ->template_value(['code' => rand(1000, 5000)]) // template value in yunpian account
        ->message('Thanks for register our application.'); // For "yunpian", 
}
```

`message()` and `signature()` options does not require for **yunpian** provider, but did require for others provider to works