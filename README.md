<img src="http://www.marcelpociot.com/git/hook.png" style="width: 100%" alt="Captain Hook" />
# Captain Hook
## Add Webhooks to your Laravel app, arrr

![image](http://img.shields.io/packagist/v/mpociot/captainhook.svg?style=flat)
![image](http://img.shields.io/packagist/l/mpociot/captainhook.svg?style=flat)
[![codecov.io](https://codecov.io/github/mpociot/captainhook/coverage.svg?branch=master)](https://codecov.io/github/mpociot/captainhook?branch=master)
[![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/mpociot/captainhook/badges/quality-score.png?b=master)](https://scrutinizer-ci.com/g/mpociot/captainhook/?branch=master)
[![Build Status](https://travis-ci.org/mpociot/captainhook.svg?branch=master)](https://travis-ci.org/mpociot/captainhook)
[![StyleCI](https://styleci.io/repos/45216255/shield)](https://styleci.io/repos/45216255)

Implement multiple webhooks into your Laravel app using the Laravel Event system.

## Examples

```bash
php artisan hook:add http://www.myapp.com/hooks/ '\App\Events\PodcastWasPurchased'
php artisan hook:add http://www.myapp.com/hooks/ 'eloquent.saved \App\User'
```

```php
Webhook::create([
    "url" => Input::get("url"),
    "event" => "\\App\\Events\\MyEvent",
    "tenant_id" => Auth::id()
]);
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
    - [Webhook logging](#logging)
    - [Using webhooks with multi tenancy](#tenant)
- [License](#license)

<a name="installation" />
## Installation

In order to add CaptainHook to your project, just add

    "mpociot/captainhook": "~2.0"

to your composer.json. Then run `composer install` or `composer update`.

Or run `composer require mpociot/captainhook ` if you prefer that.

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

All listeners are defined in the config file located at `config/captain_hook.php`.

<a name="webhook" />
### Receiving a webhook notification

To receive the event data in your configured webhook, use:

```php
// Retrieve the request's body and parse it as JSON
$input = @file_get_contents("php://input");
$event_json = json_decode($input);

// Do something with $event_json
```

<a name="logging" />
### Webhook logging

Starting with version 2.0, this package allows you to log the payload and response of the triggered webhooks.

> **NOTE:** A non-blocking queue driver (not `sync`) is highliy recommended. Otherwise your application will need to wait for the webhook execution.

You can configure how many logs will be saved **per webhook** (Default 50).

This value can be modified in the configuration file `config/captain_hook.php`.

<a name="tenant" />
### Using webhooks with multi tenancy

Sometimes you don't want to use system wide webhooks, but rather want them scoped to a specific "tenant".
This could be bound to a user or a team.

The webhook table has a field `tenant_id` for this purpose.
So if you want your users to be able to add their own webhooks, you won't use the artisan commands to add webhooks to the database,
but add them on your own.

To add a webhook that is scoped to the current user, you could do for example:

```php
Webhook::create([
    "url" => Input::get("url"),
    "event" => "\\App\\Events\\MyEvent",
    "tenant_id" => Auth::id()
]);
```

Now when you fire this event - you want to call the webhook only for the currently logged in user.

In order to filter the webhooks, modify the `filter` configuration value in the `config/captain_hook.php` file.
This filter is a Laravel collection filter.

To return only the webhooks for the currently logged in user, it might look like this:

```php
'filter' => function( $webhook ){
    return $webhook->tenant_id == Auth::id();
},
```

<a name="license" />
## License

CaptainHook is free software distributed under the terms of the MIT license.

'Day 02: Table, Lamp & Treasure Map' image licensed under [Creative Commons 2.0](https://creativecommons.org/licenses/by/2.0/) - Photo from [stevedave](https://www.flickr.com/photos/stevedave/4153323914)
