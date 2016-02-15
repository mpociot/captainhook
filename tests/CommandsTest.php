<?php

use Mockery as m;

class CommandsTest extends Orchestra\Testbench\TestCase
{
    public function setUp()
    {
        parent::setUp();

        $this->artisan('migrate', [
            '--database' => 'testing',
            '--realpath' => realpath(__DIR__.'/../src/database'),
        ]);
    }

    public function testCannotAddWebhookWithoutName()
    {
        $cmd = m::mock('\\Mpociot\\CaptainHook\\Commands\\AddWebhook[argument,error]');

        $cmd->shouldReceive('error')
            ->once()
            ->with(m::type('string'));

        $cmd->shouldReceive('argument')
            ->twice();

        $cmd->shouldReceive('argument')
            ->with('url')
            ->andReturn('http://foo.bar');

        $cmd->handle();

        $this->assertTrue(true);
    }

    public function testCanAddWebhook()
    {
        $cmd = m::mock('\\Mpociot\\CaptainHook\\Commands\\AddWebhook[argument,info]');

        $cmd->shouldReceive('argument')
            ->with('url')
            ->andReturn('http://foo.bar');

        $cmd->shouldReceive('argument')
            ->with('event')
            ->andReturn('TestModelTestModel');

        $cmd->shouldReceive('info')
            ->with(m::type('string'));

        $cmd->handle();

        $this->seeInDatabase('webhooks', [
            'event' => 'TestModelTestModel',
            'url' => 'http://foo.bar',
        ]);
    }

    public function testCannotDeleteWebhookWithWrongID()
    {
        $webhook = \Mpociot\CaptainHook\Webhook::create([
            'url' => 'http://foo.baz',
            'event' => 'DeleteWebhook',
        ]);
        $cmd = m::mock('\\Mpociot\\CaptainHook\\Commands\\DeleteWebhook[argument,error]');

        $cmd->shouldReceive('argument')
            ->with('id')
            ->andReturn(null);

        $cmd->shouldReceive('error')
            ->with(m::type('string'));

        $cmd->handle();

        $this->seeInDatabase('webhooks', [
            'url' => 'http://foo.baz',
            'event' => 'DeleteWebhook',
        ]);
    }

    public function testCanDeleteWebhook()
    {
        $webhook = \Mpociot\CaptainHook\Webhook::create([
           'url' => 'http://foo.baz',
           'event' => 'DeleteWebhook',
        ]);
        $cmd = m::mock('\\Mpociot\\CaptainHook\\Commands\\DeleteWebhook[argument,info]');

        $cmd->shouldReceive('argument')
            ->with('id')
            ->andReturn($webhook->getKey());

        $cmd->shouldReceive('info')
            ->with(m::type('string'));

        $cmd->handle();

        $this->notSeeInDatabase('webhooks', [
            'url' => 'http://foo.baz',
            'event' => 'DeleteWebhook',
        ]);
    }
}
