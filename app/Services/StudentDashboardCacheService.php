<?php

declare(strict_types=1);

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

final class StudentDashboardCacheService
{
    public function version(int $siswaId, int $tahunAjaranId): string
    {
        return Cache::rememberForever(
            $this->versionKey($siswaId, $tahunAjaranId),
            fn (): string => (string) Str::uuid()
        );
    }

    /**
     * @param  array<int|string>  $siswaIds
     */
    public function invalidateMany(array $siswaIds, int $tahunAjaranId): void
    {
        foreach (array_unique(array_map('intval', $siswaIds)) as $siswaId) {
            Cache::forever(
                $this->versionKey($siswaId, $tahunAjaranId),
                (string) Str::uuid()
            );
        }
    }

    private function versionKey(int $siswaId, int $tahunAjaranId): string
    {
        return 'student_dashboard_version_'.$siswaId.'_'.$tahunAjaranId;
    }
}
