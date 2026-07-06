<?php

declare(strict_types=1);

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

final class AdminDashboardCacheService
{
    private const string VERSION_KEY = 'admin_dashboard_cache_version';

    public function version(): string
    {
        return Cache::rememberForever(
            self::VERSION_KEY,
            fn (): string => (string) Str::uuid()
        );
    }

    public function invalidate(): void
    {
        Cache::forever(self::VERSION_KEY, (string) Str::uuid());
    }
}
