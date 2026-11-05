<?php

namespace App\Services\AI;

use App\Models\AiWebsiteKnowledgeChunk;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use Throwable;

class WebsiteKnowledgeRetriever
{
    public function __construct(
        protected OllamaService $ollama
    ) {}

    public function retrieve(string $question): array
    {
        if (! $this->isWebsiteQuestion($question)) {
            return ['context' => '', 'sources' => []];
        }

        $chunks = AiWebsiteKnowledgeChunk::query()
            ->where('visibility', 'public')
            ->get();

        if ($chunks->isEmpty()) {
            return ['context' => '', 'sources' => []];
        }

        $normalized = mb_strtolower(trim($question));
        $results = $chunks->map(function (AiWebsiteKnowledgeChunk $chunk) use ($normalized) {
            $content = mb_strtolower($chunk->content);
            $keywordScore = 0.0;
            foreach (preg_split('/[^a-zA-Z0-9_\p{L}]+/u', $normalized, -1, PREG_SPLIT_NO_EMPTY) ?: [] as $word) {
                if (mb_strlen($word) >= 3 && str_contains($content, $word)) {
                    $keywordScore += 1.0;
                }
            }

            return [
                'chunk' => $chunk,
                'keyword_score' => $keywordScore,
                'semantic_score' => 0.0,
                'score' => $keywordScore,
            ];
        });

        try {
            $queryEmbeddings = $this->ollama->embedTexts([$question]);
            if (($queryEmbeddings[0] ?? null) && is_array($queryEmbeddings[0])) {
                $queryEmbedding = $queryEmbeddings[0];
                $results = $results->map(function (array $result) use ($queryEmbedding) {
                    $embedding = $result['chunk']->embedding;
                    $semantic = is_array($embedding) ? $this->cosine($queryEmbedding, $embedding) : 0.0;
                    $result['semantic_score'] = $semantic;
                    $result['score'] = $semantic + min($result['keyword_score'] * 0.08, 0.5);

                    return $result;
                });
            }
        } catch (Throwable) {
            // Keyword fallback remains available.
        }

        $ranked = $results
            ->filter(fn (array $result) => $result['keyword_score'] > 0 || $result['semantic_score'] >= 0.25)
            ->sortByDesc('score')
            ->take(4)
            ->values();

        if ($ranked->isEmpty()) {
            return ['context' => '', 'sources' => []];
        }

        $blocks = [];
        $sources = [];
        foreach ($ranked as $result) {
            $chunk = $result['chunk'];
            $blocks[] = "Dokumentasi {$chunk->feature} ({$chunk->source_key}, versi {$chunk->version}, diperbarui {$chunk->last_updated?->toDateString()}):\n".$chunk->content;
            $sources[] = [
                'type' => 'website_documentation',
                'title' => $chunk->feature.' - Dokumentasi',
                'link' => $chunk->route ? url($chunk->route) : route('dashboard'),
            ];
        }

        return [
            'context' => implode("\n\n", $blocks),
            'sources' => collect($sources)->unique('title')->values()->all(),
        ];
    }

    public function synchronize(bool $force = false): array
    {
        $directory = resource_path('ai/knowledge');
        if (! is_dir($directory)) {
            return ['synced' => 0, 'removed' => 0, 'unchanged' => 0];
        }

        $synced = 0;
        $unchanged = 0;
        $seen = [];

        foreach (File::files($directory) as $file) {
            if ($file->getExtension() !== 'md') {
                continue;
            }

            $sourceKey = 'website/'.$file->getFilename();
            $raw = $file->getContents();
            $sourceHash = $this->computeSourceHash($raw);
            $seen[] = $sourceKey;
            $existing = AiWebsiteKnowledgeChunk::query()->where('source_key', $sourceKey)->first();

            if (! $force && $existing && $existing->source_hash === $sourceHash) {
                $unchanged++;
                continue;
            }

            $document = $this->parseDocument($raw);
            $chunks = $this->splitIntoChunks($document['body']);
            if ($chunks === []) {
                AiWebsiteKnowledgeChunk::query()->where('source_key', $sourceKey)->delete();
                continue;
            }

            AiWebsiteKnowledgeChunk::query()->where('source_key', $sourceKey)->delete();
            $embeddings = [];
            try {
                $embeddings = $this->ollama->embedTexts($chunks);
            } catch (Throwable) {
                $embeddings = [];
            }

            foreach ($chunks as $index => $chunk) {
                AiWebsiteKnowledgeChunk::create([
                    'source_key' => $sourceKey,
                    'feature' => $document['feature'],
                    'route' => $document['route'],
                    'visibility' => 'public',
                    'version' => $document['version'],
                    'last_updated' => $document['last_updated'],
                    'chunk_index' => $index,
                    'content' => $chunk,
                    'embedding' => $embeddings[$index] ?? null,
                    'content_hash' => hash('sha256', $document['feature']."\0".$chunk),
                    'source_hash' => $sourceHash,
                    'metadata' => [
                        'source_file' => $sourceKey,
                        'route' => $document['route'],
                        'visibility' => 'public',
                    ],
                ]);
            }
            $synced++;
        }

        $removed = 0;
        AiWebsiteKnowledgeChunk::query()
            ->whereNotIn('source_key', $seen)
            ->each(function (AiWebsiteKnowledgeChunk $chunk) use (&$removed) {
                $removed++;
                $chunk->delete();
            });

        return ['synced' => $synced, 'removed' => $removed, 'unchanged' => $unchanged];
    }

    protected function computeSourceHash(string $raw): string
    {
        $parts = [hash('sha256', $raw)];
        if (preg_match_all('/`([^`]+\.(?:php|md))`/', $raw, $matches)) {
            foreach (array_unique($matches[1]) as $relativePath) {
                $absolutePath = base_path(str_replace('/', DIRECTORY_SEPARATOR, $relativePath));
                if (is_file($absolutePath)) {
                    $parts[] = hash('sha256', (string) file_get_contents($absolutePath));
                }
            }
        }

        return hash('sha256', implode("\0", $parts));
    }

    protected function isWebsiteQuestion(string $question): bool
    {
        $normalized = mb_strtolower($question);
        if (preg_match('/\b(cara|bagaimana|validasi|aturan|fitur|troubleshooting|batasan|route)\b/u', $normalized)) {
            // Explicit website/help questions take precedence over personal-data wording.
        } else {
            $personalDataPatterns = [
                'tugas saya', 'aktivitas saya', 'target saya', 'catatan saya',
                'data saya', 'milik saya', 'belum selesai saya',
                'rangkum catatan', 'buat soal dari', 'latihan dari',
            ];
            foreach ($personalDataPatterns as $pattern) {
                if (str_contains($normalized, $pattern)) {
                    return false;
                }
            }
        }

        $websiteTerms = [
            'dashboard', 'task', 'tugas', 'daily', 'aktivitas', 'goal', 'target',
            'catatan belajar', 'catatan', 'pengaturan',
            'catatin ai', 'assistant', 'registrasi', 'register', 'login', 'masuk',
            'daftar', 'menu', 'cara', 'validasi',
            'aturan', 'troubleshooting', 'error', 'halaman', 'fitur tidak tersedia',
        ];

        foreach ($websiteTerms as $term) {
            if (str_contains($normalized, $term)) {
                return true;
            }
        }

        return false;
    }

    protected function parseDocument(string $raw): array
    {
        $feature = 'CatatIn';
        $route = null;
        $version = '1.0';
        $lastUpdated = now()->toDateString();
        $body = trim($raw);

        if (preg_match('/\A---\s*\r?\n(.*?)\r?\n---\s*\r?\n?(.*)\z/s', $raw, $matches)) {
            $frontmatter = $matches[1];
            $body = trim($matches[2]);
            foreach (preg_split('/\r?\n/', $frontmatter) ?: [] as $line) {
                [$key, $value] = array_pad(explode(':', $line, 2), 2, '');
                $key = trim($key);
                $value = trim(trim($value), "\"'");
                if ($key === 'feature') $feature = $value;
                if ($key === 'route' && $value !== '') $route = $value;
                if ($key === 'version') $version = $value;
                if ($key === 'last_updated') $lastUpdated = $value;
            }
        }

        return [
            'feature' => $feature,
            'route' => $route,
            'version' => $version,
            'last_updated' => $lastUpdated,
            'body' => $body,
        ];
    }

    protected function splitIntoChunks(string $text): array
    {
        $text = trim(preg_replace('/\s+/', ' ', strip_tags($text)) ?? $text);
        if ($text === '') {
            return [];
        }

        $chunks = [];
        $length = mb_strlen($text);
        for ($offset = 0, $count = 0; $offset < $length && $count < 100; $offset += 700, $count++) {
            $chunk = trim(mb_substr($text, $offset, 900));
            if ($chunk !== '') {
                $chunks[] = $chunk;
            }
        }

        return $chunks;
    }

    protected function cosine(array $first, array $second): float
    {
        if ($first === [] || count($first) !== count($second)) {
            return 0.0;
        }

        $dot = $firstMagnitude = $secondMagnitude = 0.0;
        foreach ($first as $index => $value) {
            $left = (float) $value;
            $right = (float) ($second[$index] ?? 0);
            $dot += $left * $right;
            $firstMagnitude += $left * $left;
            $secondMagnitude += $right * $right;
        }

        return $firstMagnitude && $secondMagnitude
            ? $dot / (sqrt($firstMagnitude) * sqrt($secondMagnitude))
            : 0.0;
    }
}
