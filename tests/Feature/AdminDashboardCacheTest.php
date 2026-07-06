<?php

declare(strict_types=1);

use App\Models\AktivitasPembelajaran;
use App\Services\AdminDashboardCacheService;

it('rotates the shared admin dashboard cache version', function (): void {
    $service = app(AdminDashboardCacheService::class);
    $initialVersion = $service->version();

    $service->invalidate();

    expect($service->version())->not->toBe($initialVersion);
});

it('invalidates the admin dashboard cache when an activity changes', function (): void {
    $service = app(AdminDashboardCacheService::class);
    $initialVersion = $service->version();

    AktivitasPembelajaran::factory()->create();

    expect($service->version())->not->toBe($initialVersion);
});
