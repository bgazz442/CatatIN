<?php

namespace Tests\Feature;

use App\Models\AiChatConversation;
use App\Models\User;
use App\Services\AI\AiQueryRouter;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class AiPhase3Test extends TestCase
{
    use RefreshDatabase;

    public function test_query_router_separates_feature_private_and_follow_up_intents(): void
    {
        $router = app(AiQueryRouter::class);

        $this->assertContains('website', $router->route('Bagaimana cara membuat tugas?')['intents']);
        $this->assertContains('productivity', $router->route('Apa tugas saya yang belum selesai?')['intents']);
        $this->assertContains('learning', $router->route('Rangkum catatan saya tentang algoritma')['intents']);
        $this->assertContains('learning', $router->route('Buat soal latihan dari materi ini')['intents']);
        $this->assertSame([], $router->route('Apa arti kedaulatan?')['intents']);
        $this->assertContains('learning', $router->route('Apa video favorit saya?')['intents']);
    }

    public function test_chat_updates_existing_summary_without_migration_or_cross_user_access(): void
    {
        $owner = User::factory()->create();
        $other = User::factory()->create();

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
                json_encode(['response' => 'Jawaban aman berdasarkan konteks yang tersedia.', 'done' => true])."\n"
            );
        });

        $conversation = AiChatConversation::create([
            'user_id' => $owner->id,
            'title' => 'Percobaan ringkas',
            'context_summary' => null,
        ]);

        $response = $this->actingAs($owner)->postJson(route('ai.chat'), [
            'message' => 'Ringkas catatan saya yang tersedia.',
            'conversation_id' => $conversation->id,
        ]);

        $response->assertOk();
        $response->streamedContent();

        $this->assertNotEmpty($conversation->fresh()->context_summary);

        $this->actingAs($other)->getJson(route('ai.conversation.show', $conversation))
            ->assertForbidden();

        $this->actingAs($other)->deleteJson(route('ai.conversation.destroy', $conversation))
            ->assertForbidden();
    }

    public function test_chat_handles_pdf_and_multimedia_questions_without_claiming_unavailable_processing(): void
    {
        $user = User::factory()->create();

        Http::fake(function ($request) {
            if (str_ends_with($request->url(), '/api/tags')) {
                return Http::response(['models' => [['name' => 'qwen3:1.7b']]]);
            }
            if (str_ends_with($request->url(), '/api/embed')) {
                return Http::response([
                    'embeddings' => array_fill(0, count($request['input']), [1.0, 0.0]),
                ]);
            }

            return Http::response(
                json_encode(['response' => 'Isi PDF dan video belum tersedia dalam sumber CatatIn bila konten belum diekstrak.', 'done' => true])."\n"
            );
        });

        $response = $this->actingAs($user)->postJson(route('ai.chat'), [
            'message' => 'Bisakah kamu membaca isi PDF dan video saya?',
        ]);

        $response->assertOk();
        $this->assertStringContainsString(
            'belum tersedia dalam sumber CatatIn',
            $response->streamedContent()
        );
    }
}
