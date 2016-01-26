<?php
namespace Mpociot\CaptainHook\Jobs;


use Carbon\Carbon;
use GuzzleHttp\Client;
use GuzzleHttp\Middleware;
use Illuminate\Bus\Queueable;
use GuzzleHttp\Promise\Promise;
use Psr\Http\Message\RequestInterface;
use Illuminate\Queue\SerializesModels;
use Psr\Http\Message\ResponseInterface;
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
                $middleware = Middleware::tap(function (RequestInterface $request, $options) use ($log) {
                    $log->payload_format  = isset($request->getHeader('Content-Type')[0]) ? $request->getHeader('Content-Type')[0] : null;
                    $log->payload         = $request->getBody()->getContents();
                }, function ($request, $options, Promise $response) use ($log) {
                    $response->then(function (ResponseInterface $response) use ($log) {
                        $log->status          = $response->getStatusCode();
                        $log->response        = $response->getBody()->getContents();
                        $log->response_format = $log->payload_format  = isset($response->getHeader('Content-Type')[0]) ? $response->getHeader('Content-Type')[0] : null;

                        $log->save();
                    });
                });

                $client->post($webhook[ 'url' ], [
                    'body'    => $this->eventData,
                    'handler' => $middleware($client->getConfig('handler')),
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