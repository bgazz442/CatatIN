<?php

return [
    'base_url' => env('OLLAMA_BASE_URL', 'http://127.0.0.1:11434'),
    'model' => env('OLLAMA_MODEL', 'qwen3:1.7b'),
    'embedding_model' => env('OLLAMA_EMBEDDING_MODEL', 'nomic-embed-text'),
    'timeout' => (int) env('OLLAMA_TIMEOUT', 120),
    'connect_timeout' => (int) env('OLLAMA_CONNECT_TIMEOUT', 15),
    'keep_alive' => env('OLLAMA_KEEP_ALIVE', '15m'),
    'temperature' => (float) env('OLLAMA_TEMPERATURE', 0.2),
    'top_p' => (float) env('OLLAMA_TOP_P', 0.8),
    'context_length' => (int) env('OLLAMA_CONTEXT_LENGTH', 8192),
    'max_output_tokens' => (int) env('OLLAMA_MAX_OUTPUT_TOKENS', 1024),
    'repeat_penalty' => (float) env('OLLAMA_REPEAT_PENALTY', 1.1),
];
