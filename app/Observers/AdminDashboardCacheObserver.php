<?php

declare(strict_types=1);

namespace App\Observers;

use App\Services\AdminDashboardCacheService;

final class AdminDashboardCacheObserver
{
    public function saved(): void
    {
        $this->invalidate();
    }

    public function deleted(): void
    {
        $this->invalidate();
    }

    public function restored(): void
    {
        $this->invalidate();
    }

    private function invalidate(): void
    {
        app(AdminDashboardCacheService::class)->invalidate();
    }
}
