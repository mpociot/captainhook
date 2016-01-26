<?php
namespace Mpociot\CaptainHook\Jobs;


use Carbon\Carbon;
use GuzzleHttp\Client;
use GuzzleHttp\Middleware;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Mpociot\CaptainHook\CaptainHookLog;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;

class TriggerWebhooksJob implements ShouldQueue
{
    use Queueable, InteractsWithQueue, SerializesModels;


    /**
     * All the webhooks that should be executed.
     *
     * @var array|\Illuminate\Support\Collection
     */
    protected $webhooks;

    /**
     * The event data to be posted to our hooks.
     *
     * @var mixed
     */
    protected $eventData;

    /**
     * Create a new job instance.
     *
     * @param array|\Illuminate\Support\Collection $wekbhooks
     * @param $eventData
     */
    public function __construct($webhooks, $eventData)
    {
        $this->eventData = $eventData;
        $this->webhooks = $webhooks;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $config = app('Illuminate\Contracts\Config\Repository');
        $client = app(Client::class);

        if (($logging = $config->get('captain_hook.log.active') && $config->get('queue.driver') != 'sync') &&
            $config->get('captain_hook.log.storage_time') != -1) {
            CaptainHookLog::where('updated_at', '<', Carbon::now()->subHours($config->get('captain_hook.log.storage_time')))->delete();
        }
        foreach ($this->webhooks as $webhook) {
            if ($logging) {
                $log = new CaptainHookLog([
                    'webhook_id'     => $webhook[ 'id' ],
                    'url'            => $webhook[ 'url' ],
                ]);
                $middleware = Middleware::tap(function (Request $request) use ($log) {
                    $log->payload_format  = $request->getHeader('Content-Type');
                    $log->payload         = $request->getBody();
                }, function (Response $response) use ($log) {
                    $log->status          = $response->getStatusCode();
                    $log->response        = $response->getBody();
                    $log->response_format = $response->getHeader('Content-Type');

                    $log->save();
                });

                $client->post($webhook[ 'url' ], [
                    'body'    => $this->eventData,
                    'handler' => $logging ? $middleware : Middleware::httpErrors(),
                ]);
            } else {
                $client->postAsync($webhook[ 'url' ], [
                    'body'   => $this->eventData,
                    'verify' => false,
                    'future' => true,
                ]);
            }
        }
    }
}