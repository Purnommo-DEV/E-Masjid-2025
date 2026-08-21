<?php

namespace Tests\Feature;

use Tests\TestCase;

class PushServiceWorkerAssetTest extends TestCase
{
    public function test_the_registered_push_service_worker_asset_exists(): void
    {
        $path = public_path('push-sw.js');

        $this->assertFileExists($path);
        $this->assertStringContainsString("addEventListener('push'", (string) file_get_contents($path));
        $this->assertStringContainsString("addEventListener('notificationclick'", (string) file_get_contents($path));
    }
}
