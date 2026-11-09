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
        $validated = $this->validated($request, $goal);
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
        if ($request->boolean('use_progress_step')) {
            $request->merge(['amount' => $goal->progress_step]);
        }
        $request->merge(['direction' => $request->input('direction', 'add')]);
        $validated = $request->validate([
            'amount' => [
                'required',
                'numeric',
                'gt:0',
                $goal->goal_type === 'money' ? 'decimal:0,0' : 'decimal:0,2',
            ],
            'note' => ['nullable', 'string', 'max:500'],
            'direction' => ['required', 'in:add,subtract'],
        ]);

        $progressEntry = DB::transaction(function () use ($request, $goal, $validated) {
            $lockedGoal = Goal::query()
                ->where('user_id', $request->user()->id)
                ->whereKey($goal->id)
                ->lockForUpdate()
                ->firstOrFail();
            $currentCents = (int) round((float) $lockedGoal->current_value * 100);
            $amountCents = (int) round((float) $validated['amount'] * 100);
            $signedCents = $validated['direction'] === 'subtract' ? -$amountCents : $amountCents;
            $nextCents = $currentCents + $signedCents;

            if ($nextCents < 0) {
                throw \Illuminate\Validation\ValidationException::withMessages([
                    'amount' => 'Jumlah pengurangan tidak boleh melebihi progres saat ini.',
                ]);
            }
            if ($lockedGoal->goal_type === 'percentage' && $nextCents > (int) round((float) $lockedGoal->target_value * 100)) {
                throw ValidationException::withMessages([
                    'amount' => 'Progres persentase tidak boleh melebihi nilai target.',
                ]);
            }

            $nextValue = number_format($nextCents / 100, 2, '.', '');

            $entry = $lockedGoal->progressEntries()->create([
                'user_id' => $request->user()->id,
                'amount' => number_format($signedCents / 100, 2, '.', ''),
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
                'message' => $validated['direction'] === 'subtract'
                    ? 'Progres berhasil dikurangi dari target.'
                    : 'Progres berhasil ditambahkan ke riwayat target.',
                'data' => [
                    ...$this->goalData($goal),
                    'progress_entry' => [
                        'amount' => $progressEntry->amount,
                        'label' => $goal->formattedProgressEntry($progressEntry->amount),
                        'note' => $progressEntry->note,
                        'recorded_at' => $progressEntry->recorded_at->format('d M Y H:i'),
                    ],
                ],
            ]);
        }

        return redirect()->route('goals.show', $goal)->with(
            'success',
            $validated['direction'] === 'subtract'
                ? 'Progres berhasil dikurangi dari target.'
                : 'Progres berhasil ditambahkan ke riwayat target.'
        );
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
            'goal_type' => $goal->goal_type,
            'description' => $goal->description,
            'current_value' => (float) $goal->current_value,
            'target_value' => (float) $goal->target_value,
            'progress_step' => (float) $goal->progress_step,
            'unit' => $goal->unit,
            'formatted_current_value' => $goal->formattedValue($goal->current_value),
            'formatted_target_value' => $goal->formattedValue($goal->target_value),
            'formatted_remaining_value' => $goal->formattedValue($goal->remaining()),
            'remaining_label' => $goal->remainingLabel(),
            'start_date' => $goal->start_date?->format('Y-m-d'),
            'deadline' => $goal->deadline?->format('Y-m-d'),
            'status' => $goal->status,
            'percentage' => $goal->percentage(),
            'remaining' => $goal->remaining(),
            'image_url' => $goal->image_path ? route('goals.image', $goal) : null,
        ];
    }

    private function validated(Request $request, ?Goal $goal = null): array
    {
        $request->merge(['name' => trim((string) $request->input('name'))]);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:100', new AlphanumericTitle],
            'goal_type' => ['sometimes', 'in:money,percentage'],
            'description' => ['nullable', 'string', 'max:5000'],
            'target_value' => [
                'required',
                'numeric',
                'gt:0',
                ($goal?->goal_type ?? $request->input('goal_type')) === 'money' ? 'decimal:0,0' : 'decimal:0,2',
                ...(($goal?->goal_type ?? $request->input('goal_type')) === 'percentage' ? ['lte:100'] : []),
            ],
            'progress_step' => [
                'sometimes',
                'numeric',
                'gt:0',
                ($goal?->goal_type ?? $request->input('goal_type')) === 'money' ? 'decimal:0,0' : 'decimal:0,2',
                'lte:target_value',
            ],
            'unit' => ['nullable', 'string', 'max:40'],
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

        $goalType = $goal?->goal_type ?? $validated['goal_type'] ?? 'custom';
        if ($goalType === 'money') {
            $validated['unit'] = 'Rupiah';
        } elseif ($goalType === 'percentage') {
            $validated['unit'] = '%';
        } elseif (blank($validated['unit'] ?? null)) {
            throw ValidationException::withMessages([
                'unit' => 'Satuan wajib diisi untuk target dengan satuan khusus.',
            ]);
        }

        $validated['goal_type'] = $goalType;
        $validated['progress_step'] = min(
            (float) ($validated['progress_step'] ?? $goal?->progress_step ?? 1),
            (float) $validated['target_value'],
        );

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
