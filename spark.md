# Spark installation process

This package comes with predefined views and routes to use with your existing Spark installation.

<img src="http://marcelpociot.com/user/pages/about/listing.png" />

In order to install Captainhook into your Spark application:

**1. Publish the Spark resources (views, VueJS components):**

`php artisan vendor:publish --provider="Mpociot\CaptainHook\CaptainHookServiceProvider" --tag="spark-resources"`

**2. Add the javascript components to your bootstrap.js file**

Add `require('./captainhook/webhooks.js');` to your `resources/assets/js/components/bootstrap.js` file.

**3. Compile the Javascript components**

`gulp`

**4. Add the HTML snippets**

File: `vendor/spark/settings.blade.php`

Place a link to the webhooks settings tab:

```html
<!-- Webhooks Link -->
<li role="presentation">
    <a href="#webhooks" aria-controls="webhooks" role="tab" data-toggle="tab">
        <i class="fa fa-fw fa-btn fa-code"></i>Webhooks
    </a>
</li>
```

Inside the `<!-- Tab Panels -->` section, place the code to load the webhooks tab:

```html
<div role="tabpanel" class="tab-pane" id="webhooks">
    @include('captainhook::settings.webhooks')
</div>
```

**5. Try it out**

Log into your Spark application and access the new webhook tab located at:

`http://your-spark.url/settings#/webhooks`

**Important note:**

To make sure that the webhooks only get called for the correct user, modify the 'filter' property of the `config/captain_hook.php`

```php
'filter' => function ($webhook) {
    return $webhook->tenant_id == auth()->user()->getKey();
},
```
