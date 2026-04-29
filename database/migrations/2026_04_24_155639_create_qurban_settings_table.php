<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('qurban_settings', function (Blueprint $table) {
            $table->id();
            $table->string('masjid_code', 50);
            $table->string('key', 100);
            $table->text('value')->nullable();
            $table->string('type', 20)->default('text');
            $table->string('label')->nullable();
            $table->integer('urutan')->default(0);
            $table->timestamps();
            
            $table->unique(['masjid_code', 'key']);
            $table->index(['masjid_code', 'key']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('qurban_settings');
    }
};