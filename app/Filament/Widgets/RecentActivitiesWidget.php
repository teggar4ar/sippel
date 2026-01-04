<?php

declare(strict_types=1);

namespace App\Filament\Widgets;

use App\Models\AktivitasPembelajaran;
use App\Models\User;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;
use Illuminate\Support\Facades\Auth;

final class RecentActivitiesWidget extends BaseWidget
{
    protected static ?int $sort = 2;

    protected int|string|array $columnSpan = 'full';

    public static function canView(): bool
    {
        /** @var User|null $user */
        $user = Auth::user();

        return $user?->hasRole('admin') ?? false;
    }

    public function getTableHeading(): string
    {
        return 'Aktivitas Pembelajaran Terbaru';
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(
                AktivitasPembelajaran::query()
                    ->with(['guru', 'mataPelajaran', 'kelas.tahunAjaran'])
                    ->latest('tanggal')
                    ->latest('created_at')
                    ->limit(10)
            )
            ->columns([
                TextColumn::make('tanggal')
                    ->label('Tanggal')
                    ->date('d M Y')
                    ->sortable(),

                TextColumn::make('guru.name')
                    ->label('Guru')
                    ->searchable()
                    ->limit(20),

                TextColumn::make('mataPelajaran.nama_mapel')
                    ->label('Mata Pelajaran')
                    ->searchable()
                    ->limit(25),

                TextColumn::make('kelas')
                    ->label('Kelas')
                    ->formatStateUsing(fn ($state, $record): string => $record->kelas
                        ? "{$record->kelas->tingkat_kelas}-{$record->kelas->grup_kelas}"
                        : '-')
                    ->badge()
                    ->color('primary'),

                TextColumn::make('topik')
                    ->label('Topik')
                    ->limit(40)
                    ->tooltip(fn ($state) => $state),
            ])
            ->paginated(false)
            ->defaultSort('tanggal', 'desc');
    }
}
