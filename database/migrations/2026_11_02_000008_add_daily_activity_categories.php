<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('categories')) {
            return;
        }

        foreach ([
            'Akademik',
            'Proyek',
            'Pengembangan Diri',
            'Organisasi',
            'Olahraga',
            'Lainnya',
        ] as $name) {
            DB::table('categories')->insertOrIgnore([
                'name' => $name,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    public function down(): void
    {
        // Keep shared category data intact for notes and other productivity features.
    }
};
