<?php

declare(strict_types=1);

namespace App\Filament\Resources\Kelas\Tables;

use App\Models\TahunAjaran;
use App\Services\QrAttendanceService;
use Filament\Actions\Action;
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
use Illuminate\Support\Facades\Response;

final class KelasTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('nama_lengkap')
                    ->label('Kelas')
                    ->searchable(['tingkat_kelas', 'grup_kelas'])
                    ->sortable(query: fn ($query, $direction) => $query
                        ->orderBy('tingkat_kelas', $direction)
                        ->orderBy('grup_kelas', $direction))
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

                Action::make('cetakQrKelas')
                    ->label('Cetak QR Kelas')
                    ->icon('heroicon-o-printer')
                    ->color('success')
                    ->action(function (\App\Models\Kelas $record) {
                        $service = app(QrAttendanceService::class);
                        $pdfContent = $service->generateClassQrPdf($record);

                        // Sanitize filename - remove slashes and backslashes
                        $tahunAjaran = $record->tahunAjaran?->nama_tahun ?? 'Unknown';
                        $tahunAjaran = str_replace(['/', '\\'], '-', $tahunAjaran);
                        $filename = "QR-Kelas-{$record->tingkat_kelas}{$record->grup_kelas}-{$tahunAjaran}.pdf";

                        return Response::streamDownload(
                            function () use ($pdfContent): void {
                                echo $pdfContent;
                            },
                            $filename,
                            ['Content-Type' => 'application/pdf']
                        );
                    })
                    ->requiresConfirmation()
                    ->modalHeading('Cetak QR Code Kelas')
                    ->modalDescription(fn ($record): string => "Generate dan download kartu QR untuk seluruh siswa di kelas {$record->nama_lengkap}. Total siswa: {$record->siswa_count}.")
                    ->modalSubmitActionLabel('Download PDF')
                    ->successNotificationTitle('PDF QR Code berhasil di-generate!'),

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
