<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('user_subscriptions', function (Blueprint $table) {
            // HAPUS unique / index lama
            $table->dropUnique(['endpoint']); 
            // atau jika namanya custom:
            // $table->dropIndex('user_subscriptions_endpoint_index');

            // pastikan TEXT
            $table->text('endpoint')->change();
        });
    }

    public function down(): void
    {
        Schema::table('user_subscriptions', function (Blueprint $table) {
            $table->string('endpoint', 255)->unique()->change();
        });
    }
};
