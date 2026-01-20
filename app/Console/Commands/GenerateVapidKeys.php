<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Minishlink\WebPush\VAPID;

class GenerateVapidKeys extends Command
{
    protected $signature = 'vapid:generate';
    protected $description = 'Generate VAPID keys for Web Push';

    public function handle()
    {
        $vapidKeys = VAPID::createVapidKeys();

        $this->info('VAPID Public Key: ' . $vapidKeys['publicKey']);
        $this->info('VAPID Private Key: ' . $vapidKeys['privateKey']);

        $this->info("\nTambahkan ke .env:");
        $this->info("VAPID_PUBLIC_KEY={$vapidKeys['publicKey']}");
        $this->info("VAPID_PRIVATE_KEY={$vapidKeys['privateKey']}");
    }
}