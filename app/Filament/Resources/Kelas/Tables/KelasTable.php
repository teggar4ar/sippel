<?php

declare(strict_types=1);

namespace App\Filament\Resources\Kelas\Tables;

use App\Models\TahunAjaran;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;

final class KelasTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('nama_lengkap')
                    ->label('Kelas')
                    ->searchable(['tingkat_kelas', 'grup_kelas'])
                    ->sortable()
                    ->badge()
                    ->color('primary'),

                TextColumn::make('tingkat_kelas')
                    ->label('Tingkat')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('grup_kelas')
                    ->label('Grup')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('waliKelas.name')
                    ->label('Wali Kelas')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('tahunAjaran.nama_tahun')
                    ->label('Tahun Ajaran')
                    ->searchable()
                    ->sortable()
                    ->toggleable(),

                TextColumn::make('tahunAjaran.semester')
                    ->label('Semester')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'Ganjil' => 'success',
                        'Genap' => 'info',
                        default => 'gray',
                    })
                    ->toggleable(),

                TextColumn::make('siswa_count')
                    ->label('Jumlah Siswa')
                    ->counts('siswa')
                    ->badge()
                    ->color('warning')
                    ->toggleable(),

                TextColumn::make('created_at')
                    ->label('Dibuat Pada')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('updated_at')
                    ->label('Diperbarui Pada')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('tingkat_kelas')
                    ->label('Tingkat Kelas')
                    ->options([
                        7 => 'Kelas 7',
                        8 => 'Kelas 8',
                        9 => 'Kelas 9',
                    ])
                    ->native(false),

                SelectFilter::make('tahun_ajaran_id')
                    ->label('Tahun Ajaran')
                    ->relationship('tahunAjaran', 'nama_tahun')
                    ->searchable()
                    ->preload()
                    ->native(false)
                    ->getOptionLabelFromRecordUsing(fn (TahunAjaran $record): string => "{$record->nama_tahun} - {$record->semester}")
                    ->default(fn () => TahunAjaran::where('status', true)->first()?->id),

                TrashedFilter::make(),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                    ForceDeleteBulkAction::make(),
                    RestoreBulkAction::make(),
                ]),
            ])
            ->defaultSort('tingkat_kelas', 'asc')
            ->groups([
                'tingkat_kelas',
                'tahunAjaran.nama_tahun',
            ]);
    }
}
