<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('catatan_attachments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('catatan_id')->constrained('catatan')->cascadeOnDelete();
            $table->string('disk', 40)->default('learning_private');
            $table->string('path', 500)->unique();
            $table->string('original_name', 255);
            $table->string('extension', 10);
            $table->string('mime_type', 127);
            $table->unsignedBigInteger('size');
            $table->timestamps();
        });

        Schema::create('media_favorites', function (Blueprint $table) {
            $table->id();
            $table->string('title', 180);
            $table->text('url');
            $table->string('platform', 30);
            $table->string('video_id', 32)->nullable();
            $table->foreignId('category_id')->constrained('categories')->restrictOnDelete();
            $table->text('description')->nullable();
            $table->boolean('is_favorite')->default(true);
            $table->timestamps();
            $table->index(['platform', 'category_id']);
            $table->index(['is_favorite', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('media_favorites');
        Schema::dropIfExists('catatan_attachments');
    }
};