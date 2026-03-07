<?php

declare(strict_types=1);

namespace App\Filament\Resources\Kelas\Schemas;

use App\Models\Kelas;
use App\Models\TahunAjaran;
use Closure;
use Filament\Forms\Components\Select;
use Filament\Schemas\Schema;

final class KelasForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(2)
            ->components([
                Select::make('tingkat_kelas')
                    ->label('Tingkat Kelas')
                    ->options([
                        7 => '7 (Kelas 7)',
                        8 => '8 (Kelas 8)',
                        9 => '9 (Kelas 9)',
                    ])
                    ->required()
                    ->native(false)
                    ->searchable()
                    ->live()
                    ->afterStateUpdated(function (int|string|null $state, $get, $set, $record): void {
                        // Auto-assign next available group whenever grade changes
                        $next = self::nextAvailableLetter($state, $get('tahun_ajaran_id'), $record?->id);
                        if ($next !== null) {
                            $set('grup_kelas', $next);
                        }
                    })
                    ->columnSpan(1),

                Select::make('grup_kelas')
                    ->label('Grup Kelas')
                    ->options(fn ($get, $record): array => self::grupKelasOptions($get, $record))
                    ->required()
                    ->native(false)
                    ->searchable()
                    ->live()
                    ->helperText(fn ($get, $record): string => self::grupKelasHelperText($get, $record))
                    ->rule(fn ($get, $record): Closure => self::grupKelasValidationRule($get, $record))
                    ->columnSpan(1),

                Select::make('tahun_ajaran_id')
                    ->label('Tahun Ajaran')
                    ->options(fn () => TahunAjaran::query()
                        ->get()
                        ->mapWithKeys(fn ($ta): array => [$ta->id => $ta->nama_tahun.' - '.$ta->semester])
                        ->toArray())
                    ->default(fn () => TahunAjaran::where('status', true)->first()?->id)
                    ->required()
                    ->native(false)
                    ->searchable()
                    ->live()
                    ->afterStateUpdated(function (int|string|null $state, $get, $set, $record): void {
                        // Re-auto-assign group when academic year changes
                        $next = self::nextAvailableLetter($get('tingkat_kelas'), $state, $record?->id);
                        if ($next !== null) {
                            $set('grup_kelas', $next);
                        }
                    })
                    ->helperText('Tahun ajaran yang aktif dipilih secara default')
                    ->columnSpan(2),

                Select::make('wali_kelas_id')
                    ->label('Wali Kelas')
                    ->relationship(
                        name: 'waliKelas',
                        titleAttribute: 'name',
                        modifyQueryUsing: fn ($query) => $query->role('teacher')
                    )
                    ->searchable(['name', 'email'])
                    ->preload()
                    ->required()
                    ->native(false)
                    ->helperText('Pilih guru yang akan menjadi wali kelas')
                    ->columnSpan(2),
            ]);
    }

    /**
     * Build the dropdown options for grup_kelas, marking already-taken letters.
     */
    private static function grupKelasOptions(mixed $get, mixed $record): array
    {
        $tingkat = $get('tingkat_kelas');
        $tahunAjaranId = $get('tahun_ajaran_id');

        $taken = ($tingkat && $tahunAjaranId)
            ? Kelas::where('tingkat_kelas', $tingkat)
                ->where('tahun_ajaran_id', $tahunAjaranId)
                ->when($record?->id, fn ($q) => $q->where('id', '!=', $record->id))
                ->pluck('grup_kelas')
                ->map(fn ($l) => mb_strtoupper((string) $l))
                ->all()
            : [];

        return collect(range('A', 'Z'))
            ->mapWithKeys(function (string $letter) use ($taken): array {
                $label = in_array($letter, $taken, true)
                    ? "{$letter} (sudah ada)"
                    : $letter;

                return [$letter => $label];
            })
            ->toArray();
    }

    /**
     * Build the helper text for grup_kelas describing which letters are already taken.
     */
    private static function grupKelasHelperText(mixed $get, mixed $record): string
    {
        $tingkat = $get('tingkat_kelas');
        $tahunAjaranId = $get('tahun_ajaran_id');

        if (! $tingkat || ! $tahunAjaranId) {
            return 'Pilih tingkat kelas dan tahun ajaran terlebih dahulu.';
        }

        $taken = Kelas::where('tingkat_kelas', $tingkat)
            ->where('tahun_ajaran_id', $tahunAjaranId)
            ->when($record?->id, fn ($q) => $q->where('id', '!=', $record->id))
            ->pluck('grup_kelas')
            ->map(fn ($l) => mb_strtoupper((string) $l))
            ->sort()
            ->values()
            ->all();

        return $taken
            ? 'Grup yang sudah ada: '.implode(', ', $taken).'. Grup baru ditetapkan otomatis.'
            : 'Belum ada grup untuk kelas ini. Grup A ditetapkan otomatis.';
    }

    /**
     * Build the validation rule closure for grup_kelas uniqueness.
     */
    private static function grupKelasValidationRule(mixed $get, mixed $record): Closure
    {
        return function (string $_, mixed $value, Closure $fail) use ($get, $record): void {
            $exists = Kelas::where('tingkat_kelas', $get('tingkat_kelas'))
                ->where('tahun_ajaran_id', $get('tahun_ajaran_id'))
                ->where('grup_kelas', mb_strtoupper((string) $value))
                ->when($record?->id, fn ($q) => $q->where('id', '!=', $record->id))
                ->exists();

            if ($exists) {
                $fail("Kelas {$get('tingkat_kelas')}{$value} sudah ada untuk tahun ajaran ini.");
            }
        };
    }

    /**
     * Return the next available group letter for the given grade + academic year,
     * skipping any letters that are already in use (optionally ignoring $ignoreId
     * so an edit form does not exclude the record being edited).
     */
    private static function nextAvailableLetter(int|string|null $tingkat, int|string|null $tahunAjaranId, ?int $ignoreId = null): ?string
    {
        if (! $tingkat || ! $tahunAjaranId) {
            return null;
        }

        $taken = Kelas::where('tingkat_kelas', $tingkat)
            ->where('tahun_ajaran_id', $tahunAjaranId)
            ->when($ignoreId, fn ($q) => $q->where('id', '!=', $ignoreId))
            ->pluck('grup_kelas')
            ->map(fn ($l) => mb_strtoupper((string) $l))
            ->all();

        foreach (range('A', 'Z') as $letter) {
            if (! in_array($letter, $taken, true)) {
                return $letter;
            }
        }

        return null;
    }
}
