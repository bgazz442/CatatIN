<?php

namespace App\Services;

use App\Models\Catatan;
use App\Models\CatatanAttachment;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Throwable;

class AttachmentManager
{
    public function assertWithinLimits(?Catatan $catatan, array $files): void
    {
        $limits = config('learning.uploads');
        $existingCount = $catatan?->attachments()->count() ?? 0;
        $existingBytes = $catatan?->attachments()->sum('size') ?? 0;

        if ($existingCount + count($files) > $limits['max_files']) {
            throw ValidationException::withMessages([
                'documents' => 'Maksimal 10 file untuk setiap materi, termasuk lampiran yang sudah tersimpan.',
            ]);
        }

        $newBytes = array_sum(array_map(fn ($file) => (int) $file->getSize(), $files));
        if ($existingBytes + $newBytes > $limits['max_total_bytes']) {
            throw ValidationException::withMessages([
                'documents' => 'Total ukuran seluruh lampiran untuk materi ini tidak boleh melebihi 100 MB.',
            ]);
        }
    }

    public function store(Catatan $catatan, array $files): array
    {
        $disk = config('learning.uploads.disk');
        $storedPaths = [];

        try {
            foreach ($files as $file) {
                $extension = strtolower($file->getClientOriginalExtension());
                $fileName = Str::uuid().'.'.$extension;
                $path = $file->storeAs('catatan/'.$catatan->id, $fileName, $disk);
                $storedPaths[] = $path;
                $originalName = basename(str_replace('\\', '/', $file->getClientOriginalName()));

                $catatan->attachments()->create([
                    'disk' => $disk,
                    'path' => $path,
                    'original_name' => mb_substr($originalName, 0, 255),
                    'extension' => $extension,
                    'mime_type' => $file->getMimeType() ?: 'application/octet-stream',
                    'size' => (int) $file->getSize(),
                ]);
            }
        } catch (Throwable $exception) {
            $this->deletePaths($storedPaths, $disk);
            throw $exception;
        }

        return $storedPaths;
    }

    public function deleteAttachment(CatatanAttachment $attachment): void
    {
        $disk = Storage::disk($attachment->disk);
        if ($disk->exists($attachment->path)) {
            $disk->delete($attachment->path);
        }
    }

    public function deletePaths(array $paths, ?string $disk = null): void
    {
        if ($paths === []) {
            return;
        }

        Storage::disk($disk ?? config('learning.uploads.disk'))->delete($paths);
    }
}