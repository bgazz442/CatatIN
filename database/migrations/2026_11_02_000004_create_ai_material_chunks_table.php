<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ai_material_chunks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('source_type', 30);
            $table->unsignedBigInteger('source_id');
            $table->unsignedSmallInteger('chunk_index');
            $table->longText('content');
            $table->json('embedding');
            $table->json('metadata')->nullable();
            $table->char('content_hash', 64);
            $table->timestamps();
            $table->unique(['user_id', 'source_type', 'source_id', 'chunk_index'], 'ai_chunks_source_unique');
            $table->index(['user_id', 'source_type', 'source_id'], 'ai_chunks_owner_source_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ai_material_chunks');
    }
};
