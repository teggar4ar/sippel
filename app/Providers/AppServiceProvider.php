<?php

declare(strict_types=1);

namespace App\Providers;

use App\Http\Responses\LoginResponse;
use App\Models\DetailAktivitas;
use App\Models\Kelas;
use App\Models\Siswa;
use App\Models\TahunAjaran;
use App\Models\User;
use App\Observers\DetailAktivitasObserver;
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
        if ($this->app->environment('production') && config('app.force_https')) {
            URL::forceScheme('https');
            $this->app['request']->server->set('HTTPS', 'on');
        }

        $this->configureTable();

        // Register model observers
        TahunAjaran::observe(TahunAjaranObserver::class);
        Siswa::observe(SiswaObserver::class);
        DetailAktivitas::observe(DetailAktivitasObserver::class);

        // Define Gate for class report export
        Gate::define('export-class-report', function (User $user, Kelas $kelas): bool {
            // Admin and operators can export any class
            if ($user->hasRole('admin') || $user->hasRole('operator')) {
                return true;
            }

            // Teachers can only export classes where they are the homeroom teacher
            // Note: wali_kelas_id references users.id directly (User model represents teachers)
            if ($user->hasRole('teacher')) {
                return $kelas->wali_kelas_id === $user->id;
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
