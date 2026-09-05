<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('financial_v2_attachments', function (Blueprint $table): void {
            $table->string('source_media_type', 120)->nullable()->after('media_type');
            $table->unsignedBigInteger('source_byte_size')->nullable()->after('byte_size');
            $table->unsignedInteger('image_width')->nullable()->after('source_byte_size');
            $table->unsignedInteger('image_height')->nullable()->after('image_width');
        });
    }

    public function down(): void
    {
        Schema::table('financial_v2_attachments', function (Blueprint $table): void {
            $table->dropColumn(['source_media_type', 'source_byte_size', 'image_width', 'image_height']);
        });
    }
};
