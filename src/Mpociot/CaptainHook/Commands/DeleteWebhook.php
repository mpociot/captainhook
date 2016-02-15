<?php

namespace Mpociot\CaptainHook\Commands;

use Illuminate\Console\Command;
use Mpociot\CaptainHook\Webhook;

class DeleteWebhook extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'hook:delete
                            {id : The ID of the webhook that should be deleted}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Remove an existing webhook from the system.';

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $id = $this->argument('id');
        $hook = Webhook::find($id);
        if ($hook === null) {
            $this->error('Webhook with ID '.$id.' could not be found.');
        } else {
            $hook->delete();
            $this->info('The webhook was deleted successfully.');
        }
    }
}
