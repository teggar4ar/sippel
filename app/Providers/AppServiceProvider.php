<?php

declare(strict_types=1);

namespace App\Providers;

use App\Models\TahunAjaran;
use App\Observers\TahunAjaranObserver;
use Filament\Tables\Table;
use Illuminate\Support\ServiceProvider;

final class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        $this->configureTable();

        // Register model observers
        TahunAjaran::observe(TahunAjaranObserver::class);
    }

    private function configureTable(): void
    {
        Table::configureUsing(function (Table $table): void {
            $table->striped()
                ->deferLoading();
        });
    }
}
