<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tasks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('category_id')->nullable()->constrained()->nullOnDelete();
            $table->string('title', 180);
            $table->text('description')->nullable();
            $table->date('due_date')->nullable();
            $table->string('status', 20)->default('pending');
            $table->timestamps();
            $table->index(['user_id', 'status', 'due_date']);
            $table->index(['user_id', 'category_id']);
        });

        Schema::create('daily_activities', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('category_id')->nullable()->constrained()->nullOnDelete();
            $table->string('title', 180);
            $table->text('description')->nullable();
            $table->date('activity_date');
            $table->time('start_time')->nullable();
            $table->time('end_time')->nullable();
            $table->timestamps();
            $table->index(['user_id', 'activity_date']);
            $table->index(['user_id', 'category_id', 'activity_date']);
        });

        Schema::create('goals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('name', 180);
            $table->text('description')->nullable();
            $table->decimal('target_value', 14, 2);
            $table->decimal('current_value', 14, 2)->default(0);
            $table->string('unit', 40);
            $table->date('start_date');
            $table->date('deadline')->nullable();
            $table->string('status', 20)->default('active');
            $table->timestamps();
            $table->unique(['id', 'user_id']);
            $table->index(['user_id', 'status', 'deadline']);
        });

        Schema::create('goal_progress_entries', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('goal_id');
            $table->unsignedBigInteger('user_id');
            $table->decimal('amount', 14, 2);
            $table->string('note', 500)->nullable();
            $table->timestamp('recorded_at');
            $table->timestamps();
            $table->foreign(['goal_id', 'user_id'])
                ->references(['id', 'user_id'])
                ->on('goals')
                ->cascadeOnDelete();
            $table->index(['user_id', 'recorded_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('goal_progress_entries');
        Schema::dropIfExists('goals');
        Schema::dropIfExists('daily_activities');
        Schema::dropIfExists('tasks');
    }
};
