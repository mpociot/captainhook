<?php

namespace Mpociot\CaptainHook;

use GuzzleHttp\Client;
use GuzzleHttp\ClientInterface;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\ServiceProvider;
use Mpociot\CaptainHook\Commands\AddWebhook;
use Illuminate\Foundation\Bus\DispatchesJobs;
use Mpociot\CaptainHook\Commands\ListWebhooks;
use Mpociot\CaptainHook\Commands\DeleteWebhook;
use Mpociot\CaptainHook\Jobs\TriggerWebhooksJob;

/**
 * This file is part of CaptainHook arrrrr.
 *
 * @license MIT
 */
class CaptainHookServiceProvider extends ServiceProvider
{
    use DispatchesJobs;

    /**
     * The registered event listeners.
     *
     * @var array
     */
    protected $listeners;

    /**
     * All registered webhooks.
     * @var array
     */
    protected $webhooks = [];

    /**
     * @var Client
     */
    protected $client;

    /**
     * @var \Illuminate\Contracts\Cache\Repository
     */
    protected $cache;

    /**
     * @var \Illuminate\Contracts\Config\Repository
     */
    protected $config;

    /**
     * Bootstrap.
     */
    public function boot()
    {
        $this->client = new Client();
        $this->cache = app('Illuminate\Contracts\Cache\Repository');
        $this->config = app('Illuminate\Contracts\Config\Repository');
        $this->publishMigration();
        $this->publishConfig();
        $this->publishSparkResources();
        $this->listeners = collect($this->config->get('captain_hook.listeners', []))->values();
        $this->registerEventListeners();
        $this->registerRoutes();
    }

    /**
     * Register the service provider.
     *
     * @return void
     */
    public function register()
    {
        $this->registerCommands();
    }

    /**
     * Publish migration.
     */
    protected function publishMigration()
    {
        $migrations = [
            __DIR__.'/../../database/2015_10_29_000000_captain_hook_setup_table.php' =>
                database_path('/migrations/2015_10_29_000000_captain_hook_setup_table.php'),
            __DIR__.'/../../database/2015_10_29_000001_captain_hook_setup_logs_table.php' =>
                database_path('/migrations/2015_10_29_000001_captain_hook_setup_logs_table.php'),
        ];

        // To be backwards compatible
        foreach ($migrations as $migration => $toPath) {
            preg_match('/_captain_hook_.*\.php/', $migration, $match);
            $published_migration = glob(database_path('/migrations/*'.$match[0]));
            if (count($published_migration) !== 0) {
                unset($migrations[$migration]);
            }
        }

        $this->publishes($migrations, 'migrations');
    }

    /**
     * Publish configuration file.
     */
    protected function publishConfig()
    {
        $this->publishes([
            __DIR__.'/../../config/config.php' => config_path('captain_hook.php'),
        ]);
    }

    protected function publishSparkResources()
    {
        $this->loadViewsFrom(__DIR__.'/../../resources/views/', 'captainhook');

        $this->publishes([
            __DIR__.'/../../resources/assets/js/' => base_path('resources/assets/js/components/'),
            __DIR__.'/../../resources/views/' => base_path('resources/views/vendor/captainhook/settings/'),
        ], 'spark-resources');
    }

    /**
     * Register all active event listeners.
     */
    protected function registerEventListeners()
    {
        foreach ($this->listeners as $eventName) {
            $this->app['events']->listen($eventName, [$this, 'handleEvent']);
        }
    }

    /**
     * @param array $listeners
     */
    public function setListeners($listeners)
    {
        $this->listeners = $listeners;

        $this->registerEventListeners();
    }

    /**
     * @param array $webhooks
     */
    public function setWebhooks($webhooks)
    {
        $this->webhooks = $webhooks;
        $this->getCache()->rememberForever(Webhook::CACHE_KEY, function () {
            return $this->webhooks;
        });
    }

    /**
     * @return \Illuminate\Support\Collection
     */
    public function getWebhooks()
    {
        // Check if migration ran
        if (Schema::hasTable((new Webhook)->getTable())) {
            return collect($this->getCache()->rememberForever(Webhook::CACHE_KEY, function () {
                return Webhook::all();
            }));
        }

        return collect();
    }

    /**
     * @return \Illuminate\Contracts\Cache\Repository
     */
    public function getCache()
    {
        return $this->cache;
    }

    /**
     * @param \Illuminate\Contracts\Cache\Repository $cache
     */
    public function setCache($cache)
    {
        $this->cache = $cache;
    }

    /**
     * @param ClientInterface $client
     */
    public function setClient($client)
    {
        $this->client = $client;
    }

    /**
     * @param \Illuminate\Contracts\Config\Repository $config
     */
    public function setConfig($config)
    {
        $this->config = $config;
    }

    /**
     * Event listener.
     *
     * @param $eventData
     */
    public function handleEvent($eventData)
    {
        $eventName = Event::firing();
        $webhooks = $this->getWebhooks()->where('event', $eventName);
        $webhooks = $webhooks->filter($this->config->get('captain_hook.filter', null));

        if (! $webhooks->isEmpty()) {
            $this->dispatch(new TriggerWebhooksJob($webhooks, $eventData));
        }
    }

    /**
     * Register the artisan commands.
     */
    protected function registerCommands()
    {
        $this->commands(
            ListWebhooks::class,
            AddWebhook::class,
            DeleteWebhook::class
        );
    }

    /**
     * Register predefined routes used for Spark.
     */
    protected function registerRoutes()
    {
        if (class_exists('Laravel\Spark\Providers\AppServiceProvider')) {
            include __DIR__.'/../../routes.php';
        }
    }
}
