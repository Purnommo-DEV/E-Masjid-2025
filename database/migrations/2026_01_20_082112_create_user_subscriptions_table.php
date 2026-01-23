<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('user_subscriptions', function (Blueprint $table) {
            $table->id();
            $table->text('endpoint');
            $table->json('keys');
            $table->foreignId('user_id')->nullable()->constrained()->onDelete('cascade');
            $table->string('zona_waktu')->nullable()->default('WIB');
            $table->string('kota')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_subscriptions');
    }
};