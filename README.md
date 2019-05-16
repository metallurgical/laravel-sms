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
    'from' => env('NEXMO_FROM_NUMBER')
],

'twilio' => [
    'account_sid' => env('TWILIO_ACCOUNT_SID'),
    'auth_token' => env('TWILIO_AUTH_TOKEN'),
    'from' => env('TWILIO_FROM_NUMBER')
],

'yunpian' => [
    'api_key' => env('YUNPIAN_API_KEY'),
    'from' => env('YUNPIAN_FROM_NUMBER')
],
``` 

After putting all the snippet above, put following environment variables into `.env` file:

```
NEXMO_API_KEY=<nexmo-api-key>
NEXMO_API_SECRET=<nexmo-api-secret>
NEXMO_FROM_NUMBER=<nexmo-from-number>

TWILIO_ACCOUNT_SID=<twilio-account-sid>
TWILIO_AUTH_TOKEN=<twilio-auth-token>
TWILIO_FROM_NUMBER=<twilio-from-number>

YUNPIAN_API_KEY=<yunpian-api-key>
YUNPIAN_FROM_NUMBER=<yunpian-from-number>
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

    public $driver = 'twillio'; // if not supply this property, code will automatically take default SMS driver, in this case is `nexmo`. 

    public function handle()
    {
        $this
            ->to('60169344497')
            ->signature('[COMPANY NAME]') // International country require signature 
            ->message('Thanks for register our application.');
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

This service has its own default driver, in our case is `nexmo`. To use different driver other than `nexmo`, you can override it by adding class member `public $driver = 'twillio';`. Available drivers as for now are **nexmo, twilio and yunpian**. More drivers are coming in future.

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
        Sms::send(new SmsUserAfterRegister());
    }
}
```

## Uniqueness

This service has its own advantage over existing implementation. The purpose of this creation is because to handle user experience in more convenient way by enabling `shouldSwitch` driver in case one is failing. Service will try to re-send an sms to user if 1st attempt was not successful by using different drivers(switching) until all of the drivers are tested. Sounds great, right? 

To enable switching, pass in `boolean` value as following:

```php
Sms::send(new SmsUserAfterRegister(), true); // default to false
```

