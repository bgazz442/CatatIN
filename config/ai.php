<?php

return [
    'whisper' => [
        'binary' => env('WHISPER_BIN', 'C:\\tools\\whisper.cpp\\build\\bin\\whisper-cli.exe'),
        'model' => env('WHISPER_MODEL', 'C:\\tools\\whisper.cpp\\ggml-base.bin'),
        'ffmpeg' => env('FFMPEG_BIN', 'C:\\Users\\BAGAS\\AppData\\Local\\Microsoft\\WinGet\\Packages\\Gyan.FFmpeg_Microsoft.Winget.Source_8wekyb3d8bbwe\\ffmpeg-9.0.2-full_build\\bin\\ffmpeg.exe'),
        'ffprobe' => env('FFPROBE_BIN', 'C:\\Users\\BAGAS\\AppData\\Local\\Microsoft\\WinGet\\Packages\\Gyan.FFmpeg_Microsoft.Winget.Source_8wekyb3d8bbwe\\ffmpeg-9.0.2-full_build\\bin\\ffprobe.exe'),
        'supported_audio' => ['mp3', 'wav', 'm4a', 'flac', 'ogg'],
        'supported_video' => ['mp4', 'mov', 'webm'],
    ],
    'transcription' => [
        'disk' => env('AI_TRANSCRIPTION_DISK', 'local'),
        'temp_dir' => env('AI_TRANSCRIPTION_TEMP_DIR', 'ai/transcriptions/temp'),
        'downloader' => env('AI_YTDLP_BIN', 'yt-dlp'),
    ],
];
