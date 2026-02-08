<?php

declare(strict_types=1);

namespace App\Providers;

use App\Http\Responses\LoginResponse;
use App\Models\Kelas;
use App\Models\Siswa;
use App\Models\TahunAjaran;
use App\Models\User;
use App\Observers\SiswaObserver;
use App\Observers\TahunAjaranObserver;
use Filament\Auth\Http\Responses\Contracts\LoginResponse as LoginResponseContract;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Gate;
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
        // Force HTTPS in production (Cloud Run, etc.)
        if ($this->app->environment('production')) {
            if (config('app.force_https')) {
                URL::forceScheme('https');
                $this->app['request']->server->set('HTTPS', 'on');
            } else {
                // Explicitly force HTTP when not requiring HTTPS
                URL::forceScheme('http');
            }
        }

        $this->configureTable();

        // Register model observers
        TahunAjaran::observe(TahunAjaranObserver::class);
        Siswa::observe(SiswaObserver::class);

        // Define Gate for class report export
        Gate::define('export-class-report', function (User $user, Kelas $kelas): bool {
            // Admin and operators can export any class
            if ($user->hasRole('admin') || $user->hasRole('operator')) {
                return true;
            }

            // Teachers can only export classes where they are the homeroom teacher
            if ($user->hasRole('teacher')) {
                $guru = $user->guru;

                return $guru && $kelas->wali_kelas_id === $guru->id;
            }

            return false;
        });
    }

    private function configureTable(): void
    {
        Table::configureUsing(function (Table $table): void {
            $table->striped()
                ->deferLoading();
        });
    }
}
