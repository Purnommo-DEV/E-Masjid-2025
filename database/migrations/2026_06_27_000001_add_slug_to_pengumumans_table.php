<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('pengumumans', 'slug')) {
            Schema::table('pengumumans', function (Blueprint $table) {
                $table->string('slug')->nullable()->after('judul');
            });
        }

        $usedSlugs = [];

        DB::table('pengumumans')
            ->select(['id', 'judul', 'slug'])
            ->orderBy('id')
            ->get()
            ->each(function ($pengumuman) use (&$usedSlugs) {
                $baseSlug = Str::slug($pengumuman->judul) ?: 'pengumuman';
                $slug = $pengumuman->slug ?: $baseSlug;
                $counter = 2;

                while (in_array($slug, $usedSlugs, true)) {
                    $slug = "{$baseSlug}-{$counter}";
                    $counter++;
                }

                $usedSlugs[] = $slug;

                DB::table('pengumumans')
                    ->where('id', $pengumuman->id)
                    ->update(['slug' => $slug]);
            });

        Schema::table('pengumumans', function (Blueprint $table) {
            $table->unique('slug');
        });
    }

    public function down(): void
    {
        if (! Schema::hasColumn('pengumumans', 'slug')) {
            return;
        }

        Schema::table('pengumumans', function (Blueprint $table) {
            $table->dropUnique(['slug']);
            $table->dropColumn('slug');
        });
    }
};
