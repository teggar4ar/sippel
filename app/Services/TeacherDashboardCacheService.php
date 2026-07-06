<?php

declare(strict_types=1);

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

final class TeacherDashboardCacheService
{
    public function chartVersion(int $teacherId, int $tahunAjaranId): string
    {
        return Cache::rememberForever(
            $this->chartVersionKey($teacherId, $tahunAjaranId),
            fn (): string => (string) Str::uuid()
        );
    }

    public function invalidate(int $teacherId, int $tahunAjaranId): void
    {
        Cache::forget('teacher_dashboard_stats_v2_'.$teacherId.'_'.$tahunAjaranId);
        Cache::forever(
            $this->chartVersionKey($teacherId, $tahunAjaranId),
            (string) Str::uuid()
        );
    }

    private function chartVersionKey(int $teacherId, int $tahunAjaranId): string
    {
        return 'teacher_dashboard_chart_version_'.$teacherId.'_'.$tahunAjaranId;
    }
}
