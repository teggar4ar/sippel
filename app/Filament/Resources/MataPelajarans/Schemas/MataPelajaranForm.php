<?php

declare(strict_types=1);

namespace App\Filament\Resources\MataPelajarans\Schemas;

use App\Models\Kelas;
use App\Models\MataPelajaran;
use Closure;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;

final class MataPelajaranForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(2)
            ->components([
                TextInput::make('nama_mapel')
                    ->label('Nama Mata Pelajaran')
                    ->required()
                    ->maxLength(100)
                    ->placeholder('Contoh: Matematika, Bahasa Indonesia')
                    ->helperText('Masukkan nama mata pelajaran')
                    ->rules([
                        fn (Get $get, ?MataPelajaran $record): Closure => function (string $attribute, $value, Closure $fail) use ($get, $record): void {
                            $kelasId = $get('kelas_id');
                            if (! $kelasId) {
                                return;
                            }

                            $query = MataPelajaran::where('nama_mapel', $value)
                                ->where('kelas_id', $kelasId);

                            // Exclude current record when editing
                            if ($record instanceof MataPelajaran) {
                                $query->where('id', '!=', $record->id);
                            }

                            if ($query->exists()) {
                                $fail('Mata pelajaran ini sudah ada untuk kelas yang dipilih.');
                            }
                        },
                    ])
                    ->columnSpan(2),

                Select::make('kelas_id')
                    ->label('Kelas')
                    ->options(fn () => Kelas::with('tahunAjaran')
                        ->whereHas('tahunAjaran', fn ($q) => $q->where('status', true))
                        ->get()
                        ->mapWithKeys(fn ($kelas): array => [
                            $kelas->id => "{$kelas->tingkat_kelas}{$kelas->grup_kelas} - {$kelas->tahunAjaran->nama_tahun} {$kelas->tahunAjaran->semester}",
                        ])
                        ->toArray())
                    ->required()
                    ->native(false)
                    ->searchable()
                    ->preload()
                    ->live()
                    ->placeholder('Pilih kelas')
                    ->helperText('Menampilkan kelas dari tahun ajaran aktif')
                    ->columnSpan(1),

                Select::make('guru_id')
                    ->label('Guru Pengampu')
                    ->relationship(
                        name: 'guru',
                        titleAttribute: 'name',
                        modifyQueryUsing: fn ($query) => $query->role('teacher')
                    )
                    ->getOptionLabelFromRecordUsing(fn ($record): string => $record->nama.' ('.$record->email.')')
                    ->searchable(['name', 'email'])
                    ->preload()
                    ->required()
                    ->native(false)
                    ->placeholder('Pilih guru pengampu')
                    ->helperText('Pilih guru yang akan mengajar mata pelajaran ini')
                    ->columnSpan(1),
            ])
            ->statePath('data');
    }
}
