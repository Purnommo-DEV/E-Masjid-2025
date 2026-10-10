<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

final class GenerateInternalZiswafAccessCode extends Command
{
    protected $signature = 'financial-v2:generate-internal-ziswaf-code {--length=10 : Panjang kode, 8 sampai 10 karakter}';

    protected $description = 'Generate a short cryptographically secure internal ZISWAF access code and its SHA-256 hash';

    public function handle(): int
    {
        $length = filter_var($this->option('length'), FILTER_VALIDATE_INT);
        if (! is_int($length) || $length < 8 || $length > 10) {
            $this->error('Panjang kode harus 8 sampai 10 karakter.');

            return self::FAILURE;
        }

        $alphabet = 'ABCDEFGHJKLMNPQRSTUVWXYZabcdefghijkmnopqrstuvwxyz23456789';
        $code = collect(range(1, $length))->map(fn (): string => $alphabet[random_int(0, strlen($alphabet) - 1)])->implode('');

        $this->warn('Kode berikut hanya ditampilkan sekali. Simpan dan bagikan melalui kanal internal yang aman.');
        $this->line('Kode akses: '.$code);
        $this->newLine();
        $this->line('Simpan nilai berikut sebagai FINANCIAL_INTERNAL_ZISWAF_TOKEN_HASH:');
        $this->line(hash('sha256', $code));
        $this->newLine();
        $this->info('Mengganti hash konfigurasi akan mencabut kode dan sesi lama setelah config cache diperbarui.');

        return self::SUCCESS;
    }
}
