<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ai_website_knowledge_chunks', function (Blueprint $table) {
            $table->id();
            $table->string('source_key', 180);
            $table->string('feature', 100);
            $table->string('route', 255)->nullable();
            $table->string('visibility', 20)->default('public');
            $table->string('version', 20)->default('1.0');
            $table->date('last_updated')->nullable();
            $table->unsignedSmallInteger('chunk_index');
            $table->longText('content');
            $table->json('embedding')->nullable();
            $table->char('content_hash', 64);
            $table->char('source_hash', 64);
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->unique(['source_key', 'chunk_index']);
            $table->index(['feature', 'visibility']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ai_website_knowledge_chunks');
    }
};
