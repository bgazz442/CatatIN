<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('ai_transcriptions')) {
            DB::table('ai_transcriptions')
                ->select(['disk', 'source_path'])
                ->whereNotNull('source_path')
                ->where('source_path', 'like', 'ai/transcriptions/%')
                ->orderBy('id')
                ->each(function (object $transcription): void {
                    $disk = Storage::disk($transcription->disk ?: 'local');
                    if ($disk->exists($transcription->source_path) && ! $disk->delete($transcription->source_path)) {
                        throw new RuntimeException('Gagal menghapus file transkripsi CatatIn AI.');
                    }
                });
        }

        $localDisk = Storage::disk('local');
        if ($localDisk->directoryExists('ai/transcriptions') && ! $localDisk->deleteDirectory('ai/transcriptions')) {
            throw new RuntimeException('Gagal menghapus direktori transkripsi CatatIn AI.');
        }

        if (Schema::hasTable('jobs')) {
            DB::table('jobs')
                ->where('payload', 'like', '%ProcessAiTranscription%')
                ->delete();
        }

        if (Schema::hasTable('failed_jobs')) {
            DB::table('failed_jobs')
                ->where('payload', 'like', '%ProcessAiTranscription%')
                ->delete();
        }

        Schema::dropIfExists('ai_chat_messages');
        Schema::dropIfExists('ai_chat_conversations');
        Schema::dropIfExists('ai_material_chunks');
        Schema::dropIfExists('ai_website_knowledge_chunks');
        Schema::dropIfExists('ai_transcriptions');
    }

    public function down(): void
    {
        throw new RuntimeException('CatatIn AI dan data terkait dihapus secara permanen; migrasi ini tidak dapat dibatalkan.');
    }
};
