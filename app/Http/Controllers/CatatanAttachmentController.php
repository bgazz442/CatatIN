<?php

namespace App\Http\Controllers;

use App\Models\CatatanAttachment;
use App\Services\AttachmentManager;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\HeaderUtils;
use Symfony\Component\HttpFoundation\StreamedResponse;

class CatatanAttachmentController extends Controller
{
    public function preview(CatatanAttachment $attachment): StreamedResponse
    {
        abort_unless(in_array($attachment->mime_type, config('learning.uploads.preview_mime_types'), true), 415);

        return $this->stream($attachment, 'inline');
    }

    public function download(CatatanAttachment $attachment): StreamedResponse
    {
        return $this->stream($attachment, 'attachment');
    }

    public function destroy(CatatanAttachment $attachment, AttachmentManager $manager)
    {
        $this->assertOwner($attachment);
        $catatanId = $attachment->catatan_id;
        $manager->deleteAttachment($attachment);
        $attachment->delete();

        return redirect()
            ->route('catatan.edit', $catatanId)
            ->with('success', 'Lampiran berhasil dihapus.');
    }

    private function stream(CatatanAttachment $attachment, string $disposition): StreamedResponse
    {
        $this->assertOwner($attachment);
        abort_unless($this->isSafeStoredPath($attachment), 404);

        $disk = Storage::disk($attachment->disk);
        abort_unless($disk->exists($attachment->path), 404);
        $stream = $disk->readStream($attachment->path);
        abort_if($stream === false, 404);

        $filename = basename(str_replace('\\', '/', $attachment->original_name));
        $safeName = preg_replace('/[^A-Za-z0-9._-]/', '_', $filename) ?: 'materi';
        $headers = [
            'Content-Type' => $attachment->mime_type,
            'Content-Disposition' => HeaderUtils::makeDisposition($disposition, $filename, $safeName),
            'Content-Length' => (string) $attachment->size,
            'X-Content-Type-Options' => 'nosniff',
            'Cache-Control' => 'private, no-store, max-age=0',
            'Content-Security-Policy' => "default-src 'none'; sandbox",
        ];

        return response()->stream(function () use ($stream) {
            fpassthru($stream);
            fclose($stream);
        }, 200, $headers);
    }

    private function isSafeStoredPath(CatatanAttachment $attachment): bool
    {
        $prefix = 'catatan/'.$attachment->catatan_id.'/';

        return $attachment->disk === config('learning.uploads.disk')
            && str_starts_with($attachment->path, $prefix)
            && ! str_contains($attachment->path, '..')
            && basename($attachment->path) === basename(str_replace('\\', '/', $attachment->path));
    }

    private function assertOwner(CatatanAttachment $attachment): void
    {
        abort_unless(
            auth()->check()
                && $attachment->catatan()->where('user_id', auth()->id())->exists(),
            404
        );
    }
}