<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use App\Services\LegacyCategoryBackfill;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('categories', function (Blueprint $table) {
            $table->id();
            $table->string('name', 100)->unique();
            $table->timestamps();
        });

        $defaultCategories = [
            'Matematika',
            'Bahasa Indonesia',
            'Bahasa Inggris',
            'Pemrograman',
            'Basis Data',
            'Jaringan Komputer',
            'Lainnya',
        ];
        foreach ($defaultCategories as $name) {
            DB::table('categories')->insertOrIgnore([
                'name' => $name,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        Schema::table('catatan', function (Blueprint $table) {
            $table->foreignId('category_id')
                ->nullable()
                ->after('kategori')
                ->constrained('categories')
                ->restrictOnDelete();
        });

        app(LegacyCategoryBackfill::class)->run();
    }

    public function down(): void
    {
        if (Schema::hasColumn('catatan', 'category_id')) {
            Schema::table('catatan', function (Blueprint $table) {
                $table->dropConstrainedForeignId('category_id');
            });
        }

        Schema::dropIfExists('categories');
    }
};