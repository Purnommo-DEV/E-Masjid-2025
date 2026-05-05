<?php
// database/migrations/2026_01_01_000015_fix_qurban_settings_json_values.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        // Perbaiki important_notes
        $notes = DB::table('qurban_settings')
            ->where('key', 'important_notes')
            ->where('masjid_code', masjid())
            ->first();
            
        if ($notes && is_array(json_decode($notes->value, true)) === false) {
            // Jika sudah array, jangan diubah
            // Cek apakah value sudah berupa JSON string
            if (!is_string($notes->value) || json_decode($notes->value) === null) {
                // Value bukan JSON, mungkin sudah array, biarkan saja
                // Atau konversi ke JSON
            }
        }
        
        // Perbaiki faq_items
        $faq = DB::table('qurban_settings')
            ->where('key', 'faq_items')
            ->where('masjid_code', masjid())
            ->first();
    }

    public function down()
    {
        //
    }
};