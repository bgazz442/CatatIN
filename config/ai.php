<?php

return [
    'whisper' => [
        'binary' => env('WHISPER_BIN', 'whisper-cli'),
        'model' => env('WHISPER_MODEL'),
        'ffmpeg' => env('FFMPEG_BIN', 'ffmpeg'),
        'ffprobe' => env('FFPROBE_BIN', 'ffprobe'),
        'supported_audio' => ['mp3', 'wav', 'm4a', 'flac', 'ogg'],
        'supported_video' => ['mp4', 'mov', 'webm'],
    ],
    'transcription' => [
        'disk' => env('AI_TRANSCRIPTION_DISK', 'local'),
        'temp_dir' => env('AI_TRANSCRIPTION_TEMP_DIR', 'ai/transcriptions/temp'),
        'downloader' => env('AI_YTDLP_BIN', 'yt-dlp'),
    ],
];
