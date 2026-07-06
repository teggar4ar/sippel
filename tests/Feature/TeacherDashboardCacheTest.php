<?php

declare(strict_types=1);

use App\Services\TeacherDashboardCacheService;
use Illuminate\Support\Facades\Cache;

it('invalidates teacher dashboard stats and every chart cache generation', function () {
    $teacherId = 42;
    $tahunAjaranId = 7;
    $service = app(TeacherDashboardCacheService::class);

    $initialVersion = $service->chartVersion($teacherId, $tahunAjaranId);
    Cache::put('teacher_dashboard_stats_v2_'.$teacherId.'_'.$tahunAjaranId, ['cached' => true], 300);

    $service->invalidate($teacherId, $tahunAjaranId);

    expect(Cache::has('teacher_dashboard_stats_v2_'.$teacherId.'_'.$tahunAjaranId))->toBeFalse()
        ->and($service->chartVersion($teacherId, $tahunAjaranId))->not->toBe($initialVersion);
});

it('does not invalidate another teacher or school year chart generation', function () {
    $service = app(TeacherDashboardCacheService::class);

    $otherTeacherVersion = $service->chartVersion(99, 7);
    $otherYearVersion = $service->chartVersion(42, 8);

    $service->invalidate(42, 7);

    expect($service->chartVersion(99, 7))->toBe($otherTeacherVersion)
        ->and($service->chartVersion(42, 8))->toBe($otherYearVersion);
});
