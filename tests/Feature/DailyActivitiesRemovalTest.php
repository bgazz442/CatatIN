<?php

namespace Tests\Feature;

use App\Models\AiChatConversation;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class DailyActivitiesRemovalTest extends TestCase
{
    use RefreshDatabase;

    public function test_daily_activities_routes_and_table_are_removed(): void
    {
        $this->assertFalse(Route::has('activities.index'));
        $this->assertFalse(Schema::hasTable('daily_activities'));

        $this->get('/aktivitas')->assertNotFound();
        $this->get('/aktivitas/tambah')->assertNotFound();
        $this->post('/aktivitas')->assertNotFound();
    }

    public function test_dashboard_has_no_daily_activities_navigation_or_widget(): void
    {
        $response = $this->actingAs(User::factory()->create())->get(route('dashboard'));

        $response->assertOk()
            ->assertSee('Task Manager')
            ->assertSee('Goal Tracker')
            ->assertDontSee('Daily Activity')
            ->assertDontSee('Aktivitas Hari Ini')
            ->assertDontSee('/aktivitas');
    }

    public function test_ai_states_the_feature_is_unavailable_without_retrieval_or_sources(): void
    {
        $user = User::factory()->create();
        $conversation = AiChatConversation::create([
            'user_id' => $user->id,
            'title' => 'Riwayat aktivitas lama',
            'context_summary' => 'USER: Daily Activities tersedia dan dapat digunakan.',
        ]);
        $conversation->messages()->create([
            'role' => 'assistant',
            'content' => 'Gunakan Daily Activities untuk mencatat aktivitas.',
        ]);
        Http::fake();

        foreach ([
            'Bagaimana cara menggunakan Daily Activities?',
            'Apakah saya bisa mencatat aktivitas harian?',
        ] as $question) {
            $response = $this->actingAs($user)->postJson(route('ai.chat'), [
                'message' => $question,
                'conversation_id' => $conversation->id,
            ])->assertOk();
            $events = collect(explode("\n", $response->streamedContent()))
                ->filter()
                ->map(fn (string $line) => json_decode($line, true))
                ->values();

            $this->assertStringContainsString('sudah tidak tersedia', $events[1]['text']);
            $this->assertSame([], $events->last()['source_refs']);
        }

        Http::assertNothingSent();
    }

    public function test_ai_lists_only_active_features_and_discloses_its_data_access(): void
    {
        $user = User::factory()->create();
        Http::fake();

        foreach ([
            [
                'message' => 'Apa saja fitur yang tersedia di CatatIn?',
                'expected' => ['Dashboard', 'Task Manager', 'Goal Tracker', 'Catatan Belajar', 'CatatIn AI', 'Pengaturan Akun'],
            ],
            [
                'message' => 'Apa saja data yang bisa kamu akses dari website?',
                'expected' => ['catatan belajar', 'tugas', 'target', 'akun yang sedang login'],
            ],
        ] as $case) {
            $response = $this->actingAs($user)->postJson(route('ai.chat'), [
                'message' => $case['message'],
            ])->assertOk();
            $events = collect(explode("\n", $response->streamedContent()))
                ->filter()
                ->map(fn (string $line) => json_decode($line, true))
                ->values();

            foreach ($case['expected'] as $expected) {
                $this->assertStringContainsString($expected, $events[1]['text']);
            }
            $this->assertSame([], $events->last()['source_refs']);
        }

        Http::assertNothingSent();
    }

    public function test_old_chat_sources_are_hidden_without_deleting_conversation_history(): void
    {
        $user = User::factory()->create();
        $conversation = AiChatConversation::create([
            'user_id' => $user->id,
            'title' => 'Riwayat lama',
            'context_summary' => 'USER: Daily Activities marker lama.',
        ]);
        $oldMessage = $conversation->messages()->create([
            'role' => 'assistant',
            'content' => 'Daily Activities history marker lama.',
            'source_refs' => [
                ['type' => 'aktivitas', 'title' => 'Riwayat aktivitas', 'link' => '/aktivitas/1/edit'],
                ['type' => 'tugas', 'title' => 'Task Manager', 'link' => '/tugas/1/edit'],
            ],
        ]);
        $conversation->messages()->create([
            'role' => 'user',
            'content' => 'Bagaimana cara membuat task?',
        ]);
        $conversation->messages()->create([
            'role' => 'assistant',
            'content' => 'Task Manager context marker.',
        ]);

        $historyResponse = $this->actingAs($user)->getJson(route('ai.conversation.show', $conversation));
        $historyResponse->assertOk();
        $oldHistory = collect($historyResponse->json('messages'))->firstWhere('id', $oldMessage->id);
        $this->assertSame('Daily Activities history marker lama.', $oldHistory['content']);
        $this->assertSame(['tugas'], array_column($oldHistory['source_refs'], 'type'));
        $this->assertDatabaseHas('ai_chat_messages', ['id' => $oldMessage->id]);
    }
}
