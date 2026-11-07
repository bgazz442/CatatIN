<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Catatan;
use App\Models\Goal;
use App\Models\Task;
use App\Models\User;
use Illuminate\View\View;

class AdminDashboardController extends Controller
{
    public function __invoke(): View
    {
        $now = now();
        $registrationMonths = collect(range(5, 0))->map(function (int $monthsAgo) use ($now): array {
            $month = $now->copy()->startOfMonth()->subMonths($monthsAgo);

            return [
                'label' => $month->translatedFormat('F Y'),
                'count' => User::query()
                    ->whereBetween('created_at', [$month, $month->copy()->endOfMonth()])
                    ->count(),
            ];
        });

        return view('admin.dashboard', [
            'totalUsers' => User::query()->count(),
            'activeUsers' => User::query()->where('is_active', true)->count(),
            'roleCounts' => User::query()
                ->selectRaw('role, COUNT(*) as aggregate')
                ->groupBy('role')
                ->pluck('aggregate', 'role'),
            'registrationMonths' => $registrationMonths,
            'totalTasks' => Task::query()->count(),
            'totalGoals' => Goal::query()->count(),
            'totalNotes' => Catatan::query()->count(),
        ]);
    }
}
