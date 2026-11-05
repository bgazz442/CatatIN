<?php

namespace App\Http\Controllers;

use App\Models\Goal;
use App\Rules\AlphanumericTitle;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Throwable;

class GoalController extends Controller
{
    public function index(Request $request): View
    {
        return view('goals.index', [
            'goals' => Goal::query()
                ->where('user_id', $request->user()->id)
                ->withCount('progressEntries')
                ->orderByRaw("CASE WHEN status = 'active' THEN 0 ELSE 1 END")
                ->orderBy('deadline')
                ->latest()
                ->paginate(12),
        ]);
    }

    public function create(): View
    {
        return view('goals.form', ['goal' => new Goal(['start_date' => today()])]);
    }

    public function store(Request $request): RedirectResponse|JsonResponse
    {
        $validated = $this->validated($request);
        $image = $validated['image'] ?? null;
        unset($validated['image']);
        $validated['user_id'] = $request->user()->id;
        $validated['current_value'] = 0;
        $validated['status'] = 'active';

        if ($image) {
            $validated['image_path'] = $this->storeImage($image, $request->user()->id);
        }

        try {
            $goal = Goal::create($validated);
        } catch (Throwable $exception) {
            if (isset($validated['image_path'])) {
                Storage::disk('learning_private')->delete($validated['image_path']);
            }

            throw $exception;
        }

        if ($request->expectsJson()) {
            return response()->json([
                'message' => 'Goal berhasil dibuat.',
                'data' => $this->goalData($goal),
                'redirect_url' => route('goals.show', $goal),
            ], 201);
        }

        return redirect()->route('goals.show', $goal)->with('success', 'Goal berhasil dibuat.');
    }

    public function show(Request $request, Goal $goal): View
    {
        $this->assertOwner($request, $goal);

        return view('goals.show', [
            'goal' => $goal,
            'progressEntries' => $goal->progressEntries()
                ->where('user_id', $request->user()->id)
                ->latest('recorded_at')
                ->paginate(15),
        ]);
    }

    public function edit(Request $request, Goal $goal): View
    {
        $this->assertOwner($request, $goal);

        return view('goals.form', compact('goal'));
    }

    public function image(Request $request, Goal $goal)
    {
        $this->assertOwner($request, $goal);
        $path = $goal->image_path;
        $expectedDirectory = 'goals/'.$request->user()->id.'/';

        abort_unless(is_string($path) && str_starts_with($path, $expectedDirectory), 404);

        $disk = Storage::disk('learning_private');
        abort_unless($disk->exists($path), 404);
        $absolutePath = $disk->path($path);
        $mimeType = mime_content_type($absolutePath);
        abort_unless(in_array($mimeType, ['image/jpeg', 'image/png', 'image/webp'], true), 404);

        $response = response()->file($absolutePath, [
            'Content-Type' => $mimeType,
            'X-Content-Type-Options' => 'nosniff',
        ]);
        $response->setPrivate();
        $response->headers->set('Cache-Control', 'private, no-store');

        return $response;
    }

    public function update(Request $request, Goal $goal): RedirectResponse|JsonResponse
    {
        $this->assertOwner($request, $goal);
        $validated = $this->validated($request);
        $image = $validated['image'] ?? null;
        unset($validated['image']);
        $validated['status'] = (float) $goal->current_value >= (float) $validated['target_value']
            ? 'completed'
            : 'active';
        $oldImagePath = $goal->image_path;
        $newImagePath = $image ? $this->storeImage($image, $request->user()->id) : null;
        $removeImage = ! $image && $request->boolean('remove_image');

        if ($newImagePath || $removeImage) {
            $validated['image_path'] = $newImagePath;
        }

        try {
            $goal->update($validated);
        } catch (Throwable $exception) {
            if ($newImagePath) {
                Storage::disk('learning_private')->delete($newImagePath);
            }

            throw $exception;
        }

        if (($newImagePath || $removeImage) && $oldImagePath) {
            Storage::disk('learning_private')->delete($oldImagePath);
        }

        if ($request->expectsJson()) {
            return response()->json([
                'message' => 'Target berhasil diperbarui.',
                'data' => $this->goalData($goal->fresh()),
                'redirect_url' => route('goals.show', $goal),
            ]);
        }

        return redirect()->route('goals.show', $goal)->with('success', 'Target berhasil diperbarui.');
    }

    public function addProgress(Request $request, Goal $goal): RedirectResponse|JsonResponse
    {
        $this->assertOwner($request, $goal);
        $validated = $request->validate([
            'amount' => ['required', 'numeric', 'gt:0', 'decimal:0,2'],
            'note' => ['nullable', 'string', 'max:500'],
        ]);

        $progressEntry = DB::transaction(function () use ($request, $goal, $validated) {
            $lockedGoal = Goal::query()
                ->where('user_id', $request->user()->id)
                ->whereKey($goal->id)
                ->lockForUpdate()
                ->firstOrFail();
            $currentCents = (int) round((float) $lockedGoal->current_value * 100);
            $addedCents = (int) round((float) $validated['amount'] * 100);
            $nextValue = number_format(($currentCents + $addedCents) / 100, 2, '.', '');

            $entry = $lockedGoal->progressEntries()->create([
                'user_id' => $request->user()->id,
                'amount' => $validated['amount'],
                'note' => $validated['note'] ?? null,
                'recorded_at' => now(),
            ]);
            $lockedGoal->update([
                'current_value' => $nextValue,
                'status' => (float) $nextValue >= (float) $lockedGoal->target_value ? 'completed' : 'active',
            ]);

            return $entry;
        });

        if ($request->expectsJson()) {
            $goal->refresh();

            return response()->json([
                'message' => 'Progres berhasil ditambahkan ke riwayat target.',
                'data' => [
                    ...$this->goalData($goal),
                    'progress_entry' => [
                        'amount' => $progressEntry->amount,
                        'note' => $progressEntry->note,
                        'recorded_at' => $progressEntry->recorded_at->format('d M Y H:i'),
                    ],
                ],
            ]);
        }

        return redirect()->route('goals.show', $goal)->with('success', 'Progres berhasil ditambahkan ke riwayat target.');
    }

    public function destroy(Request $request, Goal $goal): RedirectResponse|JsonResponse
    {
        $this->assertOwner($request, $goal);
        $imagePath = $goal->image_path;
        $goal->delete();
        if ($imagePath) {
            Storage::disk('learning_private')->delete($imagePath);
        }

        if ($request->expectsJson()) {
            return response()->json(['message' => 'Target dan riwayat progresnya berhasil dihapus.', 'data' => ['id' => $goal->id]]);
        }

        return redirect()->route('goals.index')->with('success', 'Target dan riwayat progresnya berhasil dihapus.');
    }

    private function goalData(Goal $goal): array
    {
        return [
            'id' => $goal->id,
            'name' => $goal->name,
            'description' => $goal->description,
            'current_value' => (float) $goal->current_value,
            'target_value' => (float) $goal->target_value,
            'unit' => $goal->unit,
            'start_date' => $goal->start_date?->format('Y-m-d'),
            'deadline' => $goal->deadline?->format('Y-m-d'),
            'status' => $goal->status,
            'percentage' => $goal->percentage(),
            'remaining' => $goal->remaining(),
            'image_url' => $goal->image_path ? route('goals.image', $goal) : null,
        ];
    }

    private function validated(Request $request): array
    {
        $request->merge(['name' => trim((string) $request->input('name'))]);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:100', new AlphanumericTitle],
            'description' => ['nullable', 'string', 'max:5000'],
            'target_value' => ['required', 'numeric', 'gt:0', 'decimal:0,2'],
            'unit' => ['required', 'string', 'max:40'],
            'start_date' => ['required', 'date'],
            'deadline' => ['nullable', 'date', 'after_or_equal:start_date'],
            'image' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120', 'dimensions:max_width=4096,max_height=4096'],
            'remove_image' => ['sometimes', 'boolean'],
        ]);

        if ($request->routeIs('goals.store') && isset($request->current_value)) {
            throw ValidationException::withMessages([
                'current_value' => 'Progres awal ditambahkan melalui riwayat progres target.',
            ]);
        }

        return $validated;
    }

    private function storeImage(UploadedFile $image, int $userId): string
    {
        $path = $image->store('goals/'.$userId, 'learning_private');
        if (! is_string($path) || $path === '') {
            throw new \RuntimeException('Goal image could not be stored.');
        }

        return $path;
    }

    private function assertOwner(Request $request, Goal $goal): void
    {
        abort_unless($goal->user_id === $request->user()->id, 404);
    }
}
