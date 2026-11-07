<?php

namespace Tests\Feature;

use App\Models\AiChatConversation;
use App\Models\AiChatMessage;
use App\Models\AiTranscription;
use App\Models\Catatan;
use App\Models\Category;
use App\Models\Goal;
use App\Models\MediaFavorite;
use App\Models\Task;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class AiChatTest extends TestCase
{
    use RefreshDatabase;

    public function test_chat_only_retrieves_materials_owned_by_the_authenticated_user(): void
    {
        $user = User::factory()->create(['google_id' => 'google-chat-owner']);
        $otherUser = User::factory()->create();
        $category = Category::where('name', 'Pemrograman')->firstOrFail();

        Catatan::create([
            'user_id' => $user->id,
            'judul' => 'Perulangan milik saya',
            'kategori' => $category->name,
            'category_id' => $category->id,
            'isi' => 'For loop digunakan untuk mengulang instruksi.',
        ]);
        Catatan::create([
            'user_id' => $otherUser->id,
            'judul' => 'Perulangan rahasia pengguna lain',
            'kategori' => $category->name,
            'category_id' => $category->id,
            'isi' => 'Materi ini tidak boleh masuk ke konteks saya.',
        ]);
        MediaFavorite::create([
            'user_id' => $user->id,
            'title' => 'Video loop',
            'url' => 'https://learn.example.org/loop',
            'platform' => 'other',
            'category_id' => $category->id,
            'description' => 'Video belajar perulangan.',
        ]);
        AiTranscription::create([
            'user_id' => $user->id,
            'title' => 'Transkrip yang tidak dipakai chatbot',
            'original_name' => 'loop.mp4',
            'source_path' => 'ai/loop.mp4',
            'status' => 'completed',
            'transcript' => 'Isi media tidak boleh masuk ke konteks chatbot.',
        ]);

        Http::fake(function ($request) {
            if (str_ends_with($request->url(), '/api/tags')) {
                return Http::response([
                    'models' => [
                        ['name' => 'qwen3:1.7b'],
                        ['name' => 'nomic-embed-text:latest'],
                    ],
                ]);
            }
            if (str_ends_with($request->url(), '/api/embed')) {
                return Http::response([
                    'embeddings' => array_fill(0, count($request['input']), [1.0, 0.0]),
                ]);
            }

            return Http::response(
                json_encode(['response' => 'For loop mengulang instruksi sesuai kondisi.', 'done' => true])."\n"
            );
        });

        $response = $this->withSession(['auth_provider' => 'google'])
            ->actingAs($user)->postJson(route('ai.chat'), [
            'message' => 'Jelaskan perulangan',
        ]);

        $response->assertOk();
        $events = collect(explode("\n", $response->streamedContent()))
            ->filter()
            ->map(fn (string $line) => json_decode($line, true))
            ->values();

        $this->assertSame('conversation', $events[0]['type']);
        $this->assertSame('chunk', $events[1]['type']);
        $this->assertSame('done', $events[2]['type']);
        $this->assertCount(1, $events[2]['source_refs']);
        $this->assertSame('catatan', $events[2]['source_refs'][0]['type']);
        $this->assertDatabaseHas('ai_material_chunks', ['user_id' => $user->id, 'source_type' => 'catatan']);
        $this->assertDatabaseMissing('ai_material_chunks', ['user_id' => $user->id, 'source_type' => 'media']);
        $this->assertDatabaseMissing('ai_material_chunks', ['user_id' => $user->id, 'source_type' => 'transkripsi']);
        $this->assertDatabaseMissing('ai_material_chunks', ['user_id' => $otherUser->id]);

        Http::assertSent(function ($request) use ($otherUser) {
            if (! str_ends_with($request->url(), '/api/generate')) {
                return false;
            }

            $prompt = $request['prompt'];

            return str_contains($prompt, 'Perulangan milik saya')
                && ! str_contains($prompt, 'rahasia pengguna lain')
                && ! str_contains($prompt, 'Isi media tidak boleh masuk')
                && str_contains($prompt, 'Hasil retrieval berikut dibatasi pada sumber terpilih')
                && $request['think'] === false
                && (int) $otherUser->id > 0;
        });
    }

    public function test_task_counts_goal_counts_and_progress_are_answered_from_structured_facts(): void
    {
        $user = User::factory()->create();
        foreach ([
            'pending', 'pending', 'pending',
            'in_progress',
            'completed', 'completed',
        ] as $status) {
            Task::create([
                'user_id' => $user->id,
                'title' => 'Synthetic '.$status.' task',
                'status' => $status,
            ]);
        }
        Goal::create([
            'user_id' => $user->id,
            'name' => 'Baca buku',
            'target_value' => 20,
            'current_value' => 15,
            'unit' => 'halaman',
            'start_date' => today(),
            'status' => 'active',
        ]);
        Http::fake();

        $this->actingAs($user)->postJson(route('ai.chat'), [
            'message' => 'Berapa jumlah task saya?',
        ])->assertOk();
        $taskAnswer = $this->lastAssistantMessage($user);
        $this->assertSame('Jumlah tugas: 6 total (pending 3, sedang dikerjakan 1, tuntas 2).', $taskAnswer);

        $this->postJson(route('ai.chat'), [
            'message' => 'Berapa task yang belum selesai?',
        ])->assertOk();
        $unfinishedAnswer = $this->lastAssistantMessage($user);
        $this->assertSame(
            'Tugas yang belum tuntas berjumlah 4 (pending 3, sedang dikerjakan 1).',
            $unfinishedAnswer
        );

        $this->postJson(route('ai.chat'), [
            'message' => 'Berapa jumlah goal saya?',
        ])->assertOk();
        $goalCountAnswer = $this->lastAssistantMessage($user);
        $this->assertSame(
            'Jumlah goal: 1. Total progres tersimpan: 15 dari 20 (agregat nilai progres, bukan jumlah goal atau persentase).',
            $goalCountAnswer
        );

        $this->postJson(route('ai.chat'), [
            'message' => 'Bagaimana progres goal Baca buku?',
        ])->assertOk();
        $goalProgressAnswer = $this->lastAssistantMessage($user);
        $this->assertSame(
            'Progres goal "Baca buku": 15 dari 20 halaman (75%); sisa 5 halaman.',
            $goalProgressAnswer
        );
        Http::assertNothingSent();
    }

    public function test_task_details_are_returned_from_matching_database_facts_without_generation(): void
    {
        $user = User::factory()->create();
        Task::create([
            'user_id' => $user->id,
            'title' => 'Menyelesaikan laporan PKL',
            'description' => 'Finalisasi laporan praktik kerja lapangan.',
            'status' => 'in_progress',
            'due_date' => '2026-11-10',
        ]);
        Http::fake();
        $this->actingAs($user);

        foreach ([
            'Apa nama task saya yang terkait laporan?' => 'Nama tugas: Menyelesaikan laporan PKL.',
            'Apa deskripsi tugas Menyelesaikan laporan PKL?' => 'Deskripsi tugas "Menyelesaikan laporan PKL": Finalisasi laporan praktik kerja lapangan.',
            'Bagaimana status tugas saya untuk Menyelesaikan laporan PKL?' => 'Status tugas "Menyelesaikan laporan PKL": sedang dikerjakan.',
        ] as $question => $expectedAnswer) {
            $this->postJson(route('ai.chat'), ['message' => $question])->assertOk();
            $this->assertSame($expectedAnswer, $this->lastAssistantMessage($user));
        }

        Http::assertNothingSent();
    }

    public function test_workspace_summary_uses_database_aggregates_and_discloses_sample_limits(): void
    {
        $user = User::factory()->create();
        $category = Category::query()->firstOrCreate(['name' => 'Synthetic workspace']);
        Catatan::create([
            'user_id' => $user->id,
            'judul' => 'Synthetic note one',
            'kategori' => $category->name,
            'category_id' => $category->id,
            'isi' => 'First synthetic note body',
        ]);
        Catatan::create([
            'user_id' => $user->id,
            'judul' => 'Synthetic note two',
            'kategori' => $category->name,
            'category_id' => $category->id,
            'isi' => 'Second synthetic note body',
        ]);
        foreach (['pending', 'pending', 'pending', 'in_progress', 'completed', 'completed'] as $status) {
            Task::create([
                'user_id' => $user->id,
                'title' => 'Synthetic workspace '.$status.' task',
                'status' => $status,
            ]);
        }
        Goal::create([
            'user_id' => $user->id,
            'name' => 'Synthetic workspace goal',
            'target_value' => 20,
            'current_value' => 15,
            'unit' => 'halaman',
            'start_date' => today(),
            'status' => 'active',
        ]);
        Http::fake();

        $response = $this->actingAs($user)->postJson(route('ai.chat'), [
            'message' => 'Bagaimana kondisi workspace saya?',
        ])->assertOk();
        $events = $this->streamEvents($response->streamedContent());
        $answer = $events[1]['text'];

        $this->assertStringContainsString('- Catatan: 2.', $answer);
        $this->assertStringContainsString('- Tugas: 6 total; pending 3, sedang dikerjakan 1, tuntas 2.', $answer);
        $this->assertStringContainsString('- Goal: 1; total progres tersimpan 15 dari 20', $answer);
        $this->assertStringContainsString('Kategori global/shared tersedia:', $answer);
        $this->assertStringContainsString('tidak mengklaim daftar lengkap', $answer);
        Http::assertNothingSent();
    }

    public function test_two_google_linked_users_have_isolated_chat_context_sources_and_history(): void
    {
        $userA = User::factory()->create(['google_id' => 'google-isolation-a']);
        $userB = User::factory()->create(['google_id' => 'google-isolation-b']);
        $category = Category::where('name', 'Pemrograman')->firstOrFail();
        $noteA = Catatan::create([
            'user_id' => $userA->id,
            'judul' => 'Catatan privat user A',
            'kategori' => $category->name,
            'category_id' => $category->id,
            'isi' => 'KODE_RAHASIA_USER_A menjelaskan algoritma pencarian biner.',
        ]);
        $noteB = Catatan::create([
            'user_id' => $userB->id,
            'judul' => 'Catatan privat user B',
            'kategori' => $category->name,
            'category_id' => $category->id,
            'isi' => 'KODE_RAHASIA_USER_B menjelaskan konsep normalisasi basis data.',
        ]);

        Http::fake(function ($request) {
            if (str_ends_with($request->url(), '/api/tags')) {
                return Http::response([
                    'models' => [
                        ['name' => 'qwen3:1.7b'],
                        ['name' => 'nomic-embed-text:latest'],
                    ],
                ]);
            }

            if (str_ends_with($request->url(), '/api/embed')) {
                return Http::response([
                    'embeddings' => array_fill(0, count($request['input']), [1.0, 0.0]),
                ]);
            }

            return Http::response(
                json_encode(['response' => 'Jawaban hanya dari catatan akun aktif.', 'done' => true])."\n"
            );
        });

        $responseA = $this->withSession(['auth_provider' => 'google'])
            ->actingAs($userA)->postJson(route('ai.chat'), [
            'message' => 'Jelaskan algoritma pencarian biner dari catatan saya.',
        ]);
        $responseA->assertOk();
        $eventsA = $this->streamEvents($responseA->streamedContent());
        $conversationA = AiChatConversation::findOrFail($eventsA[0]['conversation_id']);
        $this->assertSame($userA->id, $conversationA->user_id);
        $this->assertSame('Catatan privat user A', $eventsA[2]['source_refs'][0]['title']);
        $this->assertSame(route('catatan.show', $noteA->id), $eventsA[2]['source_refs'][0]['link']);

        $responseB = $this->withSession(['auth_provider' => 'google'])
            ->actingAs($userB)->postJson(route('ai.chat'), [
            'message' => 'Rangkum konsep normalisasi basis data dari catatan saya.',
        ]);
        $responseB->assertOk();
        $eventsB = $this->streamEvents($responseB->streamedContent());
        $conversationB = AiChatConversation::findOrFail($eventsB[0]['conversation_id']);
        $this->assertSame($userB->id, $conversationB->user_id);
        $this->assertSame('Catatan privat user B', $eventsB[2]['source_refs'][0]['title']);
        $this->assertSame(route('catatan.show', $noteB->id), $eventsB[2]['source_refs'][0]['link']);
        $this->assertNotSame($conversationA->id, $conversationB->id);

        $prompts = Http::recorded()
            ->filter(fn (array $record) => str_ends_with($record[0]->url(), '/api/generate'))
            ->map(fn (array $record) => $record[0]['prompt'])
            ->values();
        $this->assertCount(2, $prompts);
        $this->assertStringContainsString('KODE_RAHASIA_USER_A', $prompts[0]);
        $this->assertStringNotContainsString('KODE_RAHASIA_USER_B', $prompts[0]);
        $this->assertStringContainsString('KODE_RAHASIA_USER_B', $prompts[1]);
        $this->assertStringNotContainsString('KODE_RAHASIA_USER_A', $prompts[1]);

        $this->withSession(['auth_provider' => 'google'])
            ->actingAs($userA)
            ->postJson(route('ai.chat'), [
                'message' => 'Tampilkan riwayat lain',
                'conversation_id' => $conversationB->id,
            ])
            ->assertNotFound();
        $this->get(route('ai.conversation.show', $conversationB))->assertForbidden();
        $this->assertDatabaseCount('ai_chat_messages', 4);

        $historyA = $this->getJson(route('ai.conversation.show', $conversationA))->assertOk()->json();
        $historyB = $this->withSession(['auth_provider' => 'google'])
            ->actingAs($userB)->getJson(route('ai.conversation.show', $conversationB))->assertOk()->json();
        $this->assertSame($conversationA->id, $historyA['conversation_id']);
        $this->assertSame($conversationB->id, $historyB['conversation_id']);
        $this->assertStringNotContainsString('USER_B', json_encode($historyA));
        $this->assertStringNotContainsString('USER_A', json_encode($historyB));
    }

    public function test_current_database_state_takes_precedence_over_stale_history_and_summary(): void
    {
        $user = User::factory()->create();
        $task = Task::create([
            'user_id' => $user->id,
            'title' => 'Membuat laporan PKL',
            'description' => 'Draft awal',
            'status' => 'pending',
        ]);
        $task->update([
            'title' => 'Menyelesaikan laporan PKL',
            'description' => 'Versi akhir laporan',
        ]);

        $conversation = AiChatConversation::create([
            'user_id' => $user->id,
            'title' => 'Status task',
            'context_summary' => 'Task saat ini bernama Membuat laporan PKL dengan status completed.',
        ]);
        AiChatMessage::create([
            'conversation_id' => $conversation->id,
            'role' => 'assistant',
            'content' => 'Task Membuat laporan PKL masih pending.',
        ]);

        Http::fake(function ($request) {
            if (str_ends_with($request->url(), '/api/tags')) {
                return Http::response(['models' => [['name' => 'qwen3:1.7b']]], 200);
            }

            return Http::response(
                json_encode(['response' => 'Saya menggunakan data task terkini.', 'done' => true])."\n"
            );
        });

        $this->actingAs($user)->postJson(route('ai.chat'), [
            'message' => 'Apa nama task saya sekarang?',
            'conversation_id' => $conversation->id,
        ])->assertOk()->streamedContent();

        $currentAnswer = $conversation->messages()->where('role', 'assistant')->latest('id')->value('content');
        $this->assertSame('Nama tugas: Menyelesaikan laporan PKL.', $currentAnswer);
        Http::assertNothingSent();

        $task->delete();
        $this->actingAs($user)->postJson(route('ai.chat'), [
            'message' => 'Apa tugas saya sekarang?',
            'conversation_id' => $conversation->id,
        ])->assertOk()->streamedContent();

        $prompts = Http::recorded()
            ->filter(fn (array $record) => str_ends_with($record[0]->url(), '/api/generate'))
            ->map(fn (array $record) => $record[0]['prompt'])
            ->values();
        $this->assertCount(1, $prompts);
        $this->assertStringContainsString('Tidak ada tugas belum tuntas yang tersimpan.', $prompts[0]);
        $this->assertStringNotContainsString('Membuat laporan PKL', $prompts[0]);
        $this->assertStringNotContainsString('Menyelesaikan laporan PKL', $prompts[0]);
        $this->assertStringContainsString('Salin jumlah dan status persis', $prompts[0]);
        $this->assertStringContainsString('daftar yang diberi label contoh', $prompts[0]);
    }

    public function test_workspace_chat_uses_live_aggregates_and_keeps_shared_categories_separate_from_private_items(): void
    {
        $owner = User::factory()->create();
        $otherUser = User::factory()->create();
        $category = Category::create(['name' => 'Workspace Prompt Category']);
        Task::create([
            'user_id' => $owner->id,
            'category_id' => $category->id,
            'title' => 'Owner live task',
            'status' => 'pending',
        ]);
        Task::create([
            'user_id' => $otherUser->id,
            'category_id' => $category->id,
            'title' => 'Foreign private task',
            'status' => 'completed',
        ]);
        $conversation = AiChatConversation::create([
            'user_id' => $owner->id,
            'title' => 'Workspace',
            'context_summary' => 'Kondisi lama menyebut Foreign private task sebagai milik saya.',
        ]);

        Http::fake(function ($request) {
            if (str_ends_with($request->url(), '/api/tags')) {
                return Http::response(['models' => [['name' => 'qwen3:1.7b']]], 200);
            }

            return Http::response(
                json_encode(['response' => 'Ringkasan dari workspace saat ini.', 'done' => true])."\n"
            );
        });

        $response = $this->actingAs($owner)->postJson(route('ai.chat'), [
            'message' => 'Kategori mana yang paling banyak memiliki catatan?',
            'conversation_id' => $conversation->id,
        ]);
        $response->assertOk();
        $events = $this->streamEvents($response->streamedContent());
        $this->assertSame('done', $events[2]['type']);
        $this->assertContains('kategori', array_column($events[2]['source_refs'], 'type'));

        $prompt = Http::recorded()
            ->filter(fn (array $record) => str_ends_with($record[0]->url(), '/api/generate'))
            ->last()[0]['prompt'];
        $this->assertStringContainsString('Kategori yang tersedia bersifat global/shared', $prompt);
        $this->assertStringContainsString('Workspace Prompt Category: 0 catatan / 1 tugas.', $prompt);
        $this->assertStringContainsString('Tugas: 1 total; status terpisah: pending 1, sedang dikerjakan 0, tuntas 0.', $prompt);
        $this->assertStringContainsString('Owner live task', $prompt);
        $this->assertStringNotContainsString('Foreign private task', $prompt);
        $this->assertStringNotContainsString('Kondisi lama menyebut Foreign private task', $prompt);
    }

    public function test_chatbot_includes_only_the_active_users_productivity_data_and_sources(): void
    {
        $user = User::factory()->create(['google_id' => 'google-productivity-owner']);
        $otherUser = User::factory()->create(['google_id' => 'google-productivity-other']);
        Task::create([
            'user_id' => $user->id,
            'title' => 'Owner task marker',
            'status' => 'pending',
            'due_date' => today()->addDays(1),
        ]);
        Task::create([
            'user_id' => $otherUser->id,
            'title' => 'Foreign task marker',
            'status' => 'pending',
        ]);
        Goal::create([
            'user_id' => $user->id,
            'name' => 'Owner goal marker',
            'target_value' => 10,
            'current_value' => 2,
            'unit' => 'pages',
            'start_date' => today(),
            'status' => 'active',
        ]);
        Goal::create([
            'user_id' => $otherUser->id,
            'name' => 'Foreign goal marker',
            'target_value' => 10,
            'current_value' => 4,
            'unit' => 'pages',
            'start_date' => today(),
            'status' => 'active',
        ]);

        Http::fake(function ($request) {
            if (str_ends_with($request->url(), '/api/tags')) {
                return Http::response([
                    'models' => [
                        ['name' => 'qwen3:1.7b'],
                        ['name' => 'nomic-embed-text:latest'],
                    ],
                ]);
            }
            if (str_ends_with($request->url(), '/api/embed')) {
                return Http::response([
                    'embeddings' => array_fill(0, count($request['input']), [1.0, 0.0]),
                ]);
            }

            return Http::response(
                json_encode(['response' => 'Ringkasan data produktivitas pribadi.', 'done' => true])."\n"
            );
        });

        $response = $this->withSession(['auth_provider' => 'google'])
            ->actingAs($user)->postJson(route('ai.chat'), [
                'message' => 'Apa tugas saya dan progres target saya?',
            ]);

        $response->assertOk();
        $events = $this->streamEvents($response->streamedContent());
        $this->assertCount(2, $events[2]['source_refs']);
        $this->assertSame(['tugas', 'target'], array_column($events[2]['source_refs'], 'type'));
        Http::assertSent(function ($request) use ($otherUser) {
            if (! str_ends_with($request->url(), '/api/generate')) {
                return false;
            }

            return str_contains($request['prompt'], 'Owner task marker')
                && str_contains($request['prompt'], 'Owner goal marker')
                && ! str_contains($request['prompt'], 'Foreign task marker')
                && ! str_contains($request['prompt'], 'Foreign goal marker')
                && (int) $otherUser->id > 0;
        });
    }

    public function test_guest_cannot_use_the_chatbot_but_manual_accounts_can(): void
    {
        $this->get(route('ai.index'))->assertRedirect(route('login', [
            'login_required' => 1,
            'redirect_to' => '/ai',
        ]));
        $this->postJson(route('ai.chat'), ['message' => 'Pertanyaan untuk chatbot'])
            ->assertUnauthorized();

        $emailAccount = User::factory()->create(['google_id' => null]);
        $this->actingAs($emailAccount)->get(route('ai.index'))->assertOk();
    }

    public function test_chat_history_and_material_pages_are_scoped_to_the_owner(): void
    {
        $owner = User::factory()->create();
        $otherUser = User::factory()->create();
        $conversation = AiChatConversation::create([
            'user_id' => $owner->id,
            'title' => 'Percakapan privat',
        ]);
        $note = Catatan::create([
            'user_id' => $owner->id,
            'judul' => 'Catatan privat',
            'kategori' => 'Pemrograman',
            'isi' => 'Isi privat.',
        ]);

        $this->actingAs($otherUser)
            ->get(route('ai.conversation.show', $conversation))
            ->assertForbidden();

        $this->get(route('catatan.show', $note->id))->assertNotFound();
    }

    public function test_chat_continues_when_embedding_service_is_unavailable(): void
    {
        $user = User::factory()->create();
        $category = Category::where('name', 'Pemrograman')->firstOrFail();
        Catatan::create([
            'user_id' => $user->id,
            'judul' => 'Catatan PHP',
            'kategori' => $category->name,
            'category_id' => $category->id,
            'isi' => 'PHP digunakan untuk pemrograman web sisi server.',
        ]);

        Http::fake(function ($request) {
            if (str_ends_with($request->url(), '/api/tags')) {
                return Http::response([
                    'models' => [
                        ['name' => 'qwen3:1.7b'],
                        ['name' => 'nomic-embed-text:latest'],
                    ],
                ]);
            }
            if (str_ends_with($request->url(), '/api/embed')) {
                return Http::response(['error' => 'embedding unavailable'], 503);
            }
            return Http::response(
                json_encode(['response' => 'Jawaban umum tetap tersedia meski embedding tidak dapat digunakan.', 'done' => true])."\n"
            );
        });

        $response = $this->actingAs($user)->postJson(route('ai.chat'), [
                'message' => 'Apa itu PHP?',
            ]);

        $response->assertOk();
        $events = $this->streamEvents($response->streamedContent());
        $this->assertSame('conversation', $events[0]['type']);
        $this->assertSame('done', $events[count($events) - 1]['type']);
    }

    public function test_chat_page_does_not_expose_internal_ollama_configuration(): void
    {
        $this->actingAs(User::factory()->create())
            ->get(route('ai.index'))
            ->assertOk()
            ->assertSee('Apa yang ingin kamu rencanakan?')
            ->assertDontSee('127.0.0.1:11434')
            ->assertDontSee('qwen3:1.7b');
    }

    private function streamEvents(string $stream): array
    {
        return collect(explode("\n", $stream))
            ->filter()
            ->map(fn (string $line) => json_decode($line, true))
            ->values()
            ->all();
    }

    private function lastAssistantMessage(User $user): string
    {
        $conversation = AiChatConversation::query()
            ->where('user_id', $user->id)
            ->latest('id')
            ->firstOrFail();

        return (string) $conversation->messages()->where('role', 'assistant')->latest('id')->value('content');
    }

    public function test_users_cannot_open_another_users_media_or_private_attachment(): void
    {
        Storage::fake('learning_private');
        $owner = User::factory()->create();
        $otherUser = User::factory()->create();
        $category = Category::where('name', 'Pemrograman')->firstOrFail();
        $note = Catatan::create([
            'user_id' => $owner->id,
            'judul' => 'Lampiran privat',
            'kategori' => $category->name,
            'category_id' => $category->id,
            'isi' => 'Materi privat.',
        ]);
        $attachment = $note->attachments()->create([
            'disk' => 'learning_private',
            'path' => 'catatan/'.$note->id.'/private.pdf',
            'original_name' => 'private.pdf',
            'extension' => 'pdf',
            'mime_type' => 'application/pdf',
            'size' => 100,
        ]);
        $media = MediaFavorite::create([
            'user_id' => $owner->id,
            'title' => 'Video privat',
            'url' => 'https://learn.example.org/private',
            'platform' => 'other',
            'category_id' => $category->id,
        ]);

        $this->actingAs($otherUser)
            ->get(route('attachments.download', $attachment))
            ->assertNotFound();

        $this->get(route('media-favorites.show', $media))
            ->assertNotFound();
    }
}
