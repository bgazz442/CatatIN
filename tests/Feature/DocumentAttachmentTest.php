<?php

namespace Tests\Feature;

use App\Models\Catatan;
use App\Models\Category;
use App\Models\CatatanAttachment;
use App\Models\User;
use App\Services\AttachmentManager;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class DocumentAttachmentTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_sees_login_cta_and_upload_post_is_redirected_to_login(): void
    {
        $this->get(route('catatan.create'))
            ->assertRedirect(route('login', [
                'login_required' => 1,
                'redirect_to' => route('catatan.create', [], false),
            ]));

        $response = $this->post(route('catatan.store'), [
            'judul' => 'Materi guest',
            'kategori' => 'Pemrograman',
            'isi' => '',
            'documents' => [UploadedFile::fake()->create('guest.pdf', 10, 'application/pdf')],
        ]);

        $response->assertRedirect(route('login', [
            'login_required' => 1,
            'redirect_to' => '/catatan',
        ]));
        $this->assertDatabaseCount('catatan', 0);
    }

    public function test_pdf_and_image_uploads_are_private_and_can_be_previewed_downloaded_and_deleted(): void
    {
        Storage::fake('learning_private');
        Storage::fake('public');
        $this->actingAs(User::factory()->create());

        $this->post(route('catatan.store'), $this->notePayload([
            UploadedFile::fake()->create('lesson.pdf', 10, 'application/pdf'),
            UploadedFile::fake()->create('lesson.docx', 10, 'application/vnd.openxmlformats-officedocument.wordprocessingml.document'),
            UploadedFile::fake()->create('lesson.xlsx', 10, 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'),
            UploadedFile::fake()->image('diagram.jpg', 32, 32),
        ]))->assertRedirect(route('catatan.index'));

        $catatan = Catatan::firstOrFail();
        $attachments = $catatan->attachments()->orderBy('id')->get();
        $this->assertCount(4, $attachments);
        foreach ($attachments as $attachment) {
            Storage::disk('learning_private')->assertExists($attachment->path);
            Storage::disk('public')->assertMissing($attachment->path);
            $this->assertMatchesRegularExpression('/^[0-9a-f-]{36}\./', basename($attachment->path));
        }

        $pdf = $attachments->firstWhere('extension', 'pdf');
        $image = $attachments->firstWhere('extension', 'jpg');
        $this->get(route('attachments.preview', $pdf))
            ->assertOk()
            ->assertHeader('Content-Type', 'application/pdf')
            ->assertHeader('X-Content-Type-Options', 'nosniff');
        $this->get(route('attachments.preview', $image))
            ->assertOk()
            ->assertHeader('Content-Type', 'image/jpeg')
            ->assertHeader('X-Content-Type-Options', 'nosniff');
        $this->get(route('attachments.download', $image))
            ->assertOk()
            ->assertHeader('Content-Disposition');

        $this->delete(route('attachments.destroy', $pdf))
            ->assertRedirect(route('catatan.edit', $catatan));
        Storage::disk('learning_private')->assertMissing($pdf->path);
        $this->assertDatabaseMissing('catatan_attachments', ['id' => $pdf->id]);
        $this->assertDatabaseHas('catatan_attachments', ['id' => $image->id]);
    }

    public function test_guest_cannot_preview_or_download_private_attachments(): void
    {
        Storage::fake('learning_private');
        $attachment = $this->createStoredAttachment();

        $this->get(route('attachments.preview', $attachment))
            ->assertRedirect(route('login', ['login_required' => 1, 'redirect_to' => '/lampiran/'.$attachment->id.'/preview']));
        $this->get(route('attachments.download', $attachment))
            ->assertRedirect(route('login', ['login_required' => 1, 'redirect_to' => '/lampiran/'.$attachment->id.'/download']));
        $this->delete(route('attachments.destroy', $attachment))
            ->assertRedirect(route('login', ['login_required' => 1, 'redirect_to' => '/lampiran/'.$attachment->id]));
        Storage::disk('learning_private')->assertExists($attachment->path);
    }

    public function test_edit_keeps_existing_files_and_deleting_note_cleans_private_files(): void
    {
        Storage::fake('learning_private');
        $this->actingAs(User::factory()->create());
        $this->post(route('catatan.store'), $this->notePayload([
            UploadedFile::fake()->create('lesson.pdf', 10, 'application/pdf'),
        ]));
        $catatan = Catatan::firstOrFail();
        $attachment = $catatan->attachments()->firstOrFail();

        $this->put(route('catatan.update', $catatan), [
            'judul' => 'Materi diperbarui',
            'kategori' => 'Basis Data',
            'isi' => '',
        ])->assertRedirect(route('catatan.show', $catatan));

        Storage::disk('learning_private')->assertExists($attachment->path);
        $this->assertDatabaseHas('catatan_attachments', ['id' => $attachment->id]);

        $this->delete(route('catatan.destroy', $catatan));
        Storage::disk('learning_private')->assertMissing($attachment->path);
        $this->assertDatabaseMissing('catatan_attachments', ['id' => $attachment->id]);
    }

    public function test_count_total_size_and_per_file_limits_include_existing_attachments(): void
    {
        Storage::fake('learning_private');
        $user = User::factory()->create();
        $this->actingAs($user);
        $catatan = Catatan::create([
            'user_id' => $user->id,
            'judul' => 'Materi berlampiran',
            'kategori' => 'Pemrograman',
            'isi' => '',
        ]);
        foreach (range(1, 8) as $number) {
            $catatan->attachments()->create($this->attachmentMetadata('existing-'.$number.'.pdf', 1024));
        }

        $this->put(route('catatan.update', $catatan), [
            'judul' => $catatan->judul,
            'kategori' => $catatan->kategori,
            'isi' => '',
            'documents' => array_fill(0, 3, UploadedFile::fake()->create('extra.pdf', 1, 'application/pdf')),
        ])->assertSessionHasErrors('documents');

        $catatan->attachments()->delete();
        $catatan->attachments()->create($this->attachmentMetadata('large-existing.pdf', 99 * 1024 * 1024));
        $this->put(route('catatan.update', $catatan), [
            'judul' => $catatan->judul,
            'kategori' => $catatan->kategori,
            'isi' => '',
            'documents' => [UploadedFile::fake()->create('two-mb.pdf', 2048, 'application/pdf')],
        ])->assertSessionHasErrors('documents');

        $this->put(route('catatan.update', $catatan), [
            'judul' => $catatan->judul,
            'kategori' => $catatan->kategori,
            'isi' => '',
            'documents' => [UploadedFile::fake()->create('too-large.pdf', 51201, 'application/pdf')],
        ])->assertSessionHasErrors('documents.0');
    }

    public function test_disallowed_executable_upload_is_rejected(): void
    {
        $this->actingAs(User::factory()->create());

        $this->post(route('catatan.store'), $this->notePayload([
            UploadedFile::fake()->create('payload.php', 1, 'application/x-php'),
        ]))->assertSessionHasErrors('documents.0');

        $this->assertDatabaseCount('catatan', 0);
    }

    public function test_file_is_removed_when_attachment_metadata_insert_fails(): void
    {
        Storage::fake('learning_private');
        $category = Category::where('name', 'Pemrograman')->firstOrFail();
        $catatan = Catatan::create([
            'judul' => 'Rollback upload',
            'category_id' => $category->id,
            'kategori' => $category->name,
            'isi' => '',
        ]);
        CatatanAttachment::creating(function () {
            throw new \RuntimeException('Simulated metadata database failure.');
        });

        try {
            app(AttachmentManager::class)->store($catatan, [
                UploadedFile::fake()->create('rollback.pdf', 5, 'application/pdf'),
            ]);
            $this->fail('Expected metadata write to fail.');
        } catch (\RuntimeException $exception) {
            $this->assertSame('Simulated metadata database failure.', $exception->getMessage());
        }

        $this->assertSame([], Storage::disk('learning_private')->allFiles('catatan/'.$catatan->id));
        $this->assertDatabaseCount('catatan_attachments', 0);
    }

    private function notePayload(array $files): array
    {
        return [
            'judul' => 'Materi uji lampiran',
            'kategori' => 'Pemrograman',
            'isi' => '',
            'documents' => $files,
        ];
    }

    private function createStoredAttachment(): CatatanAttachment
    {
        $catatan = Catatan::create([
            'judul' => 'Private attachment',
            'kategori' => 'Pemrograman',
            'isi' => 'Private',
        ]);
        $file = UploadedFile::fake()->create('private.pdf', 2, 'application/pdf');
        $path = $file->storeAs('catatan/'.$catatan->id, 'private-test.pdf', 'learning_private');
        $metadata = $this->attachmentMetadata('private.pdf', 2048);
        $metadata['path'] = $path;

        return $catatan->attachments()->create($metadata);
    }

    private function attachmentMetadata(string $name, int $size): array
    {
        return [
            'disk' => 'learning_private',
            'path' => 'catatan/1/'.uniqid().'.pdf',
            'original_name' => $name,
            'extension' => 'pdf',
            'mime_type' => 'application/pdf',
            'size' => $size,
        ];
    }
}