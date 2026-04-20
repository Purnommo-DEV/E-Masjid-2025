<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('feedback', function (Blueprint $table) {
            $table->id();
            
            $table->string('nama')->nullable();                    // Nama opsional
            $table->text('saran');                                 // Komentar/Saran (wajib)
            
            $table->string('program')->default('kesehatan');       // Untuk identifikasi program (bisa dikembangkan nanti)
            $table->string('ip_address')->nullable();              // Optional: untuk tracking
            $table->string('user_agent')->nullable();              // Optional: info browser
            
            $table->timestamps();                                  // created_at & updated_at
        });
    }

    public function down()
    {
        Schema::dropIfExists('feedback');
    }
};