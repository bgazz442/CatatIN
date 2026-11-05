<?php

namespace App\Services;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use LogicException;

class ActivityDurationCalculator
{
    public function totalMinutes(Builder $activities): int
    {
        $expression = match (DB::connection()->getDriverName()) {
            'mysql', 'mariadb' => 'TIME_TO_SEC(TIMEDIFF(end_time, start_time)) / 60',
            'sqlite' => '((CAST(substr(end_time, 1, 2) AS INTEGER) * 60 + CAST(substr(end_time, 4, 2) AS INTEGER)) - (CAST(substr(start_time, 1, 2) AS INTEGER) * 60 + CAST(substr(start_time, 4, 2) AS INTEGER)))',
            'pgsql' => 'EXTRACT(EPOCH FROM (end_time - start_time)) / 60',
            default => throw new LogicException('Activity duration aggregation is unsupported for this database driver.'),
        };

        $total = (clone $activities)
            ->whereNotNull('start_time')
            ->whereNotNull('end_time')
            ->selectRaw('COALESCE(SUM('.$expression.'), 0) as total_minutes')
            ->first()?->total_minutes;

        return (int) round((float) ($total ?? 0));
    }
}
