<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Task;
use App\Rules\AlphanumericTitle;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class TaskController extends Controller
{
    public function index(Request $request): View
    {
        $validated = $request->validate([
            'search' => ['nullable', 'string', 'max:150'],
            'status' => ['nullable', Rule::in(Task::STATUSES)],
            'category_id' => ['nullable', 'integer', 'exists:categories,id'],
            'sort' => ['nullable', Rule::in(['deadline', 'created'])],
        ]);
        $tasks = Task::query()
            ->where('user_id', $request->user()->id)
            ->with('category')
            ->when($validated['search'] ?? null, function ($query, string $search) {
                $query->where(function ($matches) use ($search) {
                    $matches->where('title', 'like', '%'.$search.'%')
                        ->orWhere('description', 'like', '%'.$search.'%');
                });
            })
            ->when($validated['status'] ?? null, fn ($query, string $status) => $query->where('status', $status))
            ->when($validated['category_id'] ?? null, fn ($query, int $categoryId) => $query->where('category_id', $categoryId));

        if (($validated['sort'] ?? 'deadline') === 'created') {
            $tasks->latest()->orderByDesc('id');
        } else {
            $tasks->orderByRaw('due_date IS NULL')
                ->orderBy('due_date')
                ->latest()
                ->orderByDesc('id');
        }

        return view('tasks.index', [
            'tasks' => $tasks->paginate(15)->withQueryString(),
            'categories' => Category::query()->orderBy('name')->get(),
            'filters' => $validated,
        ]);
    }

    public function create(): View
    {
        return view('tasks.form', [
            'task' => new Task(['status' => 'pending']),
            'categories' => Category::query()->orderBy('name')->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse|JsonResponse
    {
        $task = Task::create([
            ...$this->validated($request),
            'user_id' => $request->user()->id,
        ]);

        if ($request->expectsJson()) {
            return response()->json([
                'message' => 'Tugas berhasil ditambahkan.',
                'data' => $this->taskData($task->load('category')),
                'redirect_url' => route('tasks.index'),
            ], 201);
        }

        return redirect()->route('tasks.index')->with('success', 'Tugas berhasil ditambahkan.');
    }

    public function edit(Request $request, Task $task): View
    {
        $this->assertOwner($request, $task);

        return view('tasks.form', [
            'task' => $task,
            'categories' => Category::query()->orderBy('name')->get(),
        ]);
    }

    public function update(Request $request, Task $task): RedirectResponse|JsonResponse
    {
        $this->assertOwner($request, $task);
        $task->update($this->validated($request));

        if ($request->expectsJson()) {
            return response()->json([
                'message' => 'Tugas berhasil diperbarui.',
                'data' => $this->taskData($task->fresh()->load('category')),
                'redirect_url' => route('tasks.index'),
            ]);
        }

        return redirect()->route('tasks.index')->with('success', 'Tugas berhasil diperbarui.');
    }

    public function updateStatus(Request $request, Task $task): RedirectResponse|JsonResponse
    {
        $this->assertOwner($request, $task);
        $validated = $request->validate([
            'status' => ['required', Rule::in(Task::STATUSES)],
        ]);
        $task->update($validated);

        if ($request->expectsJson()) {
            return response()->json([
                'message' => 'Status tugas berhasil diperbarui.',
                'data' => $this->taskData($task->fresh()->load('category')),
            ]);
        }

        return redirect()->route('tasks.index')->with('success', 'Status tugas berhasil diperbarui.');
    }

    public function destroy(Request $request, Task $task): RedirectResponse|JsonResponse
    {
        $this->assertOwner($request, $task);
        $task->delete();

        if ($request->expectsJson()) {
            return response()->json(['message' => 'Tugas berhasil dihapus.', 'data' => ['id' => $task->id]]);
        }

        return redirect()->route('tasks.index')->with('success', 'Tugas berhasil dihapus.');
    }

    private function taskData(Task $task): array
    {
        return [
            'id' => $task->id,
            'title' => $task->title,
            'description' => $task->description,
            'status' => $task->status,
            'category' => $task->category?->name,
            'category_id' => $task->category_id,
            'due_date' => $task->due_date?->format('Y-m-d'),
            'overdue' => $task->status !== 'completed' && $task->due_date?->lt(today()) === true,
            'due_soon' => $task->status !== 'completed'
                && $task->due_date?->between(today(), today()->addDays(3)) === true,
        ];
    }

    private function validated(Request $request): array
    {
        $request->merge(['title' => trim((string) $request->input('title'))]);

        return $request->validate([
            'title' => ['required', 'string', 'max:100', new AlphanumericTitle],
            'description' => ['nullable', 'string', 'max:5000'],
            'category_id' => ['nullable', 'integer', 'exists:categories,id'],
            'due_date' => ['nullable', 'date'],
            'status' => ['required', Rule::in(Task::STATUSES)],
        ]);
    }

    private function assertOwner(Request $request, Task $task): void
    {
        abort_unless($task->user_id === $request->user()->id, 404);
    }
}
