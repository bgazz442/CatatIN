<?php

namespace App\Jobs;

use App\Models\AiTranscription;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class ProcessAiTranscription implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(public int $transcriptionId) {}

    public function handle(): void
    {
        $transcription = AiTranscription::findOrFail($this->transcriptionId);
        if ($transcription->status === 'completed' && filled($transcription->transcript)) {
            return;
        }

        $metadata = (array) $transcription->metadata;
        $metadata['transcription_disabled_at'] = now()->toDateTimeString();
        $transcription->update([
            'status' => 'failed',
            'error_message' => 'Fitur transkripsi AI telah dinonaktifkan.',
            'metadata' => $metadata,
        ]);
    }
}
