<?php

namespace App\Services\AI;

use App\Models\AiMaterialChunk;
use Illuminate\Support\Facades\DB;

class LearningMaterialIndexer
{
    public function forgetMaterial(int $userId, string $sourceType, int $sourceId): void
    {
        AiMaterialChunk::query()
            ->where('user_id', $userId)
            ->where('source_type', $sourceType)
            ->where('source_id', $sourceId)
            ->delete();
    }

    public function rankCandidates(int $userId, array $candidates, array $queryEmbedding): array
    {
        $sourceTypes = array_values(array_unique(array_column($candidates, 'source_type')));
        $sourceIds = array_values(array_unique(array_column($candidates, 'source_id')));
        $cachedChunks = AiMaterialChunk::query()
            ->where('user_id', $userId)
            ->whereIn('source_type', $sourceTypes)
            ->whereIn('source_id', $sourceIds)
            ->get()
            ->groupBy(fn (AiMaterialChunk $chunk) => $this->cacheKey($chunk->source_type, (int) $chunk->source_id));

        $pending = [];
        foreach ($candidates as $index => &$candidate) {
            $candidate['text'] = trim(preg_replace('/\s+/', ' ', strip_tags($candidate['text'])) ?? $candidate['text']);
            $candidate['content_hash'] = hash(
                'sha256',
                config('ollama.embedding_model', 'nomic-embed-text')."\0".$candidate['text']
            );
            $candidate['chunks'] = $this->splitIntoChunks($candidate['text']);
            $cached = $cachedChunks->get($this->cacheKey($candidate['source_type'], $candidate['source_id']), collect());

            if (
                $cached->first()?->content_hash === $candidate['content_hash']
                && $cached->count() === count($candidate['chunks'])
            ) {
                $candidate['embeddings'] = $cached->sortBy('chunk_index')->pluck('embedding')->all();

                continue;
            }

            foreach ($candidate['chunks'] as $chunkIndex => $chunk) {
                $pending[] = [
                    'candidate_index' => $index,
                    'chunk_index' => $chunkIndex,
                    'content' => $chunk,
                ];
            }
        }
        unset($candidate);

        $generatedEmbeddings = [];
        foreach (array_chunk($pending, 64) as $batch) {
            $vectors = app(OllamaService::class)->embedTexts(array_column($batch, 'content'));
            foreach ($batch as $index => $item) {
                $generatedEmbeddings[$item['candidate_index']][$item['chunk_index']] = $vectors[$index];
            }
        }

        if ($generatedEmbeddings !== []) {
            DB::transaction(function () use (&$candidates, $generatedEmbeddings, $userId) {
                foreach ($generatedEmbeddings as $candidateIndex => $vectors) {
                    $candidate = $candidates[$candidateIndex];
                    AiMaterialChunk::query()
                        ->where('user_id', $userId)
                        ->where('source_type', $candidate['source_type'])
                        ->where('source_id', $candidate['source_id'])
                        ->delete();

                    foreach ($vectors as $chunkIndex => $embedding) {
                        AiMaterialChunk::create([
                            'user_id' => $userId,
                            'source_type' => $candidate['source_type'],
                            'source_id' => $candidate['source_id'],
                            'chunk_index' => $chunkIndex,
                            'content' => $candidate['chunks'][$chunkIndex],
                            'embedding' => $embedding,
                            'metadata' => $candidate['metadata'],
                            'content_hash' => $candidate['content_hash'],
                        ]);
                    }
                    $candidates[$candidateIndex]['embeddings'] = $vectors;
                }
            });
        }

        foreach ($candidates as &$candidate) {
            $similarity = collect($candidate['embeddings'] ?? [])
                ->map(fn (array $embedding) => $this->cosine($queryEmbedding, $embedding))
                ->max() ?? 0.0;
            $candidate['similarity'] = $similarity;
            $candidate['score'] = $similarity + min($candidate['keyword_score'] * 0.06, 0.5);
        }
        unset($candidate);

        return collect($candidates)
            ->filter(fn (array $candidate) => $candidate['keyword_score'] > 0 || $candidate['similarity'] >= 0.25)
            ->sortByDesc('score')
            ->values()
            ->all();
    }

    protected function splitIntoChunks(string $text): array
    {
        $length = mb_strlen($text);
        $chunks = [];

        for ($offset = 0, $count = 0; $offset < $length && $count < 100; $offset += 800, $count++) {
            $chunk = trim(mb_substr($text, $offset, 1000));
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

        $dot = 0.0;
        $firstMagnitude = 0.0;
        $secondMagnitude = 0.0;
        foreach ($first as $index => $value) {
            if (! is_numeric($value) || ! is_numeric($second[$index] ?? null)) {
                return 0.0;
            }

            $left = (float) $value;
            $right = (float) $second[$index];
            $dot += $left * $right;
            $firstMagnitude += $left * $left;
            $secondMagnitude += $right * $right;
        }

        if ($firstMagnitude <= 0.0 || $secondMagnitude <= 0.0) {
            return 0.0;
        }

        return $dot / (sqrt($firstMagnitude) * sqrt($secondMagnitude));
    }

    protected function cacheKey(string $sourceType, int $sourceId): string
    {
        return $sourceType.':'.$sourceId;
    }
}
