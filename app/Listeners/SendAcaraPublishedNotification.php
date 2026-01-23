<?php

namespace App\Listeners;

use App\Events\AcaraPublished;
use App\Http\Controllers\PushController;
use App\Models\UserSubscription;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class SendAcaraPublishedNotification
{
    public function handle(AcaraPublished $event): void
    {
        $acara = $event->acara;

        echo "[ACARA] 📢 Publish terdeteksi: {$acara->judul}\n";

        $subs = UserSubscription::all();
        if ($subs->isEmpty()) {
            echo "[ACARA] ⏭️  Tidak ada subscriber\n";
            return;
        }

        // Format waktu
        $tanggal = Carbon::parse($acara->mulai)->translatedFormat('l, d F Y');
        $jam     = Carbon::parse($acara->mulai)->format('H:i');

        $judulNotif = 'Acara Baru di Masjid 🕌';
        $pesanNotif =
            "Acara baru: {$acara->judul}\n" .
            "$tanggal pukul $jam.\n" .
            "Yuk ikuti!";

        echo "[ACARA] 🚀 Kirim notif ke {$subs->count()} user\n";

        foreach ($subs as $sub) {

            $ok = PushController::send(
                $judulNotif,
                $pesanNotif,
                '/acara/' . $acara->slug,
                $sub->endpoint,
                $sub->keys
            );

            echo $ok
                ? "[ACARA] ✅ Sukses\n"
                : "[ACARA] ❌ Gagal\n";

            if (!$ok) {
                $sub->delete();
            }
        }

        Log::info("Notif acara dikirim: {$acara->judul}");
    }
}
