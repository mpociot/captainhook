<?php

namespace Mpociot\CaptainHook\Commands;

use Illuminate\Console\Command;
use Mpociot\CaptainHook\Webhook;

class ListWebhooks extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'hook:list';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'List all installed webhooks.';

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $all = Webhook::select('id', 'tenant_id', 'url', 'event')->get();
        $this->table(['id', 'tenant_id', 'url', 'event'], $all->toArray());
    }
}
