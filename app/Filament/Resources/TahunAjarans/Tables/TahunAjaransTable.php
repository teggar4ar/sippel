<?php

declare(strict_types=1);

namespace App\Filament\Resources\TahunAjarans\Tables;

use App\Models\TahunAjaran;
use Filament\Actions\BulkAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Collection;

final class TahunAjaransTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns(self::buildColumns())
            ->filters(self::buildFilters())
            ->recordActions(self::buildRecordActions())
            ->toolbarActions(self::buildToolbarActions())
            ->defaultSort('nama_tahun', 'desc');
    }

    /**
     * @return array<int, mixed>
     */
    private static function buildColumns(): array
    {
        return [
            TextColumn::make('nama_tahun')
                ->label('Tahun Ajaran')
                ->searchable()
                ->sortable(),

            TextColumn::make('semester')
                ->label('Semester')
                ->badge()
                ->color(fn (string $state): string => match ($state) {
                    'Ganjil' => 'success',
                    'Genap' => 'info',
                    default => 'gray',
                }),

            TextColumn::make('tanggal_mulai')
                ->label('Tanggal Mulai')
                ->date('d/m/Y')
                ->sortable(),

            TextColumn::make('tanggal_selesai')
                ->label('Tanggal Selesai')
                ->date('d/m/Y')
                ->sortable(),

            ToggleColumn::make('status')
                ->label('Status Aktif')
                ->beforeStateUpdated(function ($record, $state) {
                    // If activating this record, check if another is already active
                    if ($state) {
                        $activeExists = TahunAjaran::where('id', '!=', $record->id)
                            ->where('status', true)
                            ->exists();

                        if ($activeExists) {
                            Notification::make()
                                ->danger()
                                ->title('Gagal Mengaktifkan')
                                ->body('Tidak dapat mengaktifkan tahun ajaran ini karena ada tahun ajaran lain yang masih aktif. Harap nonaktifkan tahun ajaran yang aktif terlebih dahulu.')
                                ->send();

                            // Prevent the state change
                            return false;
                        }

                        // Deactivate all others
                        TahunAjaran::where('id', '!=', $record->id)
                            ->update(['status' => false]);
                    }
                })
                ->afterStateUpdated(function ($state): void {
                    if ($state) {
                        Notification::make()
                            ->success()
                            ->title('Berhasil Diaktifkan')
                            ->body('Tahun ajaran telah diaktifkan.')
                            ->send();
                    }
                }),

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
        ];
    }

    /**
     * @return array<int, mixed>
     */
    private static function buildFilters(): array
    {
        return [
            SelectFilter::make('status')
                ->label('Status')
                ->options([
                    true => 'Aktif',
                    false => 'Tidak Aktif',
                ])
                ->native(false),

            SelectFilter::make('semester')
                ->label('Semester')
                ->options([
                    'Ganjil' => 'Ganjil',
                    'Genap' => 'Genap',
                ])
                ->native(false),

            TrashedFilter::make(),
        ];
    }

    /**
     * @return array<int, mixed>
     */
    private static function buildRecordActions(): array
    {
        return [
            EditAction::make(),
            DeleteAction::make()
                ->before(function (DeleteAction $action, TahunAjaran $record): void {
                    if ($record->status) {
                        Notification::make()
                            ->danger()
                            ->title('Gagal Menghapus')
                            ->body('Tidak dapat menghapus tahun ajaran yang masih aktif. Harap nonaktifkan tahun ajaran terlebih dahulu.')
                            ->send();

                        $action->cancel();
                    }
                }),
        ];
    }

    /**
     * @return array<int, mixed>
     */
    private static function buildToolbarActions(): array
    {
        return [
            BulkActionGroup::make([
                BulkAction::make('activate')
                    ->label('Aktifkan')
                    ->icon('heroicon-o-check-circle')
                    ->requiresConfirmation()
                    ->modalHeading('Aktifkan Tahun Ajaran')
                    ->modalDescription('Tindakan ini akan menonaktifkan tahun ajaran lain yang sedang aktif dan mengaktifkan tahun ajaran yang dipilih.')
                    ->modalSubmitActionLabel('Ya, Aktifkan')
                    ->action(function (Collection $records): void {
                        // Check if trying to activate more than one
                        if ($records->count() > 1) {
                            Notification::make()
                                ->danger()
                                ->title('Gagal')
                                ->body('Hanya dapat mengaktifkan satu tahun ajaran pada satu waktu.')
                                ->send();

                            return;
                        }

                        // Deactivate all first
                        TahunAjaran::query()->update(['status' => false]);

                        // Activate selected
                        $records->first()->update(['status' => true]);

                        Notification::make()
                            ->success()
                            ->title('Berhasil')
                            ->body('Tahun ajaran telah diaktifkan.')
                            ->send();
                    })
                    ->deselectRecordsAfterCompletion()
                    ->color('success'),

                BulkAction::make('deactivate')
                    ->label('Nonaktifkan')
                    ->icon('heroicon-o-x-circle')
                    ->requiresConfirmation()
                    ->action(fn (Collection $records) => $records->each->update(['status' => false]))
                    ->deselectRecordsAfterCompletion()
                    ->color('danger'),

                DeleteBulkAction::make()
                    ->before(function (DeleteBulkAction $action, Collection $records): void {
                        $activeRecords = $records->filter(fn ($record) => $record->status);

                        if ($activeRecords->isNotEmpty()) {
                            Notification::make()
                                ->danger()
                                ->title('Gagal Menghapus')
                                ->body('Tidak dapat menghapus tahun ajaran yang masih aktif. Harap nonaktifkan tahun ajaran terlebih dahulu.')
                                ->send();

                            $action->cancel();
                        }
                    }),

                ForceDeleteBulkAction::make()
                    ->before(function (ForceDeleteBulkAction $action, Collection $records): void {
                        $activeRecords = $records->filter(fn ($record) => $record->status);

                        if ($activeRecords->isNotEmpty()) {
                            Notification::make()
                                ->danger()
                                ->title('Gagal Menghapus Permanen')
                                ->body('Tidak dapat menghapus permanen tahun ajaran yang masih aktif. Harap nonaktifkan tahun ajaran terlebih dahulu.')
                                ->send();

                            $action->cancel();
                        }
                    }),

                RestoreBulkAction::make(),
            ]),
        ];
    }
}
