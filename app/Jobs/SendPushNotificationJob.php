<?php

namespace App\Jobs;

use App\Http\Controllers\PushController;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class SendPushNotificationJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public int $timeout = 15;

    public function __construct(
        public string $title,
        public string $message,
        public string $url,
        public string $endpoint,
        public array  $keys
    ) {}

    public function handle(): void
    {
        try {
        
            $ok = PushController::send(
                $this->title,
                $this->message,
                $this->url,
                $this->endpoint,
                $this->keys
            );

            if (!$ok) {
                Log::warning('Push gagal (job)', [
                    'endpoint' => $this->endpoint
                ]);
            }

        } catch (\Throwable $e) {
            Log::error('PUSH FAILED', [
                'error' => $e->getMessage(),
                'endpoint' => $this->endpoint,
            ]);
        }
    }

    public function failed(\Throwable $e): void
    {
        Log::error('Push job failed', [
            'endpoint' => $this->endpoint,
            'error' => $e->getMessage()
        ]);
    }
}
