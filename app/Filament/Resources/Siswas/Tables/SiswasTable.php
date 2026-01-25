<?php

declare(strict_types=1);

namespace App\Filament\Resources\Siswas\Tables;

use App\Models\Kelas;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Support\Enums\FontWeight;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;

final class SiswasTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('nis')
                    ->label('NIS')
                    ->searchable()
                    ->sortable()
                    ->weight(FontWeight::Bold)
                    ->copyable()
                    ->copyMessage('NIS disalin!')
                    ->copyMessageDuration(1500),

                TextColumn::make('user.nama')
                    ->label('Nama Lengkap')
                    ->searchable(['users.name'])
                    ->sortable()
                    ->description(fn ($record) => $record->user->email),

                TextColumn::make('user.jenis_kelamin')
                    ->label('Jenis Kelamin')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'L' => 'info',
                        'P' => 'warning',
                    })
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'L' => 'Laki-laki',
                        'P' => 'Perempuan',
                    }),

                TextColumn::make('kelas.nama_lengkap')
                    ->label('Kelas')
                    ->sortable(query: function ($query, $direction) {
                        return $query
                            ->leftJoin('kelas', 'siswa.kelas_id', '=', 'kelas.id')
                            ->orderBy('kelas.tingkat_kelas', $direction)
                            ->orderBy('kelas.grup_kelas', $direction);
                    })
                    ->badge()
                    ->color('success')
                    ->description(
                        fn ($record) => $record->kelas?->tahunAjaran ?
                            "{$record->kelas->tahunAjaran->nama_tahun} {$record->kelas->tahunAjaran->semester}" :
                            null
                    ),

                TextColumn::make('user.email')
                    ->label('Email')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->copyable(),

                TextColumn::make('created_at')
                    ->label('Tanggal Daftar')
                    ->dateTime('d M Y')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('kelas_id')
                    ->label('Filter Kelas')
                    ->relationship('kelas', 'id')
                    ->getOptionLabelFromRecordUsing(
                        fn (Kelas $record): string => "{$record->tingkat_kelas}{$record->grup_kelas} - {$record->tahunAjaran?->nama_tahun}"
                    )
                    ->searchable()
                    ->preload(),

                SelectFilter::make('jenis_kelamin')
                    ->label('Jenis Kelamin')
                    ->options([
                        'L' => 'Laki-laki',
                        'P' => 'Perempuan',
                    ])
                    ->query(function ($query, array $data) {
                        if (isset($data['value'])) {
                            return $query->whereHas('user', function ($q) use ($data): void {
                                $q->where('jenis_kelamin', $data['value']);
                            });
                        }

                        return $query;
                    }),

                TrashedFilter::make(),
            ])
            ->recordActions([
                EditAction::make(),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                    ForceDeleteBulkAction::make(),
                    RestoreBulkAction::make(),

                    // Bulk action to assign students to a class
                    Action::make('assignToClass')
                        ->label('Pindahkan ke Kelas')
                        ->icon('heroicon-o-academic-cap')
                        ->accessSelectedRecords()
                        ->form([
                            \Filament\Forms\Components\Select::make('kelas_id')
                                ->label('Kelas Tujuan')
                                ->options(
                                    Kelas::with('tahunAjaran')
                                        ->get()
                                        ->mapWithKeys(fn ($kelas): array => [
                                            $kelas->id => "{$kelas->tingkat_kelas}{$kelas->grup_kelas} - {$kelas->tahunAjaran?->nama_tahun} {$kelas->tahunAjaran?->semester}",
                                        ])
                                )
                                ->required()
                                ->searchable(),
                        ])
                        ->action(function (array $data, $records): void {
                            $records->each(function ($record) use ($data): void {
                                $record->update(['kelas_id' => $data['kelas_id']]);
                            });
                        })
                        ->deselectRecordsAfterCompletion()
                        ->requiresConfirmation()
                        ->modalHeading('Pindahkan Siswa ke Kelas Lain')
                        ->modalDescription('Anda akan memindahkan siswa yang dipilih ke kelas yang ditentukan.')
                        ->modalSubmitActionLabel('Pindahkan')
                        ->successNotificationTitle('Siswa berhasil dipindahkan!'),
                ]),
            ])
            ->defaultSort('nis')
            ->striped()
            ->paginated([10, 25, 50, 100]);
    }
}
