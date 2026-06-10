<?php

declare(strict_types=1);

namespace App\Filament\Resources\Users\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Notifications\Notification;
use Filament\Support\Enums\FontWeight;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

final class UsersTable
{
    public const string MSG_CANNOT_DELETE = 'Tidak dapat menghapus user';

    public static function configure(Table $table): Table
    {
        return $table
            ->columns(self::buildColumns())
            ->filters(self::buildFilters())
            ->recordActions(self::buildRecordActions())
            ->toolbarActions(self::buildBulkActions())
            ->defaultSort('created_at', 'desc')
            ->striped()
            ->paginated([10, 25, 50, 100]);
    }

    /** @return array<int, \Filament\Tables\Columns\Column> */
    private static function buildColumns(): array
    {
        return [
            TextColumn::make('name')
                ->label('Nama')
                ->sortable()
                ->searchable()
                ->weight(FontWeight::Bold)
                ->description(fn ($record) => $record->email),

            TextColumn::make('email')
                ->label('Email')
                ->sortable()
                ->searchable()
                ->toggleable(isToggledHiddenByDefault: true)
                ->copyable()
                ->copyMessage('Email disalin!'),

            TextColumn::make('jenis_kelamin')
                ->label('Jenis Kelamin')
                ->badge()
                ->color(fn (string $state): string => match ($state) {
                    'L' => 'info',
                    'P' => 'warning',
                    default => 'gray',
                })
                ->formatStateUsing(fn (string $state): string => match ($state) {
                    'L' => 'Laki-laki',
                    'P' => 'Perempuan',
                    default => $state,
                }),

            TextColumn::make('roles.name')
                ->label('Role')
                ->badge()
                ->color(fn (string $state): string => match ($state) {
                    'admin' => 'success',
                    'teacher' => 'warning',
                    'student' => 'info',
                    default => 'gray',
                })
                ->formatStateUsing(fn (string $state): string => match ($state) {
                    'admin' => 'Admin',
                    'teacher' => 'Guru',
                    'student' => 'Siswa',
                    default => ucfirst($state),
                }),

            TextColumn::make('email_verified_at')
                ->label('Email Terverifikasi')
                ->dateTime('d M Y H:i')
                ->sortable()
                ->toggleable(isToggledHiddenByDefault: true)
                ->placeholder('Belum terverifikasi'),

            TextColumn::make('created_at')
                ->label('Dibuat')
                ->dateTime('d M Y')
                ->sortable()
                ->toggleable(),

            TextColumn::make('updated_at')
                ->label('Diubah')
                ->dateTime('d M Y')
                ->sortable()
                ->toggleable(isToggledHiddenByDefault: true),
        ];
    }

    /** @return array<int, \Filament\Tables\Filters\BaseFilter> */
    private static function buildFilters(): array
    {
        return [
            SelectFilter::make('roles')
                ->label('Role')
                ->relationship('roles', 'name')
                ->multiple()
                ->preload(),

            SelectFilter::make('jenis_kelamin')
                ->label('Jenis Kelamin')
                ->options([
                    'L' => 'Laki-laki',
                    'P' => 'Perempuan',
                ]),
        ];
    }

    /** @return array<int, \Filament\Actions\Action> */
    private static function buildRecordActions(): array
    {
        return [
            EditAction::make(),
            DeleteAction::make()
                ->before(function (DeleteAction $action, $record): void {
                    // Prevent deletion if user has linked siswa record
                    if ($record->siswa()->exists()) {
                        Notification::make()
                            ->danger()
                            ->title(self::MSG_CANNOT_DELETE)
                            ->body('User ini terhubung dengan data siswa. Hapus data siswa terlebih dahulu.')
                            ->persistent()
                            ->send();

                        $action->cancel();
                    }

                    // Prevent deletion if user is a teacher with assigned classes or subjects
                    if ($record->hasRole('teacher')) {
                        if ($record->kelasAsWali()->exists()) {
                            Notification::make()
                                ->danger()
                                ->title(self::MSG_CANNOT_DELETE)
                                ->body('User ini adalah wali kelas. Hapus atau pindahkan kelas terlebih dahulu.')
                                ->persistent()
                                ->send();

                            $action->cancel();
                        }

                        if ($record->mataPelajaranAsGuru()->exists()) {
                            Notification::make()
                                ->danger()
                                ->title(self::MSG_CANNOT_DELETE)
                                ->body('User ini mengajar mata pelajaran. Hapus atau pindahkan mata pelajaran terlebih dahulu.')
                                ->persistent()
                                ->send();

                            $action->cancel();
                        }
                    }
                }),
        ];
    }

    /** @return array<int, BulkActionGroup|\Filament\Actions\BulkAction> */
    private static function buildBulkActions(): array
    {
        return [
            BulkActionGroup::make([
                DeleteBulkAction::make()
                    ->before(function (DeleteBulkAction $action, $records): void {
                        foreach ($records as $record) {
                            // Check each record for linked data
                            if ($record->siswa()->exists()) {
                                Notification::make()
                                    ->danger()
                                    ->title('Tidak dapat menghapus beberapa user')
                                    ->body("User {$record->name} terhubung dengan data siswa.")
                                    ->persistent()
                                    ->send();

                                $action->cancel();

                                return;
                            }

                            if ($record->hasRole('teacher') && ($record->kelasAsWali()->exists() || $record->mataPelajaranAsGuru()->exists())) {
                                Notification::make()
                                    ->danger()
                                    ->title('Tidak dapat menghapus beberapa user')
                                    ->body("User {$record->name} memiliki kelas atau mata pelajaran yang ditugaskan.")
                                    ->persistent()
                                    ->send();
                                $action->cancel();

                                return;
                            }
                        }
                    }),
            ]),
        ];
    }
}
