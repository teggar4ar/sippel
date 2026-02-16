<?php

declare(strict_types=1);

namespace App\Filament\Resources\Siswas\Tables;

use App\Models\Kelas;
use App\Services\QrAttendanceService;
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
use Illuminate\Support\Facades\Response;

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
                    ->description(fn($record) => $record->user->email),

                TextColumn::make('user.jenis_kelamin')
                    ->label('Jenis Kelamin')
                    ->badge()
                    ->color(fn(string $state): string => match ($state) {
                        'L' => 'info',
                        'P' => 'warning',
                    })
                    ->formatStateUsing(fn(string $state): string => match ($state) {
                        'L' => 'Laki-laki',
                        'P' => 'Perempuan',
                    }),

                TextColumn::make('kelas.nama_lengkap')
                    ->label('Kelas')
                    ->sortable(query: fn($query, $direction) => $query
                        ->select('siswa.*')
                        ->leftJoin('kelas', 'siswa.kelas_id', '=', 'kelas.id')
                        ->orderBy('kelas.tingkat_kelas', $direction)
                        ->orderBy('kelas.grup_kelas', $direction))
                    ->badge()
                    ->color('success')
                    ->description(
                        fn($record) => $record->kelas?->tahunAjaran ?
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
                        fn(Kelas $record): string => "{$record->tingkat_kelas}{$record->grup_kelas} - {$record->tahunAjaran?->nama_tahun}"
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

                Action::make('downloadQr')
                    ->label('Download QR')
                    ->icon('heroicon-o-qr-code')
                    ->color('success')
                    ->action(function ($record) {
                        $service = app(QrAttendanceService::class);
                        $qrImage = $service->generateQrImage($record, 400);

                        // Extract base64 data from data URI
                        $base64Data = explode(',', $qrImage)[1];
                        $imageData = base64_decode($base64Data);

                        // Sanitize filename
                        $nama = str_replace(['/', '\\'], '-', $record->user->nama);
                        $filename = "qr-{$record->nis}-{$nama}.png";

                        return Response::streamDownload(
                            function () use ($imageData): void {
                                echo $imageData;
                            },
                            $filename,
                            ['Content-Type' => 'image/png']
                        );
                    })
                    ->requiresConfirmation(false)
                    ->successNotificationTitle('QR Code berhasil diunduh!'),
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
                                        ->mapWithKeys(fn($kelas): array => [
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

                    // Bulk action to regenerate QR codes
                    Action::make('regenerateQr')
                        ->label('Regenerate QR Code')
                        ->icon('heroicon-o-arrow-path')
                        ->color('warning')
                        ->accessSelectedRecords()
                        ->action(function ($records) {
                            $count = 0;

                            foreach ($records as $record) {
                                $record->generateQrSecret();
                                $count++;
                            }

                            return $count;
                        })
                        ->deselectRecordsAfterCompletion()
                        ->requiresConfirmation()
                        ->modalHeading('Regenerate QR Code')
                        ->modalDescription('QR code lama akan tidak valid. Kartu QR baru harus dicetak ulang dan dibagikan ke siswa.')
                        ->modalSubmitActionLabel('Regenerate')
                        ->successNotification(
                            fn($result) => \Filament\Notifications\Notification::make()
                                ->success()
                                ->title('QR Code berhasil di-regenerate!')
                                ->body("{$result} QR code telah di-generate ulang.")
                        ),
                ]),
            ])
            ->defaultSort('nis')
            ->striped()
            ->paginated([10, 25, 50, 100]);
    }
}
