<?php

namespace App\Services\AI;

use App\Models\Catatan;
use App\Models\Category;
use App\Models\DailyActivity;
use App\Models\Goal;
use App\Models\Task;

class WorkspaceContextRetriever
{
    private const SAMPLE_LIMIT = 5;

    private const CATEGORY_LIMIT = 30;

    public function retrieve(int $userId, string $question): array
    {
        $notesCount = Catatan::query()->where('user_id', $userId)->count();
        $taskCounts = Task::query()
            ->where('user_id', $userId)
            ->selectRaw('status, COUNT(*) as aggregate')
            ->groupBy('status')
            ->pluck('aggregate', 'status');
        $goalsCount = Goal::query()->where('user_id', $userId)->count();
        $goalProgress = Goal::query()
            ->where('user_id', $userId)
            ->selectRaw('COALESCE(SUM(current_value), 0) as current_total, COALESCE(SUM(target_value), 0) as target_total')
            ->first();
        $activitiesCount = DailyActivity::query()->where('user_id', $userId)->count();

        $categoriesTotal = Category::query()->count();
        $categories = Category::query()
            ->withCount([
                'catatan as user_notes_count' => fn ($query) => $query->where('user_id', $userId),
                'tasks as user_tasks_count' => fn ($query) => $query->where('user_id', $userId),
                'dailyActivities as user_activities_count' => fn ($query) => $query->where('user_id', $userId),
            ])
            ->orderByDesc('user_notes_count')
            ->orderBy('name')
            ->limit(self::CATEGORY_LIMIT)
            ->get();

        $taskStatusCounts = [
            'pending' => (int) $taskCounts->get('pending', 0),
            'in_progress' => (int) $taskCounts->get('in_progress', 0),
            'completed' => (int) $taskCounts->get('completed', 0),
        ];
        $goalProgressFacts = [
            'current_total' => $goalProgress->current_total,
            'target_total' => $goalProgress->target_total,
        ];
        $lines = [
            'Ringkasan workspace pribadi (agregat database untuk akun saat ini):',
            '- Catatan: '.$notesCount.'.',
            '- Tugas: '.array_sum($taskCounts->all())
                .' total; status terpisah: pending '.$taskStatusCounts['pending']
                .', sedang dikerjakan '.$taskStatusCounts['in_progress']
                .', tuntas '.$taskStatusCounts['completed'].'.',
            '- Jumlah goal: '.$goalsCount.'; total progres tersimpan '
                .$goalProgressFacts['current_total'].' dari '.$goalProgressFacts['target_total']
                .' (agregat nilai progres, bukan jumlah goal atau persentase).',
            '- Aktivitas tercatat: '.$activitiesCount.'.',
            'Kategori yang tersedia bersifat global/shared, bukan kategori milik satu pengguna. Jumlah kategori: '.$categoriesTotal.'.',
        ];

        if ($categoriesTotal > self::CATEGORY_LIMIT) {
            $lines[] = 'Daftar kategori berikut dibatasi pada '.self::CATEGORY_LIMIT.' kategori dengan jumlah catatan pengguna tertinggi.';
        }

        $lines[] = 'Kategori dan jumlah data pada akun ini (catatan / tugas / aktivitas):';
        foreach ($categories as $category) {
            $lines[] = '- '.$category->name.': '
                .$category->user_notes_count.' catatan / '
                .$category->user_tasks_count.' tugas / '
                .$category->user_activities_count.' aktivitas.';
        }

        $sources = [[
            'type' => 'kategori',
            'title' => 'Kategori CatatIn dan ringkasan workspace',
            'link' => route('categories.index'),
        ]];

        $recentNotes = Catatan::query()
            ->where('user_id', $userId)
            ->with('category')
            ->latest()
            ->limit(self::SAMPLE_LIMIT)
            ->get();
        $lines[] = 'Contoh catatan terbaru (maksimum '.self::SAMPLE_LIMIT.'; bukan daftar lengkap jika jumlah catatan lebih besar):';
        foreach ($recentNotes as $note) {
            $categoryName = $note->category?->name ?? $note->kategori ?? 'tanpa kategori';
            $lines[] = '- '.$note->judul.' | kategori: '.$categoryName.'.';
            $sources[] = [
                'type' => 'catatan',
                'title' => $note->judul,
                'link' => route('catatan.show', $note),
            ];
        }
        if ($recentNotes->isEmpty()) {
            $lines[] = '- Tidak ada catatan.';
        }

        $recentTasks = Task::query()
            ->where('user_id', $userId)
            ->with('category')
            ->latest()
            ->limit(self::SAMPLE_LIMIT)
            ->get();
        $lines[] = 'Contoh tugas terbaru (maksimum '.self::SAMPLE_LIMIT.'; bukan daftar lengkap jika jumlah tugas lebih besar):';
        foreach ($recentTasks as $task) {
            $lines[] = '- '.$task->title.' | status: '.$task->status
                .' | kategori: '.($task->category?->name ?? 'tanpa kategori').'.';
            $sources[] = [
                'type' => 'tugas',
                'title' => 'Tugas: '.$task->title,
                'link' => route('tasks.edit', $task),
            ];
        }
        if ($recentTasks->isEmpty()) {
            $lines[] = '- Tidak ada tugas.';
        }

        $recentGoals = Goal::query()
            ->where('user_id', $userId)
            ->latest()
            ->limit(self::SAMPLE_LIMIT)
            ->get();
        $lines[] = 'Contoh goal terbaru (maksimum '.self::SAMPLE_LIMIT.'; bukan daftar lengkap jika jumlah goal lebih besar):';
        foreach ($recentGoals as $goal) {
            $lines[] = '- '.$goal->name.' | status: '.$goal->status
                .' | progres: '.$goal->current_value.' / '.$goal->target_value.' '.$goal->unit
                .' ('.$goal->percentage().'%).';
            $sources[] = [
                'type' => 'target',
                'title' => 'Target: '.$goal->name,
                'link' => route('goals.show', $goal),
            ];
        }
        if ($recentGoals->isEmpty()) {
            $lines[] = '- Tidak ada goal.';
        }

        $recentActivities = DailyActivity::query()
            ->where('user_id', $userId)
            ->with('category')
            ->orderByDesc('activity_date')
            ->orderByDesc('created_at')
            ->limit(self::SAMPLE_LIMIT)
            ->get();
        $lines[] = 'Contoh aktivitas terbaru (maksimum '.self::SAMPLE_LIMIT.'; bukan daftar lengkap jika jumlah aktivitas lebih besar):';
        foreach ($recentActivities as $activity) {
            $lines[] = '- '.$activity->activity_date->format('Y-m-d').' | '.$activity->title
                .' | kategori: '.($activity->category?->name ?? 'tanpa kategori').'.';
            $sources[] = [
                'type' => 'aktivitas',
                'title' => 'Aktivitas: '.$activity->title.' ('.$activity->activity_date->format('d M Y').')',
                'link' => route('activities.edit', $activity),
            ];
        }
        if ($recentActivities->isEmpty()) {
            $lines[] = '- Tidak ada aktivitas.';
        }

        return [
            'context' => implode("\n", $lines),
            'sources' => $sources,
            'facts' => [
                'notes_count' => $notesCount,
                'tasks_count' => array_sum($taskCounts->all()),
                'task_status_counts' => $taskStatusCounts,
                'goals_count' => $goalsCount,
                'goal_progress' => $goalProgressFacts,
                'activities_count' => $activitiesCount,
                'categories_count' => $categoriesTotal,
                'categories' => $categories->map(fn (Category $category) => [
                    'name' => $category->name,
                    'notes_count' => (int) $category->user_notes_count,
                    'tasks_count' => (int) $category->user_tasks_count,
                    'activities_count' => (int) $category->user_activities_count,
                ])->all(),
                'categories_truncated' => $categoriesTotal > self::CATEGORY_LIMIT,
            ],
        ];
    }
}
