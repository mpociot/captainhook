<img src="http://www.marcelpociot.com/git/hook.png" style="width: 100%" alt="Captain Hook" />
# Captain Hook
## Add Webhooks to your Laravel app, arrr

![image](http://img.shields.io/packagist/v/mpociot/captainhook.svg?style=flat)
![image](http://img.shields.io/packagist/l/mpociot/captainhook.svg?style=flat)
[![codecov.io](https://codecov.io/github/mpociot/captainhook/coverage.svg?branch=master)](https://codecov.io/github/mpociot/captainhook?branch=master)
[![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/mpociot/captainhook/badges/quality-score.png?b=master)](https://scrutinizer-ci.com/g/mpociot/captainhook/?branch=master)
[![Build Status](https://travis-ci.org/mpociot/captainhook.svg?branch=master)](https://travis-ci.org/mpociot/captainhook)

Implement multiple webhooks into your Laravel app using the Laravel Event system.


```bash
php artisan hook:add http://www.myapp.com/hooks/ '\App\Events\PodcastWasPurchased'
```


## Contents

- [Installation](#installation)
- [Implementation](#implementation)
- [Usage](#usage)
    - [Custom event listeners](#listeners)
    - [Add new webhooks](#add)
    - [Delete existing webhooks](#delete)
    - [List all active webhooks](#list)
    - [Receiving a webhook notification](#webhook)
- [License](#license) 

<a name="installation" />
## Installation

In order to add CaptainHook to your project, just add 

    "mpociot/captainhook": "~1.0"

to your composer.json. Then run `composer install` or `composer update`.

Or run `composer require mpociot/captainhook ` if you prefere that.

Then in your `config/app.php` add 

    Mpociot\CaptainHook\CaptainHookServiceProvider::class
    
to the `providers` array.


Publish and run the migration to create the "webhooks" table that will hold all installed webhooks.

```bash
php artisan vendor:publish --provider="Mpociot\CaptainHook\CaptainHookServiceProvider"

php artisan migrate
```

<a name="usage" />
## Usage

The CaptainHook service provider listens for every `eloquent.*` events.

If the package finds a configured webhook for an event, it will make a `POST` request to the specified URL.

Webhook data is sent as JSON in the POST request body. The full event object is included and can be used directly, after parsing the JSON body.

**Example**

Let's say you want to have a webhook that get's called every time your User model get's updated.

The event that get's called from Laravel will be:

`eloquent.updated \App\User`

So this will be the event you want to listen for.

<a name="add" />
### Add new webhooks

If you know which event you want to listen to, you can add a new webhook by using the `hook:add` artisan command.

This command takes two arguments:

- The webhook URL that will receive the POST requests
- The event name. This could either be one of the `eloquent.*` events, or one of your custom events.

```bash
php artisan hook:add http://www.myapp.com/hook/ 'eloquent.saved \App\User'
```

You can also add multiple webhooks for the same event, as all configured webhooks will get calles asynchronous.

<a name="delete" />
### Delete existing webhooks

To remove an existing webhook from the system, use the `hook:delete` command. This command takes the webhook ID as an argument.

```bash
php artisan hook:delete 2
```

<a name="list" />
### List all active webhooks

To list all existing webhooks, use the `hook:list` command.

It will output all configured webhooks in a table.

<a name="listeners" />
### Custom event listeners

If you want CaptainHook to listen for custom events, you need to override the `CaptainHookServiceProvider`.

All listeners are defined in a protected array inside the service provider.

```php

class CustomCaptainHookServiceProvider extends CaptainHookServiceProvider
{

    /**
     * The registered event listeners.
     *
     * @var array
     */
    protected $listeners = ["eloquent.*", "\\App\\Events\\MyCustomEvent"];

}
```

If you extend the original `CaptainHookServiceProvider` be sure to replace your custom service provider with the package provider in your `config/app.php`.


<a name="webhook" />
### Receiving a webhook notification

To receive the event data in your configured webhook, use:

```php
// Retrieve the request's body and parse it as JSON
$input = @file_get_contents("php://input");
$event_json = json_decode($input);

// Do something with $event_json
```

<a name="license" />
## License

CaptainHook is free software distributed under the terms of the MIT license.

'Day 02: Table, Lamp & Treasure Map' image licensed under [Creative Commons 2.0](https://creativecommons.org/licenses/by/2.0/) - Photo from [stevedave](https://www.flickr.com/photos/stevedave/4153323914) 
