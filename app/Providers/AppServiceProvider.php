<?php

declare(strict_types=1);

namespace App\Providers;

use App\Http\Responses\LoginResponse;
use App\Models\Siswa;
use App\Models\TahunAjaran;
use App\Observers\SiswaObserver;
use App\Observers\TahunAjaranObserver;
use Filament\Auth\Http\Responses\Contracts\LoginResponse as LoginResponseContract;
use Filament\Tables\Table;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;

final class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // Bind custom LoginResponse for role-based redirect after login
        $this->app->singleton(LoginResponseContract::class, LoginResponse::class);
    }

    public function boot(): void
    {
        // Force HTTPS when behind a proxy (ngrok, cloudflare, etc.)
        if ($this->app->environment('local') && request()->header('X-Forwarded-Proto') === 'https') {
            URL::forceScheme('https');
        }

        $this->configureTable();

        // Register model observers
        TahunAjaran::observe(TahunAjaranObserver::class);
        Siswa::observe(SiswaObserver::class);
    }

    private function configureTable(): void
    {
        Table::configureUsing(function (Table $table): void {
            $table->striped()
                ->deferLoading();
        });
    }
}
