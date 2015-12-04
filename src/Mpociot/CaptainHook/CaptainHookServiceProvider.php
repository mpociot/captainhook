<?php
namespace Mpociot\CaptainHook;

use GuzzleHttp\Client;
use GuzzleHttp\ClientInterface;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;
use Mpociot\CaptainHook\Commands\AddWebhook;
use Mpociot\CaptainHook\Commands\DeleteWebhook;
use Mpociot\CaptainHook\Commands\ListWebhooks;

/**
 * This file is part of CaptainHook arrrrr
 *
 * @license MIT
 * @package CaptainHook
 */
class CaptainHookServiceProvider extends ServiceProvider
{
    /**
     * The registered event listeners.
     *
     * @var array
     */
    protected $listeners = ['eloquent.*'];

    /**
     * All registered webhooks
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
     * Bootstrap
     */
    public function boot()
    {
        $this->client = new Client();
        $this->cache = app('Illuminate\Contracts\Cache\Repository');
        $this->config = app('Illuminate\Contracts\Config\Repository');
        $this->publishMigration();
        $this->publishConfig();
        $this->registerEventListeners();
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
     * Publish migration
     */
    protected function publishMigration()
    {
        $published_migration = glob(database_path('/migrations/*_captain_hook_setup_table.php'));
        if (count($published_migration) === 0) {
            $this->publishes([
                __DIR__ . '/../../database/2015_10_29_000000_captain_hook_setup_table.php' => database_path('/migrations/' . date('Y_m_d_His') . '_captain_hook_setup_table.php'),
            ], 'migrations');
        }
    }

    /**
     * Publish configuration file
     */
    protected function publishConfig()
    {
        $this->publishes([
            __DIR__.'/../../config/config.php' => config_path('captain_hook.php'),
        ]);
    }

    /**
     * Register all active event listeners
     */
    protected function registerEventListeners()
    {
        foreach ($this->listeners as $eventName) {
            $this->app[ 'events' ]->listen($eventName, [$this, 'handleEvent']);
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
        if (!$this->getCache()->has(Webhook::CACHE_KEY)) {
            $this->getCache()->rememberForever(Webhook::CACHE_KEY, function () {
                return Webhook::all();
            });
        }
        return collect($this->getCache()->get(Webhook::CACHE_KEY));
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

        $this->callWebhooks($webhooks, $eventData);
    }

    /**
     * Call all webhooks asynchronous
     *
     * @param array $webhooks
     * @param       $eventData
     */
    private function callWebhooks($webhooks, $eventData)
    {
        foreach ($webhooks as $webhook) {
            $this->client->post($webhook[ 'url' ], [
                'body' => json_encode($this->createRequestBody($eventData)),
                'verify' => false,
                'future' => true
            ]);
        }
    }

    /**
     * Create the request body for the event data.
     * Override this method if necessary to post different data.
     *
     * @param $eventData
     *
     * @return array
     */
    protected function createRequestBody($eventData)
    {
        return $eventData;
    }

    /**
     * Register the artisan commands
     */
    protected function registerCommands()
    {
        $this->app[ 'hook.list' ] = $this->app->share(function () {
            return new ListWebhooks();
        });

        $this->app[ 'hook.add' ] = $this->app->share(function () {
            return new AddWebhook();
        });

        $this->app[ 'hook.delete' ] = $this->app->share(function () {
            return new DeleteWebhook();
        });

        $this->commands(
            'hook.list',
            'hook.add',
            'hook.delete'
        );
    }
}
