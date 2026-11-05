<?php

namespace Tests\Feature;

use App\Models\AiWebsiteKnowledgeChunk;
use App\Models\User;
use App\Services\AI\WebsiteKnowledgeRetriever;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class WebsiteKnowledgeTest extends TestCase
{
    use RefreshDatabase;

    public function test_website_knowledge_sync_creates_public_chunks_without_user_binding(): void
    {
        Http::fake(function ($request) {
            if (str_ends_with($request->url(), '/api/tags')) {
                return Http::response(['models' => [
                    ['name' => 'qwen3:1.7b'],
                    ['name' => 'nomic-embed-text:latest'],
                ]]);
            }
            if (str_ends_with($request->url(), '/api/embed')) {
                return Http::response([
                    'embeddings' => array_fill(0, count($request['input']), [1.0, 0.0]),
                ]);
            }
            return Http::response(['response' => '', 'done' => true]);
        });

        $result = app(WebsiteKnowledgeRetriever::class)->synchronize(true);

        $this->assertGreaterThanOrEqual(8, $result['synced']);
        $this->assertGreaterThanOrEqual(8, AiWebsiteKnowledgeChunk::query()->distinct()->count('source_key'));
        $this->assertDatabaseMissing('ai_website_knowledge_chunks', ['visibility' => 'private']);
        $this->assertDatabaseHas('ai_website_knowledge_chunks', [
            'feature' => 'Task Manager',
            'visibility' => 'public',
        ]);
        $this->assertDatabaseHas('ai_website_knowledge_chunks', [
            'feature' => 'Registrasi dan Login',
            'visibility' => 'public',
        ]);
        $this->assertDatabaseMissing('ai_website_knowledge_chunks', [
            'feature' => 'Media Favorit',
        ]);
    }

    public function test_sync_does_not_reembed_when_document_hash_is_unchanged(): void
    {
        Http::fake(function ($request) {
            if (str_ends_with($request->url(), '/api/embed')) {
                return Http::response([
                    'embeddings' => array_fill(0, count($request['input']), [1.0, 0.0]),
                ]);
            }
            return Http::response(['models' => [
                ['name' => 'qwen3:1.7b'],
                ['name' => 'nomic-embed-text:latest'],
            ]]);
        });

        $retriever = app(WebsiteKnowledgeRetriever::class);
        $first = $retriever->synchronize(true);
        $second = $retriever->synchronize();

        $this->assertGreaterThanOrEqual(8, $first['synced']);
        $this->assertSame(0, $second['synced']);
    }

    public function test_retriever_finds_task_documentation_and_returns_feature_citation(): void
    {
        Http::fake(function ($request) {
            if (str_ends_with($request->url(), '/api/tags')) {
                return Http::response(['models' => [
                    ['name' => 'qwen3:1.7b'],
                    ['name' => 'nomic-embed-text:latest'],
                ]]);
            }
            if (str_ends_with($request->url(), '/api/embed')) {
                return Http::response([
                    'embeddings' => array_fill(0, count($request['input']), [1.0, 0.0]),
                ]);
            }
            return Http::response(['response' => '', 'done' => true]);
        });

        $retriever = app(WebsiteKnowledgeRetriever::class);
        $retriever->synchronize(true);
        $result = $retriever->retrieve(
            'Bagaimana cara validasi dan aturan status pada Task Manager?'
        );

        $this->assertStringContainsString('Task Manager', $result['context']);
        $this->assertNotEmpty($result['sources']);
        $this->assertSame('website_documentation', $result['sources'][0]['type']);
        $this->assertStringContainsString('Task Manager', $result['sources'][0]['title']);
    }

    public function test_fallback_keyword_retrieval_continues_when_embedding_fails(): void
    {
        Http::fake(function ($request) {
            if (str_ends_with($request->url(), '/api/tags')) {
                return Http::response(['models' => [
                    ['name' => 'qwen3:1.7b'],
                    ['name' => 'nomic-embed-text:latest'],
                ]]);
            }
            if (str_ends_with($request->url(), '/api/embed')) {
                return Http::response(['error' => 'embedding unavailable'], 503);
            }
            return Http::response(['response' => '', 'done' => true]);
        });

        app(WebsiteKnowledgeRetriever::class)->synchronize(true);
        $result = app(WebsiteKnowledgeRetriever::class)->retrieve(
            'Bagaimana validasi pada Goal Tracker?'
        );

        $this->assertStringContainsString('Goal Tracker', $result['context']);
        $this->assertNotEmpty($result['sources']);
    }

    public function test_private_personal_question_does_not_return_public_documentation_context(): void
    {
        $result = app(WebsiteKnowledgeRetriever::class)->retrieve(
            'Rangkum catatan saya yang belum selesai.'
        );

        $this->assertSame('', $result['context']);
        $this->assertSame([], $result['sources']);
    }

    public function test_unknown_feature_question_returns_empty_context_instead_of_inventing_information(): void
    {
        Http::fake(function ($request) {
            if (str_ends_with($request->url(), '/api/tags')) {
                return Http::response(['models' => [['name' => 'qwen3:1.7b']]]);
            }
            if (str_ends_with($request->url(), '/api/embed')) {
                return Http::response([
                    'embeddings' => array_fill(0, count($request['input']), [1.0, 0.0]),
                ]);
            }
            return Http::response(['response' => '', 'done' => true]);
        });

        $result = app(WebsiteKnowledgeRetriever::class)->retrieve(
            'Bagaimana fitur pembayaran berlangganan di CatatIn?'
        );

        $this->assertSame('', $result['context']);
        $this->assertSame([], $result['sources']);
    }

    public function test_guest_cannot_use_chat_route_and_guest_menu_remains_separate(): void
    {
        $this->get(route('ai.index'))->assertRedirect(route('login', [
            'login_required' => 1,
            'redirect_to' => '/ai',
        ]));
        $this->postJson(route('ai.chat'), [
            'message' => 'Bagaimana cara membuat tugas?',
        ])->assertUnauthorized();
    }

    public function test_authenticated_chat_returns_public_feature_citation(): void
    {
        $user = User::factory()->create();

        Http::fake(function ($request) {
            if (str_ends_with($request->url(), '/api/tags')) {
                return Http::response(['models' => [
                    ['name' => 'qwen3:1.7b'],
                    ['name' => 'nomic-embed-text:latest'],
                ]]);
            }
            if (str_ends_with($request->url(), '/api/embed')) {
                return Http::response([
                    'embeddings' => array_fill(0, count($request['input']), [1.0, 0.0]),
                ]);
            }
            return Http::response(
                json_encode(['response' => 'Gunakan Task Manager sesuai dokumentasi.', 'done' => true])."\n"
            );
        });

        app(WebsiteKnowledgeRetriever::class)->synchronize(true);

        $response = $this->actingAs($user)->postJson(route('ai.chat'), [
            'message' => 'Bagaimana cara membuat tugas dan validasinya?',
        ]);

        $response->assertOk();
        $lines = collect(explode("\n", $response->streamedContent()))
            ->filter()
            ->map(fn (string $line) => json_decode($line, true))
            ->values();

        $this->assertNotEmpty($lines->last()['source_refs'] ?? []);
        $this->assertContains(
            'website_documentation',
            array_column($lines->last()['source_refs'], 'type')
        );
    }
}
