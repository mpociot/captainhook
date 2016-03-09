<?php

namespace Mpociot\CaptainHook\Jobs;

use GuzzleHttp\Client;
use GuzzleHttp\Middleware;
use GuzzleHttp\Promise\PromiseInterface;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Bus\SelfHandling;
use Mpociot\CaptainHook\WebhookLog;
use Illuminate\Queue\SerializesModels;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;

class TriggerWebhooksJob implements SelfHandling, ShouldQueue
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
     * @param array|\Illuminate\Support\Collection $webhooks
     * @param mixed $eventData
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

        $logging = $config->get('captain_hook.log.active');

        foreach ($this->webhooks as $webhook) {
            if ($logging) {
                if ($config->get('captain_hook.log.storage_quantity') != -1 &&
                    $webhook->logs()->count() >= $config->get('captain_hook.log.storage_quantity')) {
                    $webhook->logs()->orderBy('updated_at', 'desc')->first()->delete();
                }
                $log = new WebhookLog([
                    'webhook_id' => $webhook[ 'id' ],
                    'url' => $webhook[ 'url' ],
                ]);
                $middleware = Middleware::tap(function (RequestInterface $request, $options) use ($log) {
                    $log->payload_format = isset($request->getHeader('Content-Type')[0]) ? $request->getHeader('Content-Type')[0] : null;
                    $log->payload = $request->getBody()->getContents();
                }, function ($request, $options, PromiseInterface $response) use ($log) {
                    $response->then(function (ResponseInterface $response) use ($log) {
                        $log->status = $response->getStatusCode();
                        $log->response = $response->getBody()->getContents();
                        $log->response_format = $log->payload_format = isset($response->getHeader('Content-Type')[0]) ? $response->getHeader('Content-Type')[0] : null;

                        $log->save();

                        // Retry this job if the webhook response didn't give us a HTTP 200 OK
                        if ($response->getStatusCode() != 200) {
                            $this->release(30);
                        }
                    });
                });

                $client->post($webhook[ 'url' ], [
                    'exceptions' => false,
                    'body' => $this->eventData,
                    'verify' => false,
                    'handler' => $middleware($client->getConfig('handler')),
                ]);
            } else {
                $client->post($webhook[ 'url' ], [
                    'exceptions' => false,
                    'body' => $this->eventData,
                    'verify' => false,
                    'timeout' => 10,
                ]);
            }
        }
    }
}
