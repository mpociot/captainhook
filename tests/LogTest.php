<?php

use Mockery as m;
use Mpociot\CaptainHook\Webhook;

class LogTest extends Orchestra\Testbench\TestCase
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

        \Schema::create('log_test_models', function ($table) {
            $table->increments('id');
            $table->string('name');
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function testItDoesNotLogTriggeredWebhooks()
    {
        $this->app['config']->set('captain_hook.log.active', false);
        $provider = $this->app->getProvider('Mpociot\\CaptainHook\\CaptainHookServiceProvider');

        $webhook = new Webhook();
        $webhook->url = 'http://test.foo/saved';
        $webhook->event = 'eloquent.saved: LogTestModel';
        $webhook->save();

        $webhook = new Webhook();
        $webhook->url = 'http://test.bar/saved';
        $webhook->event = 'eloquent.saved: LogTestModel';
        $webhook->save();

        $webhook = new Webhook();
        $webhook->url = 'http://test.foo/deleted';
        $webhook->event = 'eloquent.deleted: LogTestModel';
        $webhook->save();

        $handler = new \GuzzleHttp\Handler\MockHandler([
            new \GuzzleHttp\Psr7\Response(200, [
                'Content-Type' => 'application/json',
            ], '{"data":"First data"}'),
            new \GuzzleHttp\Psr7\Response(200, [
                'Content-Type' => 'application/json',
            ], '{"data":"Second data"}'),
        ]);

        $handler = \GuzzleHttp\HandlerStack::create($handler);

        $client = new \GuzzleHttp\Client(['handler' => $handler]);

        $this->app->instance(GuzzleHttp\Client::class, $client);

        $provider->setClient($client);

        $test = new LogTestModel();
        $test->name = 'Test';
        $test->save();

        $this->notSeeInDatabase('webhook_logs', [
            'webhook_id' => 1,
            'url' => 'http://test.foo/saved',
            'response' => '{"data":"First data"}',
            'response_format' => 'application/json',
        ]);

        $this->notSeeInDatabase('webhook_logs', [
            'webhook_id' => 2,
            'url' => 'http://test.bar/saved',
            'response' => '{"data":"Second data"}',
            'response_format' => 'application/json',
        ]);
    }

    public function testItLogsTriggeredWebhooks()
    {
        $provider = $this->app->getProvider('Mpociot\\CaptainHook\\CaptainHookServiceProvider');

        $webhook = new Webhook();
        $webhook->url = 'http://test.foo/saved';
        $webhook->event = 'eloquent.saved: LogTestModel';
        $webhook->save();

        $webhook = new Webhook();
        $webhook->url = 'http://test.bar/saved';
        $webhook->event = 'eloquent.saved: LogTestModel';
        $webhook->save();

        $webhook = new Webhook();
        $webhook->url = 'http://test.foo/deleted';
        $webhook->event = 'eloquent.deleted: LogTestModel';
        $webhook->save();

        $handler = new \GuzzleHttp\Handler\MockHandler([
            new \GuzzleHttp\Psr7\Response(200, [
                'Content-Type' => 'application/json',
            ], '{"data":"First data"}'),
            new \GuzzleHttp\Psr7\Response(200, [
                'Content-Type' => 'application/json',
            ], '{"data":"Second data"}'),
        ]);

        $handler = \GuzzleHttp\HandlerStack::create($handler);

        $client = new \GuzzleHttp\Client(['handler' => $handler]);

        $this->app->instance(GuzzleHttp\Client::class, $client);

        $provider->setClient($client);

        $test = new LogTestModel();
        $test->name = 'Test';
        $test->save();

        $this->seeInDatabase('webhook_logs', [
            'webhook_id' => 1,
            'url' => 'http://test.foo/saved',
            'response' => '{"data":"First data"}',
            'response_format' => 'application/json',
        ]);

        $this->seeInDatabase('webhook_logs', [
            'webhook_id' => 2,
            'url' => 'http://test.bar/saved',
            'response' => '{"data":"Second data"}',
            'response_format' => 'application/json',
        ]);
    }

    public function testItRemovesWebhooksAfterMaxCount()
    {
        $this->app['config']->set('captain_hook.log.storage_quantity', 1);
        $webhook = new Webhook();
        $webhook->url = 'http://test.foo/saved';
        $webhook->event = 'eloquent.saved: LogTestModel';
        $webhook->save();

        $handler = new \GuzzleHttp\Handler\MockHandler([
            new \GuzzleHttp\Psr7\Response(200, [
                'Content-Type' => 'application/json',
            ], '{"data":"First data"}'),
        ]);

        $handler = \GuzzleHttp\HandlerStack::create($handler);

        $client = new \GuzzleHttp\Client(['handler' => $handler]);

        $this->app->instance(GuzzleHttp\Client::class, $client);

        $log = new \Mpociot\CaptainHook\WebhookLog([
            'webhook_id' => $webhook->id,
            'url' => 'anything',
            'payload_format' => null,
            'payload' => '',
            'status' => 200,
            'response' => '',
            'response_format' => null,
        ]);
        $log->save();

        $log = new \Mpociot\CaptainHook\WebhookLog([
            'webhook_id' => null,
            'url' => 'anything',
            'payload_format' => null,
            'payload' => '',
            'status' => 200,
            'response' => '',
            'response_format' => null,
        ]);
        $log->save();

        $this->seeInDatabase('webhook_logs', [
            'webhook_id' => $webhook->id,
            'url' => 'anything',
            'payload_format' => null,
            'payload' => '',
            'status' => 200,
            'response' => '',
            'response_format' => null,
        ]);

        // Create a log for a null webhook to ensure it doesn't
        // get deleted.
        $this->seeInDatabase('webhook_logs', [
            'webhook_id' => null,
            'url' => 'anything',
            'payload_format' => null,
            'payload' => '',
            'status' => 200,
            'response' => '',
            'response_format' => null,
        ]);

        $test = new LogTestModel(['name' => 'Test']);
        $test->save();

        $this->dontSeeInDatabase('webhook_logs', [
            'webhook_id' => $webhook->id,
            'url' => 'anything',
            'payload_format' => null,
            'payload' => '',
            'status' => 200,
            'response' => '',
            'response_format' => null,
        ]);

        $this->seeInDatabase('webhook_logs', [
            'webhook_id' => $webhook->id,
            'url' => $webhook->url,
            'status' => 200,
            'response_format' => 'application/json',
            'response' => '{"data":"First data"}',
        ]);

        // Make sure our null webhook didn't get deleted
        $this->seeInDatabase('webhook_logs', [
            'webhook_id' => null,
            'url' => 'anything',
            'payload_format' => null,
            'payload' => '',
            'status' => 200,
            'response' => '',
            'response_format' => null,
        ]);
    }



    public function testResponseCallbackReceivesWebhookAndResponse()
    {
        $provider = $this->app->getProvider('Mpociot\\CaptainHook\\CaptainHookServiceProvider');

        $webhook = new Webhook();
        $webhook->url = 'http://test.foo/saved';
        $webhook->event = 'eloquent.saved: LogTestModel';
        $webhook->save();

        $checkWebhook = Webhook::find($webhook->getKey());

        $handler = new \GuzzleHttp\Handler\MockHandler([
            new \GuzzleHttp\Psr7\Response(200, [
                'Content-Type' => 'application/json',
            ], '{"data":"First data"}')
        ]);

        $mock = \Mockery::mock('stdClass');
        $mock->shouldReceive('callback')
            ->once()
            ->withArgs([
                $checkWebhook->toArray(),
                Mockery::type('Psr\Http\Message\ResponseInterface')
            ]);

        $this->app['config']->set('captain_hook.response_callback', (function($webhook, $response) use ($mock){
            return $mock->callback($webhook->toArray(), $response);
        }));

        $handler = \GuzzleHttp\HandlerStack::create($handler);

        $client = new \GuzzleHttp\Client(['handler' => $handler]);

        $this->app->instance(GuzzleHttp\Client::class, $client);

        $provider->setClient($client);

        $test = new LogTestModel();
        $test->name = 'Test';
        $test->save();
    }


}

class LogTestModel extends \Illuminate\Database\Eloquent\Model
{
    protected $fillable = ['name'];
}
