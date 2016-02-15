<?php

namespace Mpociot\CaptainHook\Commands;

use Exception;
use Illuminate\Console\Command;
use Mpociot\CaptainHook\Webhook;

class AddWebhook extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'hook:add
                            {url : The URL to use for the webhook}
                            {event : The namespaced class name of the event}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Add a new webhook to the system.';

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $hook = new Webhook();
        $hook->url = $this->argument('url');
        $hook->event = $this->argument('event');
        try {
            $hook->save();
            $this->info('The webhook was saved successfully.');
            $this->info('Event: '.$hook->event);
            $this->info('URL: '.$hook->url);
        } catch (Exception $e) {
            $this->error("The webhook couldn't be added to the database ".$e->getMessage());
        }
    }
}
