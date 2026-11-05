<?php

namespace Tests\Feature;

use App\Jobs\ProcessAiTranscription;
use App\Models\AiTranscription;
use App\Models\Category;
use App\Models\MediaFavorite;
use App\Models\User;
use App\Support\VideoLinkParser;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class MediaFavoriteTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_is_redirected_to_login_for_media_favorites(): void
    {
        $this->get(route('media-favorites.index'))
            ->assertRedirect(route('login', [
                'login_required' => 1,
                'redirect_to' => '/media-favorit',
            ]));
    }

    public function test_supported_video_links_and_external_https_are_classified(): void
    {
        $this->actingAs(User::factory()->create());
        $category = Category::where('name', 'Pemrograman')->firstOrFail();
        $items = [
            ['youtube', 'https://www.youtube.com/watch?v=dQw4w9WgXcQ', 'dQw4w9WgXcQ'],
            ['tiktok', 'https://www.tiktok.com/@kelas/video/1234567890123456789', '1234567890123456789'],
            ['other', 'https://learn.example.org/course/intro', null],
        ];

        foreach ($items as $index => [$platform, $url, $videoId]) {
            $this->post(route('media-favorites.store'), [
                'title' => 'Media uji '.$index,
                'url' => $url,
                'category_id' => $category->id,
            ])->assertRedirect();
            $this->assertDatabaseHas('media_favorites', [
                'title' => 'Media uji '.$index,
                'platform' => $platform,
                'video_id' => $videoId,
            ]);
        }
    }

    public function test_unsafe_urls_are_rejected_and_embed_is_built_from_validated_id(): void
    {
        $this->actingAs(User::factory()->create());
        $category = Category::where('name', 'Matematika')->firstOrFail();

        foreach ([
            'javascript:alert(1)',
            'http://example.org/video',
            'https://youtube.com/watch?v[]=invalid',
            'https://www.tiktok.com/@kelas/video/not-a-video-id',
        ] as $url) {
            $this->from(route('media-favorites.create'))
                ->post(route('media-favorites.store'), [
                    'title' => 'URL tidak aman',
                    'url' => $url,
                    'category_id' => $category->id,
                ])
                ->assertSessionHasErrors('url');
        }

        $parser = app(VideoLinkParser::class);
        $this->assertSame('https://www.youtube-nocookie.com/embed/dQw4w9WgXcQ', $parser->embedUrl('youtube', 'dQw4w9WgXcQ'));
        $this->assertSame('https://www.tiktok.com/player/v1/1234567890123456789', $parser->embedUrl('tiktok', '1234567890123456789'));
        $this->assertNull($parser->embedUrl('youtube', 'javascript:alert(1)'));
        $this->assertNull($parser->embedUrl('other', '123'));
        $this->assertSame('dQw4w9WgXcQ', $parser->parse('https://youtube.com/shorts/dQw4w9WgXcQ')['video_id']);
        $this->assertDatabaseCount('media_favorites', 0);
    }

    public function test_media_can_be_searched_filtered_sorted_edited_and_favorited(): void
    {
        $this->actingAs(User::factory()->create());
        $category = Category::where('name', 'Basis Data')->firstOrFail();
        $media = MediaFavorite::create([
            'title' => 'Database Normalization',
            'url' => 'https://learn.example.org/db',
            'platform' => 'other',
            'category_id' => $category->id,
            'is_favorite' => false,
        ]);
        $olderMedia = MediaFavorite::create([
            'title' => 'Older database lesson',
            'url' => 'https://learn.example.org/db-old',
            'platform' => 'other',
            'category_id' => $category->id,
            'is_favorite' => true,
        ]);
        $olderMedia->forceFill([
            'created_at' => now()->subDay(),
            'updated_at' => now()->subDay(),
        ])->saveQuietly();

        $this->get(route('media-favorites.index', [
            'search' => 'Normalization',
            'platform' => 'other',
            'category_id' => $category->id,
            'sort' => 'terlama',
        ]))->assertOk()->assertSee('Database Normalization');

        $sorted = $this->get(route('media-favorites.index', [
            'platform' => 'other',
            'category_id' => $category->id,
            'sort' => 'terlama',
        ]));
        $sorted->assertViewHas('mediaItems', fn ($items) => $items->first()->is($olderMedia));

        $this->patch(route('media-favorites.toggle-favorite', $media))->assertRedirect();
        $this->assertTrue($media->fresh()->is_favorite);
        $this->put(route('media-favorites.update', $media), [
            'title' => 'Database Normalization Updated',
            'url' => 'https://learn.example.org/db-updated',
            'category_id' => $category->id,
        ])->assertRedirect(route('media-favorites.show', $media));
        $this->assertSame('Database Normalization Updated', $media->fresh()->title);
    }

    public function test_media_page_has_no_ai_transcription_controls_or_endpoint(): void
    {
        $owner = User::factory()->create();
        $category = Category::where('name', 'Pemrograman')->firstOrFail();
        $media = MediaFavorite::create([
            'user_id' => $owner->id,
            'title' => 'Media tanpa pemrosesan AI',
            'url' => 'https://www.youtube.com/watch?v=dQw4w9WgXcQ',
            'platform' => 'youtube',
            'video_id' => 'dQw4w9WgXcQ',
            'category_id' => $category->id,
        ]);

        $this->actingAs($owner)
            ->get(route('media-favorites.show', $media))
            ->assertOk()
            ->assertDontSee('Transkrip media ini')
            ->assertDontSee('Transkrip belum didukung');

        $this->assertFalse(Route::has('media-favorites.transcribe'));
        $this->assertFalse(Route::has('ai.transcription.store'));
    }

    public function test_non_chatbot_ai_routes_are_retired(): void
    {
        $this->actingAs(User::factory()->create(['google_id' => 'google-retirement-test']));

        foreach ([
            'ai.test',
            'ai.summarize-catatan',
            'ai.save-summary',
            'ai.transcription.index',
            'ai.transcription.create',
            'ai.transcription.store',
            'ai.transcription.show',
        ] as $routeName) {
            $this->assertFalse(Route::has($routeName), 'Retired route still exists: '.$routeName);
        }

        $this->get('/ai/transkripsi')->assertNotFound();
        $this->post('/ai/test')->assertNotFound();
        $this->post('/ai/save-summary')->assertNotFound();
    }

    public function test_queued_transcription_is_disabled_without_deleting_existing_transcript_data(): void
    {
        $queued = AiTranscription::create([
            'user_id' => User::factory()->create()->id,
            'title' => 'Transkripsi antrean lama',
            'original_name' => 'legacy.mp4',
            'source_path' => 'ai/transcriptions/legacy.mp4',
            'status' => 'queued',
            'metadata' => ['source_sha256' => str_repeat('a', 64)],
        ]);
        $completed = AiTranscription::create([
            'user_id' => $queued->user_id,
            'title' => 'Transkripsi yang tersimpan',
            'original_name' => 'saved.mp4',
            'source_path' => 'ai/transcriptions/saved.mp4',
            'status' => 'completed',
            'transcript' => 'Transkrip lama tetap dipertahankan.',
        ]);

        (new ProcessAiTranscription($queued->id))->handle();
        (new ProcessAiTranscription($completed->id))->handle();

        $this->assertSame('failed', $queued->fresh()->status);
        $this->assertSame('Fitur transkripsi AI telah dinonaktifkan.', $queued->fresh()->error_message);
        $this->assertNull($queued->fresh()->transcript);
        $this->assertSame('Transkrip lama tetap dipertahankan.', $completed->fresh()->transcript);
        $this->assertSame('completed', $completed->fresh()->status);
        $this->assertDatabaseCount('ai_transcriptions', 2);
    }

    public function test_category_in_use_by_media_cannot_be_deleted_and_media_can_be_deleted(): void
    {
        $this->actingAs(User::factory()->create());
        $category = Category::where('name', 'Pemrograman')->firstOrFail();
        $media = MediaFavorite::create([
            'title' => 'Video belajar',
            'url' => 'https://www.youtube.com/watch?v=dQw4w9WgXcQ',
            'platform' => 'youtube',
            'video_id' => 'dQw4w9WgXcQ',
            'category_id' => $category->id,
        ]);

        $this->delete(route('categories.destroy', $category))
            ->assertRedirect();
        $this->assertDatabaseHas('media_favorites', ['id' => $media->id]);

        $this->delete(route('media-favorites.destroy', $media))
            ->assertRedirect(route('media-favorites.index'));
        $this->assertDatabaseMissing('media_favorites', ['id' => $media->id]);
    }
}
