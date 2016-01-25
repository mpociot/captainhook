<?php
namespace Mpociot\CaptainHook\Jobs;


use GuzzleHttp\Client;
use GuzzleHttp\Middleware;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Mpociot\CaptainHook\CaptainHookLog;

class TriggerWebhooksJob implements ShouldQueue
{
    use Queueable, InteractsWithQueue, SerializesModels;


    /**
     * All the webhooks that should be executed.
     *
     * @var array
     */
    protected $webhooks;

    /**
     * The event data to be posted to our hooks.
     *
     * @var mixed
     */
    protected $eventData;

    /**
     * @var \GuzzleHttp\Client
     */
    protected $client;

    /**
     * @var \Illuminate\Contracts\Config\Repository
     */
    protected $config;

    /**
     * Create a new job instance.
     *
     * @param array $wekbhooks
     * @param $eventData
     */
    public function __construct(array $wekbhooks, $eventData)
    {
        $this->client = new Client();
        $this->config = app('Illuminate\Contracts\Config\Repository');
        $this->eventData = $eventData;
        $this->webhooks = $wekbhooks;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $logging = $this->config->get('captain_hook.log.active');
        foreach ($this->webhooks as $webhook) {
            $logData = [
                'webhook_id'     => $webhook[ 'id' ],
                'url'            => $webhook[ 'url' ],
            ];

            $middleware = Middleware::tap(function (Request $request) use ($logData) {
                $logData['payload_format'] = $request->getHeader('Content-Type');
                $logData['payload'] = $request->getBody();
            }, function (Response $response) use ($logData) {
                $logData['status'] = $response->getStatusCode();
                $logData['response'] = $response->getBody();
                $logData['response_format'] = $response->getHeader('Content-Type');

                CaptainHookLog::create($logData);
            });

            $this->client->post($webhook[ 'url' ], [
                'body' => $this->eventData,
                'handler' => $logging ? $middleware : Middleware::httpErrors(),
            ]);
        }
    }
}