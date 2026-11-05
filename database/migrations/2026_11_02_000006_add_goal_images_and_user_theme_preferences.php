<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('goals', function (Blueprint $table) {
            $table->string('image_path')->nullable();
        });

        Schema::create('user_preferences', function (Blueprint $table) {
            $table->foreignId('user_id')->primary()->constrained()->cascadeOnDelete();
            $table->char('accent_color', 7)->default('#2F6F64');
            $table->char('background_color', 7)->default('#F8FAFC');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_preferences');

        Schema::table('goals', function (Blueprint $table) {
            $table->dropColumn('image_path');
        });
    }
};
