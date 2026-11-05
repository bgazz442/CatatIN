<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\DailyActivity;
use App\Rules\AlphanumericTitle;
use App\Services\ActivityDurationCalculator;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class DailyActivityController extends Controller
{
    public function index(Request $request): View
    {
        $validated = $request->validate([
            'date' => ['nullable', 'date'],
            'category_id' => ['nullable', 'integer', 'exists:categories,id'],
            'month' => ['nullable', 'regex:/^\d{4}-\d{2}$/'],
        ]);

        $selectedDate = $validated['date'] ?? today()->toDateString();
        $month = $validated['month'] ?? substr($selectedDate, 0, 7);

        if (! preg_match('/^\d{4}-\d{2}$/', $month)) {
            $month = substr($selectedDate, 0, 7);
        }

        try {
            $monthStart = Carbon::createFromFormat('!Y-m', $month)->startOfDay();
        } catch (\Throwable) {
            $monthStart = Carbon::today()->startOfMonth();
            $month = $monthStart->format('Y-m');
        }

        if (! str_starts_with($selectedDate, $month)) {
            $selectedDate = $monthStart->toDateString();
        }

        $baseQuery = DailyActivity::query()
            ->where('user_id', $request->user()->id)
            ->with('category')
            ->when($validated['category_id'] ?? null, fn ($activities, int $categoryId) => $activities->where('category_id', $categoryId));

        $activitiesQuery = (clone $baseQuery)->whereDate('activity_date', $selectedDate);
        $totalMinutes = app(ActivityDurationCalculator::class)->totalMinutes($activitiesQuery);
        $activities = $activitiesQuery
            ->orderByRaw('start_time IS NULL')
            ->orderBy('start_time')
            ->orderBy('id')
            ->get();
        $categoryCount = $activities
            ->filter(fn (DailyActivity $activity) => $activity->category_id !== null)
            ->pluck('category_id')
            ->unique()
            ->count();

        $monthEnd = $monthStart->copy()->endOfMonth()->endOfDay();
        $markerRows = (clone $baseQuery)
            ->whereBetween('activity_date', [$monthStart->toDateString(), $monthEnd->toDateString()])
            ->selectRaw('activity_date, COUNT(*) as activity_count')
            ->groupBy('activity_date')
            ->pluck('activity_count', 'activity_date')
            ->map(fn ($count) => (int) $count);

        $calendarStart = $monthStart->copy()->startOfWeek(Carbon::MONDAY);
        $calendarDays = collect();
        for ($day = 0; $day < 42; $day++) {
            $calendarDays->push($calendarStart->copy()->addDays($day));
        }

        return view('activities.index', [
            'activities' => $activities,
            'categories' => Category::query()->orderBy('name')->get(),
            'filters' => array_merge($validated, ['date' => $selectedDate, 'month' => $month]),
            'totalMinutes' => $totalMinutes,
            'selectedDate' => Carbon::parse($selectedDate),
            'monthStart' => $monthStart,
            'calendarDays' => $calendarDays,
            'markerRows' => $markerRows,
            'categoryCount' => $categoryCount,
        ]);
    }

    public function create(): View
    {
        return view('activities.form', [
            'activity' => new DailyActivity(['activity_date' => today()]),
            'categories' => Category::query()->orderBy('name')->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse|JsonResponse
    {
        $activity = DailyActivity::create([
            ...$this->validated($request),
            'user_id' => $request->user()->id,
        ]);

        if ($request->expectsJson()) {
            return response()->json([
                'message' => 'Aktivitas berhasil disimpan.',
                'data' => $this->activityData($activity->load('category')),
                'redirect_url' => route('activities.index'),
            ], 201);
        }

        return redirect()->route('activities.index')->with('success', 'Aktivitas berhasil disimpan.');
    }

    public function edit(Request $request, DailyActivity $activity): View
    {
        $this->assertOwner($request, $activity);

        return view('activities.form', [
            'activity' => $activity,
            'categories' => Category::query()->orderBy('name')->get(),
        ]);
    }

    public function show(Request $request, DailyActivity $activity): View
    {
        $this->assertOwner($request, $activity);

        return view('activities.show', [
            'activity' => $activity->load('category'),
        ]);
    }

    public function update(Request $request, DailyActivity $activity): RedirectResponse|JsonResponse
    {
        $this->assertOwner($request, $activity);
        $activity->update($this->validated($request));

        if ($request->expectsJson()) {
            return response()->json([
                'message' => 'Aktivitas berhasil diperbarui.',
                'data' => $this->activityData($activity->fresh()->load('category')),
                'redirect_url' => route('activities.index'),
            ]);
        }

        return redirect()->route('activities.index')->with('success', 'Aktivitas berhasil diperbarui.');
    }

    public function destroy(Request $request, DailyActivity $activity): RedirectResponse|JsonResponse
    {
        $this->assertOwner($request, $activity);
        $activity->delete();

        if ($request->expectsJson()) {
            return response()->json(['message' => 'Aktivitas berhasil dihapus.', 'data' => ['id' => $activity->id]]);
        }

        return redirect()->route('activities.index')->with('success', 'Aktivitas berhasil dihapus.');
    }

    private function activityData(DailyActivity $activity): array
    {
        return [
            'id' => $activity->id,
            'title' => $activity->title,
            'description' => $activity->description,
            'activity_date' => $activity->activity_date?->format('Y-m-d'),
            'start_time' => $activity->start_time ? substr($activity->start_time, 0, 5) : null,
            'end_time' => $activity->end_time ? substr($activity->end_time, 0, 5) : null,
            'category' => $activity->category?->name,
            'category_id' => $activity->category_id,
            'duration_minutes' => $activity->durationMinutes(),
        ];
    }

    private function validated(Request $request): array
    {
        $request->merge(['title' => trim((string) $request->input('title'))]);

        $validated = $request->validate([
            'title' => ['required', 'string', 'max:100', new AlphanumericTitle],
            'description' => ['nullable', 'string', 'max:5000'],
            'activity_date' => ['required', 'date'],
            'start_time' => ['nullable', 'date_format:H:i'],
            'end_time' => ['nullable', 'date_format:H:i'],
            'category_id' => ['required', 'integer', Rule::exists('categories', 'id')],
        ]);

        $startTime = $validated['start_time'] ?? null;
        $endTime = $validated['end_time'] ?? null;

        if ($startTime && $endTime) {
            if (substr($endTime, 0, 5) < substr($startTime, 0, 5)) {
                throw ValidationException::withMessages([
                    'end_time' => 'Waktu selesai tidak boleh lebih awal dari waktu mulai.',
                ]);
            }
        }

        return $validated;
    }

    private function assertOwner(Request $request, DailyActivity $activity): void
    {
        abort_unless($activity->user_id === $request->user()->id, 404);
    }
}
