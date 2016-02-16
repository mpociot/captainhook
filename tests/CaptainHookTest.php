<?php

use Mockery as m;
use Mpociot\CaptainHook\Webhook;
use Illuminate\Queue\SerializesModels;

class CaptainHookTest extends Orchestra\Testbench\TestCase
{
    protected function getPackageProviders($app)
    {
        return ['Mpociot\CaptainHook\CaptainHookServiceProvider'];
    }

    protected function mockConfig($m, $configOption, $return)
    {
        return $m->shouldReceive('get')
            ->with($configOption)
            ->andReturn($return);
    }

    public function setUp()
    {
        parent::setUp();

        $this->artisan('migrate', [
            '--database' => 'testing',
            '--realpath' => realpath(__DIR__.'/../src/database'),
        ]);
    }

    public function tearDown()
    {
        \Cache::forget(Webhook::CACHE_KEY);
        m::close();
    }

    /**
     * Define environment setup.
     *
     * @param  \Illuminate\Foundation\Application  $app
     * @return void
     */
    protected function getEnvironmentSetUp($app)
    {
        // Setup default database to use sqlite :memory:
        $app['config']->set('captain_hook.transformer', function ($eventData) {
            return json_encode($eventData);
        });
        $app['config']->set('captain_hook.listeners', ['eloquent.*']);
        $app['config']->set('captain_hook.log.active', true);
        $app['config']->set('captain_hook.log.storage_quantity', 50);
        $app['config']->set('database.default', 'testing');
        $app['config']->set('queue.driver', 'sync');

        \Schema::create('test_models', function ($table) {
            $table->increments('id');
            $table->string('name');
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function testEloquentEventListenerGetCalled()
    {
        $provider = $this->app->getProvider('Mpociot\\CaptainHook\\CaptainHookServiceProvider');
        $provider->setWebhooks([
            [
                'event' => 'eloquent.saved: TestModel',
                'url' => 'http://foo.baz/hook',
            ],
            [
                'event' => 'eloquent.saved: TestModel',
                'url' => 'http://foo.bar/hook',
            ],
            [
                'event' => 'eloquent.deleted: TestModel',
                'url' => 'http://foo.baz/foo',
            ],
        ]);

        $client = m::mock('GuzzleHttp\\Client');

        $client->shouldReceive('post')
            ->twice();

        $client->shouldReceive('post')
            ->with('http://foo.baz/hook', m::any());

        $client->shouldReceive('post')
            ->with('http://foo.bar/hook', m::any());

        $this->app->instance(GuzzleHttp\Client::class, $client);

        $provider->setClient($client);

        // Trigger eloquent event
        $obj = new TestModel();
        $obj->name = 'Test';
        $obj->save();
    }

    public function testCustomEventListener()
    {
        $provider = $this->app->getProvider('Mpociot\\CaptainHook\\CaptainHookServiceProvider');
        $provider->setListeners([
            'TestEvent',
        ]);
        $provider->setWebhooks([
            [
                'event' => 'TestEvent',
                'url' => 'http://foo.bar/hook',
            ],
        ]);

        $model = new TestModel();
        $model->name = 'Test';

        $client = m::mock('GuzzleHttp\\Client');

        $client->shouldReceive('post')
            ->once()
            ->with('http://foo.bar/hook', ['body' => json_encode(['testModel' => $model]), 'verify' => false, 'timeout' => 10]);

        $provider->setClient($client);
        $this->app->instance(GuzzleHttp\Client::class, $client);

        // Trigger eloquent event
        \Event::fire(new TestEvent($model));
    }

    public function testUsesWebhooksFromCache()
    {
        $webhook = new Webhook();
        $webhook->url = 'http://test.foo/saved';
        $webhook->event = 'eloquent.saved: TestModel';
        $webhook->save();

        $webhook = new Webhook();
        $webhook->url = 'http://test.foo/deleted';
        $webhook->event = 'eloquent.deleted: TestModel';
        $webhook->save();

        $provider = $this->app->getProvider('Mpociot\\CaptainHook\\CaptainHookServiceProvider');
        $this->assertCount(2, $provider->getWebhooks());

        $this->assertTrue(Cache::has(Webhook::CACHE_KEY));
        $this->assertCount(2, Cache::get(Webhook::CACHE_KEY));
    }

    public function testUsesWebhooksFromDatabase()
    {
        $webhook = new Webhook();
        $webhook->url = 'http://test.foo/saved';
        $webhook->event = 'eloquent.saved: TestModel';
        $webhook->save();

        $webhook = new Webhook();
        $webhook->url = 'http://test.bar/saved';
        $webhook->event = 'eloquent.saved: TestModel';
        $webhook->save();

        $webhook = new Webhook();
        $webhook->url = 'http://test.foo/deleted';
        $webhook->event = 'eloquent.deleted: TestModel';
        $webhook->save();

        $client = m::mock('GuzzleHttp\\Client');

        $client->shouldReceive('post')
            ->twice();

        $client->shouldReceive('post')
            ->with('http://test.foo/saved', m::any());

        $client->shouldReceive('post')
            ->with('http://test.bar/saved', m::any());

        $this->app->instance(GuzzleHttp\Client::class, $client);

        $provider = $this->app->getProvider('Mpociot\\CaptainHook\\CaptainHookServiceProvider');
        $provider->setClient($client);

        $obj = new TestModel();
        $obj->name = 'Test';
        $obj->save();
    }

    public function testCanFilterWebhooks()
    {
        $webhook = new Webhook();
        $webhook->tenant_id = 1;
        $webhook->url = 'http://test.foo/saved';
        $webhook->event = 'eloquent.saved: TestModel';
        $webhook->save();

        $webhook = new Webhook();
        $webhook->tenant_id = 2;
        $webhook->url = 'http://test.bar/saved';
        $webhook->event = 'eloquent.saved: TestModel';
        $webhook->save();

        $webhook = new Webhook();
        $webhook->tenant_id = 3;
        $webhook->url = 'http://test.baz/saved';
        $webhook->event = 'eloquent.saved: TestModel';
        $webhook->save();

        $client = m::mock('GuzzleHttp\\Client');

        $client->shouldReceive('post')
            ->once();

        $client->shouldReceive('post')
            ->with('http://test.bar/saved', m::any());

        $this->app->instance(GuzzleHttp\Client::class, $client);

        $config = m::mock('stdClass');
        $config->shouldReceive('get')
            ->with('captain_hook.filter', null)
            ->andReturn(function ($item) {
                return $item->tenant_id == 2;
            });
        $config->shouldReceive('get')
            ->with('captain_hook.transformer')
            ->andReturn(function ($data) {
                return json_encode($data);
            });

        $provider = $this->app->getProvider('Mpociot\\CaptainHook\\CaptainHookServiceProvider');
        $provider->setClient($client);
        $provider->setConfig($config);

        $obj = new TestModel();
        $obj->name = 'Test';
        $obj->save();
    }
}

class TestModel extends \Illuminate\Database\Eloquent\Model
{
    protected $fillable = ['name'];
}

class TestEvent extends \Illuminate\Support\Facades\Event
{
    use SerializesModels;

    public function __construct(TestModel $model)
    {
        $this->testModel = $model;
    }
}
