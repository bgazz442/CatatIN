<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('ai_website_knowledge_chunks')) {
            DB::table('ai_website_knowledge_chunks')
                ->where('source_key', 'website/daily-activities.md')
                ->delete();
        }

        if (Schema::hasTable('ai_chat_messages')) {
            DB::table('ai_chat_messages')
                ->whereNotNull('source_refs')
                ->orderBy('id')
                ->chunkById(100, function ($messages): void {
                    foreach ($messages as $message) {
                        $sources = json_decode($message->source_refs, true);
                        if (! is_array($sources)) {
                            continue;
                        }

                        $filtered = array_values(array_filter(
                            $sources,
                            fn ($source): bool => ! $this->isDailyActivitySource($source)
                        ));

                        if (count($filtered) !== count($sources)) {
                            DB::table('ai_chat_messages')
                                ->where('id', $message->id)
                                ->update(['source_refs' => json_encode($filtered, JSON_THROW_ON_ERROR)]);
                        }
                    }
                });
        }

        Schema::dropIfExists('daily_activities');
    }

    public function down(): void
    {
        if (! Schema::hasTable('daily_activities')) {
            Schema::create('daily_activities', function (Blueprint $table): void {
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
        }
    }

    private function isDailyActivitySource(mixed $source): bool
    {
        if (! is_array($source)) {
            return false;
        }

        if (in_array($source['type'] ?? null, ['aktivitas', 'activity', 'daily_activity', 'daily_activities'], true)) {
            return true;
        }

        $path = parse_url((string) ($source['link'] ?? ''), PHP_URL_PATH);

        return is_string($path) && preg_match('~(?:^|/)aktivitas(?:/|$)~u', $path) === 1;
    }
};
