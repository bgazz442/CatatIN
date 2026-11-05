<?php

namespace Tests\Feature;

use App\Models\AiChatConversation;
use App\Models\User;
use App\Services\AI\AiQueryRouter;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class AiCoreIntentTest extends TestCase
{
    use RefreshDatabase;

    public function test_router_classifies_core_question_types(): void
    {
        $router = app(AiQueryRouter::class);

        $this->assertSame('greeting', $router->route('Hai')['primary_intent']);
        $this->assertSame('acknowledgment', $router->route('Terima kasih')['primary_intent']);
        $this->assertSame('general', $router->route('Apa itu Laravel?')['primary_intent']);
        $this->assertSame('website', $router->route('Apa fungsi Dashboard?')['primary_intent']);
        $this->assertSame('productivity', $router->route('Apa tugas saya yang belum selesai?')['primary_intent']);
        $this->assertSame('learning', $router->route('Rangkum catatan belajar saya')['primary_intent']);
        $this->assertSame('website', $router->route('Apa saja status task yang tersedia?')['primary_intent']);
        $this->assertSame('productivity', $router->route('Bagaimana status tugas saya untuk laporan PKL?')['primary_intent']);
        $this->assertSame('productivity', $router->route('Apa deskripsi tugas laporan PKL?')['primary_intent']);
        $this->assertSame('productivity', $router->route('Apa aktivitas terbaru saya?')['primary_intent']);
        $this->assertSame('ambiguous', $router->route('Bagaimana progres saya?')['primary_intent']);
        $this->assertSame('unsupported', $router->route('Bagaimana fitur pembayaran berlangganan di CatatIn?')['primary_intent']);
    }

    public function test_router_classifies_phase_4b_greeting_acknowledgment_and_language_variations(): void
    {
        $router = app(AiQueryRouter::class);

        $cases = [
            'greeting' => [
                'Hai',
                'Halo bro',
                'Selamat pagi semuanya',
                'Apa kabar?',
                'Halo gan',
                'Hi, teman!',
                'Selamat sore semuanya',
            ],
            'acknowledgment' => [
                'Makasih',
                'Mksih',
                'Terima kasih',
                'Oke, sip',
                'Siap',
                'Thanks',
                'Makasi ya',
            ],
        ];

        foreach ($cases as $intent => $questions) {
            foreach ($questions as $question) {
                $this->assertSame(
                    $intent,
                    $router->route($question)['primary_intent'],
                    "Unexpected intent for: {$question}"
                );
            }
        }

        $acknowledgment = $router->route('Mksih');
        $this->assertSame(['acknowledgment'], $acknowledgment['intents']);
        $greeting = $router->route('Apa kabar?');
        $this->assertSame(['greeting'], $greeting['intents']);
    }

    public function test_router_classifies_phase_4b_general_website_productivity_and_learning_variations(): void
    {
        $router = app(AiQueryRouter::class);

        $expected = [
            'general' => [
                'Beda PHP sama JavaScript gimana?',
                'Beda PHP sama JS gimana?',
                'Cara belajar programming?',
                'cara bljr programming',
                'Jelaskan konsep API dengan sederhana.',
                'jelasin konsep API secara simpel',
            ],
            'website' => [
                'Gimana cara buat task?',
                'gmna cara buat task?',
                'Fitur dashboard buat apa?',
                'Status tugas ada apa aja?',
                'status task apa saja',
            ],
            'productivity' => [
                'Goal gw gimana?',
                'Tugas saya yang belum selesai apa?',
                'Aktivitas saya minggu ini apa saja?',
                'aktivitas gw hari ini apa saja',
            ],
            'learning' => [
                'Coba rangkum catatan saya.',
                'Jelaskan materi yang saya pelajari.',
                'Rangkum catatan saya tentang Laravel',
                'jelasin materi saya tentang API',
            ],
        ];

        foreach ($expected as $intent => $questions) {
            foreach ($questions as $question) {
                $this->assertSame(
                    $intent,
                    $router->route($question)['primary_intent'],
                    "Unexpected intent for: {$question}"
                );
            }
        }
    }

    public function test_router_sends_category_and_workspace_questions_to_database_retrieval(): void
    {
        $router = app(AiQueryRouter::class);
        $cases = [
            'Kategori apa saja yang tersedia?' => ['workspace'],
            'Berapa jumlah kategori saya?' => ['workspace'],
            'Berapa catatan dalam kategori Pemrograman?' => ['workspace'],
            'Apa saja catatan dalam kategori Pemrograman?' => ['workspace', 'learning'],
            'Apa saja task dalam kategori Proyek?' => ['workspace', 'productivity'],
            'Apa saja aktivitas dalam kategori Olahraga?' => ['workspace', 'productivity'],
            'Bagaimana kondisi workspace saya?' => ['workspace'],
            'Ringkas seluruh workspace saya.' => ['workspace'],
            'Apa saja yang saya punya di CatatIn?' => ['workspace'],
            'Berapa jumlah seluruh catatan saya?' => ['workspace'],
            'Berapa task yang belum selesai?' => ['workspace'],
            'Bagaimana progres goal saya?' => ['workspace'],
            'Berapa aktivitas yang sudah saya catat?' => ['workspace'],
            'Kategori mana yang paling banyak memiliki catatan?' => ['workspace'],
        ];

        foreach ($cases as $question => $expectedIntents) {
            $route = $router->route($question);
            $this->assertSame('workspace', $route['primary_intent'], $question);
            $this->assertSame($expectedIntents, $route['intents'], $question);
        }
    }

    public function test_follow_up_uses_previous_topic_without_activating_all_retrievers(): void
    {
        $router = app(AiQueryRouter::class);
        $websiteHistory = "USER: Bagaimana cara membuat task?\nASSISTANT: Gunakan Task Manager.";
        $learningHistory = "USER: Rangkum catatan saya tentang algoritma\nASSISTANT: Ringkasan tersedia.";

        $this->assertSame(['website'], $router->route('Jelaskan lebih detail.', $websiteHistory)['intents']);
        $this->assertSame(['learning'], $router->route('Berikan contoh.', $learningHistory)['intents']);
        $this->assertSame('ambiguous', $router->route('Jelaskan lebih detail.')['primary_intent']);
    }

    public function test_greeting_chat_returns_short_answer_without_ollama_or_sources(): void
    {
        $user = User::factory()->create();
        Http::fake();

        $response = $this->actingAs($user)->postJson(route('ai.chat'), [
            'message' => 'Hai',
        ]);

        $response->assertOk();
        $lines = collect(explode("\n", $response->streamedContent()))
            ->filter()
            ->map(fn (string $line) => json_decode($line, true))
            ->values();

        $this->assertNotEmpty($lines->firstWhere('type', 'conversation'));
        $this->assertStringContainsString('Halo!', $lines->firstWhere('type', 'chunk')['text']);
        $this->assertSame([], $lines->last()['source_refs']);
        $this->assertSame(0, Http::recorded()->count());
    }

    public function test_phase_4b_greetings_and_acknowledgments_do_not_call_ollama(): void
    {
        $user = User::factory()->create();
        Http::fake();

        foreach (['Halo bro', 'Selamat pagi semuanya', 'Apa kabar?', 'Makasih', 'Mksih', 'Oke, sip', 'Siap'] as $message) {
            $response = $this->actingAs($user)->postJson(route('ai.chat'), [
                'message' => $message,
            ]);

            $response->assertOk();
            $lines = collect(explode("\n", $response->streamedContent()))
                ->filter()
                ->map(fn (string $line) => json_decode($line, true))
                ->values();

            $this->assertNotEmpty($lines->firstWhere('type', 'chunk'), "Expected static answer for: {$message}");
            $this->assertSame([], $lines->last()['source_refs']);
        }

        $this->assertSame(0, Http::recorded()->count());
    }

    public function test_unknown_website_question_returns_grounded_fallback_without_source(): void
    {
        $user = User::factory()->create();
        Http::fake();

        $response = $this->actingAs($user)->postJson(route('ai.chat'), [
            'message' => 'Bagaimana cara melakukan pembayaran berlangganan di CatatIn?',
        ]);

        $response->assertOk();
        $content = $response->streamedContent();
        $this->assertStringContainsString('tidak menemukan dokumentasi', $content);
        $this->assertStringNotContainsString('Media Favorit', $content);
    }

    public function test_conversation_history_and_source_refs_remain_user_scoped(): void
    {
        $owner = User::factory()->create();
        $other = User::factory()->create();

        $conversation = AiChatConversation::create([
            'user_id' => $owner->id,
            'title' => 'Isolasi pengguna',
        ]);

        $this->actingAs($other)
            ->getJson(route('ai.conversation.show', $conversation))
            ->assertForbidden();

        $this->actingAs($owner)->postJson(route('ai.chat'), [
            'message' => 'Halo',
            'conversation_id' => $conversation->id,
        ])->assertOk();

        $this->assertSame($owner->id, $conversation->fresh()->user_id);
        $this->assertDatabaseHas('ai_chat_messages', [
            'conversation_id' => $conversation->id,
            'role' => 'user',
            'content' => 'Halo',
        ]);
    }
}
