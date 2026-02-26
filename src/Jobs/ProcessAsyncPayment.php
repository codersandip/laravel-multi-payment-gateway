<?php

namespace VendorName\MultiPayment\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use VendorName\MultiPayment\Facades\MultiPayment;
use Illuminate\Support\Facades\Log;
use Exception;

class ProcessAsyncPayment implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $method;
    public $parameters;

    /**
     * Create a new job instance.
     *
     * @param string $method
     * @param array $parameters
     */
    public function __construct(string $method, array $parameters)
    {
        $this->method = $method;
        $this->parameters = $parameters;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        try {
            $method = $this->method;
            MultiPayment::$method(...$this->parameters);
        } catch (Exception $e) {
            Log::channel(config('multi-payment.logging.channel', 'daily'))
                ->error('Async Payment Job Failed: ' . $e->getMessage());
            throw $e;
        }
    }
}
