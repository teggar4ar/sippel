<?php

declare(strict_types=1);

namespace App\Filament\Resources\MataPelajarans\Schemas;

use App\Models\Kelas;
use App\Models\MataPelajaran;
use App\Models\User;
use Closure;
use Filament\Forms\Components\Checkbox;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
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

                            if ($record instanceof MataPelajaran) {
                                $query->where('id', '!=', $record->id);
                            }

                            if ($query->exists()) {
                                $fail('Mata pelajaran ini sudah ada untuk kelas yang dipilih.');
                            }
                        },
                    ])
                    ->columnSpan(2),

                // Virtual field: drives kelas_id filtering, not saved to DB
                Select::make('tingkat_kelas')
                    ->label('Tingkat Kelas')
                    ->options([
                        '7' => 'Kelas 7',
                        '8' => 'Kelas 8',
                        '9' => 'Kelas 9',
                    ])
                    ->native(false)
                    ->live()
                    ->dehydrated(false)
                    ->placeholder('Filter berdasarkan tingkat (opsional)')
                    ->afterStateUpdated(function (callable $set): void {
                        $set('kelas_id', null);
                        $set('apply_to_other_groups', false);
                        $set('other_groups', []);
                    })
                    ->columnSpan(1),

                Select::make('kelas_id')
                    ->label('Kelas')
                    ->options(function (Get $get): array {
                        $tingkat = $get('tingkat_kelas');

                        $query = Kelas::with('tahunAjaran')
                            ->whereHas('tahunAjaran', fn ($q) => $q->where('status', true));

                        if ($tingkat) {
                            $query->where('tingkat_kelas', $tingkat);
                        }

                        return $query
                            ->orderBy('tingkat_kelas')
                            ->orderBy('grup_kelas')
                            ->get()
                            ->mapWithKeys(fn ($kelas): array => [
                                $kelas->id => "{$kelas->tingkat_kelas}{$kelas->grup_kelas} - {$kelas->tahunAjaran->nama_tahun} {$kelas->tahunAjaran->semester}",
                            ])
                            ->toArray();
                    })
                    ->required()
                    ->native(false)
                    ->searchable()
                    ->preload()
                    ->live()
                    ->placeholder('Pilih kelas')
                    ->helperText('Menampilkan kelas dari tahun ajaran aktif')
                    ->afterStateUpdated(function (callable $set): void {
                        $set('apply_to_other_groups', false);
                        $set('other_groups', []);
                    })
                    ->columnSpan(1),

                Select::make('guru_id')
                    ->label('Guru Pengampu')
                    ->relationship(
                        name: 'guru',
                        titleAttribute: 'name',
                        modifyQueryUsing: fn ($query) => $query->role('teacher')
                    )
                    ->getOptionLabelFromRecordUsing(fn ($record): string => $record->name.' ('.$record->email.')')
                    ->searchable(['name', 'email'])
                    ->preload()
                    ->required()
                    ->native(false)
                    ->live()
                    ->placeholder('Pilih guru pengampu')
                    ->helperText('Pilih guru yang akan mengajar mata pelajaran ini')
                    ->columnSpan(2),

                // ── Apply-to-other-groups section (create-only) ────────────────────
                Section::make('Terapkan ke Grup Kelas Lain')
                    ->description('Buat mata pelajaran yang sama untuk grup kelas lain di tingkat yang sama sekaligus. Setiap grup dapat memiliki guru yang berbeda.')
                    ->hiddenOn('edit')
                    ->visible(fn (Get $get): bool => (bool) $get('kelas_id'))
                    ->schema([
                        Toggle::make('apply_to_other_groups')
                            ->label('Terapkan ke grup kelas lain di tingkat yang sama')
                            ->helperText(function (Get $get): string {
                                $kelasId = $get('kelas_id');
                                if (! $kelasId) {
                                    return 'Pilih kelas terlebih dahulu.';
                                }

                                $kelas = Kelas::find((int) $kelasId);
                                if (! $kelas) {
                                    return '';
                                }

                                $count = Kelas::where('tahun_ajaran_id', $kelas->tahun_ajaran_id)
                                    ->where('tingkat_kelas', $kelas->tingkat_kelas)
                                    ->where('id', '!=', $kelas->id)
                                    ->count();

                                return $count > 0
                                    ? "Ada {$count} grup lain di kelas {$kelas->tingkat_kelas}. Aktifkan untuk mengatur guru per grup."
                                    : "Tidak ada grup lain di kelas {$kelas->tingkat_kelas} pada tahun ajaran ini.";
                            })
                            ->live()
                            ->afterStateUpdated(function (bool $state, Get $get, callable $set): void {
                                if (! $state) {
                                    $set('other_groups', []);

                                    return;
                                }

                                $kelasId = $get('kelas_id');
                                $guruId = $get('guru_id');

                                if (! $kelasId) {
                                    $set('apply_to_other_groups', false);

                                    return;
                                }

                                $kelas = Kelas::find((int) $kelasId);
                                if (! $kelas) {
                                    $set('apply_to_other_groups', false);

                                    return;
                                }

                                $others = Kelas::where('tahun_ajaran_id', $kelas->tahun_ajaran_id)
                                    ->where('tingkat_kelas', $kelas->tingkat_kelas)
                                    ->where('id', '!=', $kelas->id)
                                    ->orderBy('grup_kelas')
                                    ->get();

                                if ($others->isEmpty()) {
                                    $set('apply_to_other_groups', false);

                                    return;
                                }

                                $items = [];
                                foreach ($others as $other) {
                                    $items[] = [
                                        'kelas_id' => (string) $other->id,
                                        'kelas_label' => $other->tingkat_kelas.$other->grup_kelas,
                                        'guru_id' => $guruId ? (string) $guruId : null,
                                        'enabled' => true,
                                    ];
                                }

                                $set('other_groups', $items);
                            })
                            ->columnSpanFull(),

                        Repeater::make('other_groups')
                            ->label('')
                            ->schema([
                                Grid::make(3)
                                    ->schema([
                                        Checkbox::make('enabled')
                                            ->label(fn (Get $get): string => 'Kelas '.($get('kelas_label') ?? ''))
                                            ->default(true)
                                            ->live()
                                            ->columnSpan(1),

                                        Select::make('guru_id')
                                            ->label('Guru Pengampu')
                                            ->options(fn (): array => User::role('teacher')
                                                ->orderBy('name')
                                                ->pluck('name', 'id')
                                                ->toArray())
                                            ->searchable()
                                            ->native(false)
                                            ->required()
                                            ->disabled(fn (Get $get): bool => ! (bool) $get('enabled'))
                                            ->dehydratedWhenHidden()
                                            ->columnSpan(2),
                                    ]),

                                Hidden::make('kelas_id'),
                                Hidden::make('kelas_label'),
                            ])
                            ->defaultItems(0)
                            ->addable(false)
                            ->deletable(false)
                            ->reorderable(false)
                            ->visible(fn (Get $get): bool => (bool) $get('apply_to_other_groups'))
                            ->columnSpanFull(),
                    ])
                    ->columnSpanFull(),
            ])
            ->statePath('data');
    }
}
