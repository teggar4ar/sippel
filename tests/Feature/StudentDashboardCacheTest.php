<?php

declare(strict_types=1);

use App\Services\StudentDashboardCacheService;

it('invalidates every cached student dashboard data set for affected students', function () {
    $service = app(StudentDashboardCacheService::class);
    $tahunAjaranId = 7;

    $firstVersion = $service->version(10, $tahunAjaranId);
    $secondVersion = $service->version(11, $tahunAjaranId);

    $service->invalidateMany([10, 11, 11], $tahunAjaranId);

    expect($service->version(10, $tahunAjaranId))->not->toBe($firstVersion)
        ->and($service->version(11, $tahunAjaranId))->not->toBe($secondVersion);
});

it('keeps cache generations for unaffected students and school years', function () {
    $service = app(StudentDashboardCacheService::class);

    $otherStudentVersion = $service->version(99, 7);
    $otherYearVersion = $service->version(10, 8);

    $service->invalidateMany([10], 7);

    expect($service->version(99, 7))->toBe($otherStudentVersion)
        ->and($service->version(10, 8))->toBe($otherYearVersion);
});
