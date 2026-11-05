<?php

namespace App\Services\AI;

use Illuminate\Support\Facades\Http;
use RuntimeException;

class OllamaService
{
    public function __construct(
        protected ?string $baseUrl = null,
        protected ?string $model = null,
    ) {
        $this->baseUrl = rtrim($this->baseUrl ?? config('ollama.base_url', 'http://127.0.0.1:11434'), '/');
        $this->model = $this->model ?? config('ollama.model', 'qwen3:1.7b');
    }

    public function getBaseUrl(): string
    {
        return $this->baseUrl;
    }

    public function getModel(): string
    {
        return $this->model;
    }

    public function getStatus(): array
    {
        $models = $this->listModels();
        $available = $this->isModelAvailable($this->model, $models);

        return [
            'connected' => $this->checkConnection(),
            'model' => $this->model,
            'available' => $available,
            'models' => $models,
            'base_url' => $this->baseUrl,
        ];
    }

    public function checkConnection(): bool
    {
        try {
            $response = Http::baseUrl($this->baseUrl)
                ->timeout(config('ollama.timeout', 30))
                ->connectTimeout(config('ollama.connect_timeout', 10))
                ->get('/api/tags');

            return $response->successful() && is_array($response->json('models'));
        } catch (\Throwable) {
            return false;
        }
    }

    public function listModels(): array
    {
        try {
            $response = Http::baseUrl($this->baseUrl)
                ->timeout(config('ollama.timeout', 30))
                ->connectTimeout(config('ollama.connect_timeout', 10))
                ->get('/api/tags');

            if (! $response->successful()) {
                return [];
            }

            $models = $response->json('models', []);

            return is_array($models)
                ? array_values(array_map(fn ($model) => (string) ($model['name'] ?? ''), array_filter($models, fn ($model) => is_array($model))))
                : [];
        } catch (\Throwable) {
            return [];
        }
    }

    public function ensureAvailableModel(?string $model = null): void
    {
        $selectedModel = $model ?? $this->model;
        $available = $this->listModels();

        if ($selectedModel === '' || ! $this->isModelAvailable($selectedModel, $available)) {
            throw new RuntimeException("Model {$selectedModel} tidak tersedia pada Ollama. Pastikan model sudah dipasang dan berjalan.");
        }
    }

    protected function isModelAvailable(string $model, array $availableModels): bool
    {
        $model = strtolower(trim($model));

        foreach ($availableModels as $availableModel) {
            $availableModel = strtolower(trim($availableModel));
            if (
                $model === $availableModel
                || (! str_contains($model, ':') && $model.':latest' === $availableModel)
                || (! str_contains($availableModel, ':') && $availableModel.':latest' === $model)
            ) {
                return true;
            }
        }

        return false;
    }

    public function streamText(string $prompt, callable $onChunk, ?string $model = null): string
    {
        if (trim($prompt) === '') {
            throw new RuntimeException('Prompt tidak boleh kosong.');
        }

        if (! $this->checkConnection()) {
            throw new RuntimeException('Ollama tidak dapat diakses.');
        }

        $selectedModel = $model ?? $this->model;
        $this->ensureAvailableModel($selectedModel);

        $response = Http::baseUrl($this->baseUrl)
            ->timeout(config('ollama.timeout', 120))
            ->connectTimeout(config('ollama.connect_timeout', 15))
            ->withOptions(['stream' => true])
            ->post('/api/generate', [
                'model' => $selectedModel,
                'prompt' => $prompt,
                'stream' => true,
                'think' => false,
                'keep_alive' => config('ollama.keep_alive', '15m'),
                'options' => [
                    'temperature' => config('ollama.temperature', 0.2),
                    'top_p' => config('ollama.top_p', 0.8),
                ],
            ]);

        if (! $response->successful()) {
            throw new RuntimeException('Permintaan ke Ollama gagal.');
        }

        $stream = $response->toPsrResponse()->getBody();
        $buffer = '';
        $text = '';
        $done = false;

        while (! $stream->eof() && ! connection_aborted()) {
            $buffer .= $stream->read(2048);
            while (($newline = strpos($buffer, "\n")) !== false) {
                $line = trim(substr($buffer, 0, $newline));
                $buffer = substr($buffer, $newline + 1);
                if ($line === '') {
                    continue;
                }

                $payload = json_decode($line, true);
                if (! is_array($payload)) {
                    throw new RuntimeException('Format respons streaming Ollama tidak valid.');
                }

                $chunk = (string) ($payload['response'] ?? '');
                if ($chunk !== '') {
                    $text .= $chunk;
                    $onChunk($chunk);
                }
                $done = $done || (bool) ($payload['done'] ?? false);
            }
        }

        if (connection_aborted()) {
            $stream->close();
            throw new RuntimeException('Generasi jawaban dihentikan oleh pengguna.');
        }

        $remaining = trim($buffer);
        if ($remaining !== '') {
            $payload = json_decode($remaining, true);
            if (! is_array($payload)) {
                throw new RuntimeException('Format respons streaming Ollama tidak valid.');
            }
            $chunk = (string) ($payload['response'] ?? '');
            if ($chunk !== '') {
                $text .= $chunk;
                $onChunk($chunk);
            }
            $done = $done || (bool) ($payload['done'] ?? false);
        }

        if (! $done || trim($text) === '') {
            throw new RuntimeException('Ollama tidak menyelesaikan jawaban atau mengembalikan respons kosong.');
        }

        return trim($text);
    }

    public function embedTexts(array $texts): array
    {
        $texts = array_values(array_filter($texts, fn ($text) => is_string($text) && trim($text) !== ''));
        if ($texts === []) {
            return [];
        }

        $model = (string) config('ollama.embedding_model', 'nomic-embed-text');
        $this->ensureAvailableModel($model);

        $response = Http::baseUrl($this->baseUrl)
            ->timeout(config('ollama.timeout', 120))
            ->connectTimeout(config('ollama.connect_timeout', 15))
            ->post('/api/embed', [
                'model' => $model,
                'input' => $texts,
            ]);

        if (! $response->successful()) {
            throw new RuntimeException('Ollama gagal membuat embedding materi.');
        }

        $embeddings = $response->json('embeddings');
        if (! is_array($embeddings) || count($embeddings) !== count($texts)) {
            throw new RuntimeException('Ollama mengembalikan jumlah embedding yang tidak sesuai.');
        }

        foreach ($embeddings as $embedding) {
            if (! is_array($embedding) || $embedding === [] || count(array_filter($embedding, 'is_numeric')) !== count($embedding)) {
                throw new RuntimeException('Ollama mengembalikan embedding dengan format tidak valid.');
            }
        }

        return $embeddings;
    }

}
