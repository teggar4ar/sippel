<?php

declare(strict_types=1);

namespace App\Filament\Resources\Kelas\Schemas;

use App\Models\Kelas;
use App\Models\TahunAjaran;
use Closure;
use Filament\Forms\Components\Select;
use Filament\Schemas\Schema;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;

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
            ? self::getTakenGroups((int) $tingkat, (int) $tahunAjaranId, $record?->id)
            : collect();

        return collect(range('A', 'Z'))
            ->mapWithKeys(function (string $letter) use ($taken): array {
                $label = $taken->contains($letter)
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

        $taken = self::getTakenGroups((int) $tingkat, (int) $tahunAjaranId, $record?->id)
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
            $exists = self::getTakenGroups(
                (int) $get('tingkat_kelas'),
                (int) $get('tahun_ajaran_id'),
                $record?->id,
            )->contains(mb_strtoupper((string) $value));

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
        if ($tingkat === 0 || ($tingkat === '' || $tingkat === '0') || $tingkat === null || ($tahunAjaranId === 0 || ($tahunAjaranId === '' || $tahunAjaranId === '0') || $tahunAjaranId === null)) {
            return null;
        }

        $taken = self::getTakenGroups((int) $tingkat, (int) $tahunAjaranId, $ignoreId);

        foreach (range('A', 'Z') as $letter) {
            if (! $taken->contains($letter)) {
                return $letter;
            }
        }

        return null;
    }

    /**
     * @return Collection<int, uppercase-string>
     */
    private static function getTakenGroups(int $tingkat, int $tahunAjaranId, ?int $excludeId): Collection
    {
        $cacheKey = "kelas_taken_groups_{$tingkat}_{$tahunAjaranId}_".($excludeId ?? 'none');

        return Cache::store('array')->rememberForever(
            $cacheKey,
            fn (): Collection => Kelas::where('tingkat_kelas', $tingkat)
                ->where('tahun_ajaran_id', $tahunAjaranId)
                ->when($excludeId, fn ($query) => $query->where('id', '!=', $excludeId))
                ->pluck('grup_kelas')
                ->map(fn (mixed $group): string => mb_strtoupper((string) $group))
                ->values(),
        );
    }
}
