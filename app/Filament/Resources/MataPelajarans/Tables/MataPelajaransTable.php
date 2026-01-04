<?php

declare(strict_types=1);

namespace App\Filament\Resources\MataPelajarans\Tables;

use App\Models\Kelas;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Actions\RestoreAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Support\Enums\FontWeight;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Grouping\Group;
use Filament\Tables\Table;

final class MataPelajaransTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('nama_mapel')
                    ->label('Mata Pelajaran')
                    ->searchable()
                    ->sortable()
                    ->weight(FontWeight::Bold)
                    ->description(fn ($record) => $record->kelas
                        ? "Kelas {$record->kelas->tingkat_kelas}{$record->kelas->grup_kelas}"
                        : null),

                TextColumn::make('kelas.tingkat_kelas')
                    ->label('Kelas')
                    ->badge()
                    ->color('success')
                    ->sortable()
                    ->formatStateUsing(fn ($record): string => $record->kelas
                        ? "{$record->kelas->tingkat_kelas}{$record->kelas->grup_kelas}"
                        : '-')
                    ->description(
                        fn ($record) => $record->kelas?->tahunAjaran
                            ? "{$record->kelas->tahunAjaran->nama_tahun} {$record->kelas->tahunAjaran->semester}"
                            : null
                    ),

                TextColumn::make('guru.nama')
                    ->label('Guru Pengampu')
                    ->searchable(['users.name', 'users.email'])
                    ->sortable()
                    ->description(fn ($record) => $record->guru?->email),

                TextColumn::make('created_at')
                    ->label('Dibuat')
                    ->dateTime('d M Y')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('updated_at')
                    ->label('Diubah')
                    ->dateTime('d M Y')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('kelas_id')
                    ->label('Filter Kelas')
                    ->options(fn () => Kelas::with('tahunAjaran')
                        ->get()
                        ->mapWithKeys(fn ($kelas): array => [
                            $kelas->id => "{$kelas->tingkat_kelas}{$kelas->grup_kelas} - {$kelas->tahunAjaran->nama_tahun}",
                        ])
                        ->toArray())
                    ->searchable()
                    ->preload(),

                SelectFilter::make('guru_id')
                    ->label('Filter Guru')
                    ->relationship('guru', 'name', fn ($query) => $query->role('teacher'))
                    ->searchable()
                    ->preload(),

                SelectFilter::make('tahun_ajaran')
                    ->label('Tahun Ajaran')
                    ->options(fn () => \App\Models\TahunAjaran::query()
                        ->get()
                        ->mapWithKeys(fn ($ta): array => [$ta->id => "{$ta->nama_tahun} {$ta->semester}"])
                        ->toArray())
                    ->query(function ($query, array $data) {
                        if ($data['value']) {
                            return $query->whereHas('kelas', function ($q) use ($data): void {
                                $q->where('tahun_ajaran_id', $data['value']);
                            });
                        }

                        return $query;
                    })
                    ->default(fn () => \App\Models\TahunAjaran::where('status', true)->first()?->id),

                TrashedFilter::make(),
            ])
            ->groups([
                Group::make('kelas_id')
                    ->label('Kelas')
                    ->getTitleFromRecordUsing(fn ($record): string => $record->kelas
                        ? "{$record->kelas->tingkat_kelas}{$record->kelas->grup_kelas} - {$record->kelas->tahunAjaran->nama_tahun}"
                        : 'Tanpa Kelas')
                    ->collapsible(),
            ])
            ->defaultGroup('kelas_id')
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
                RestoreAction::make(),
                ForceDeleteAction::make(),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                    ForceDeleteBulkAction::make(),
                    RestoreBulkAction::make(),
                ]),
            ])
            ->defaultSort('kelas_id')
            ->striped()
            ->paginated([10, 25, 50, 100]);
    }
}
