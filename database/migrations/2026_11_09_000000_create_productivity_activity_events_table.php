<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('productivity_activity_events', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('entity_type', 24);
            $table->string('event_type', 48);
            $table->unsignedBigInteger('subject_id')->nullable();
            $table->string('summary', 500);
            $table->timestamps();
            $table->index(['user_id', 'created_at'], 'activity_user_created_idx');
            $table->index(['user_id', 'entity_type', 'created_at'], 'activity_user_type_created_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('productivity_activity_events');
    }
};
