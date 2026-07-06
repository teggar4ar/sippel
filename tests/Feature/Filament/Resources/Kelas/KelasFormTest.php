<?php

declare(strict_types=1);

use App\Filament\Resources\Kelas\Schemas\KelasForm;
use App\Models\Kelas;
use App\Models\TahunAjaran;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

function invokeTakenGroups(int $tingkat, int $tahunAjaranId, ?int $excludeId = null): Collection
{
    $method = new ReflectionMethod(KelasForm::class, 'getTakenGroups');

    return $method->invoke(null, $tingkat, $tahunAjaranId, $excludeId);
}

it('queries taken class groups only once for the same form context', function () {
    $teacher = User::factory()->create();
    $tahunAjaran = TahunAjaran::factory()->create();

    foreach (['A', 'B'] as $group) {
        Kelas::factory()->create([
            'tingkat_kelas' => 7,
            'grup_kelas' => $group,
            'tahun_ajaran_id' => $tahunAjaran->id,
            'wali_kelas_id' => $teacher->id,
        ]);
    }

    Cache::store('array')->flush();
    DB::flushQueryLog();
    DB::enableQueryLog();

    expect(invokeTakenGroups(7, $tahunAjaran->id)->all())->toBe(['A', 'B'])
        ->and(invokeTakenGroups(7, $tahunAjaran->id)->all())->toBe(['A', 'B'])
        ->and(invokeTakenGroups(7, $tahunAjaran->id)->all())->toBe(['A', 'B']);

    $kelasQueries = collect(DB::getQueryLog())
        ->pluck('query')
        ->map(fn (string $query): string => str_replace(['`', '"'], '', mb_strtolower($query)))
        ->filter(fn (string $query): bool => str_contains($query, 'from kelas'));

    expect($kelasQueries)->toHaveCount(1);
});

it('excludes the current class group when editing', function () {
    $teacher = User::factory()->create();
    $tahunAjaran = TahunAjaran::factory()->create();
    $currentKelas = Kelas::factory()->create([
        'tingkat_kelas' => 8,
        'grup_kelas' => 'A',
        'tahun_ajaran_id' => $tahunAjaran->id,
        'wali_kelas_id' => $teacher->id,
    ]);
    Kelas::factory()->create([
        'tingkat_kelas' => 8,
        'grup_kelas' => 'B',
        'tahun_ajaran_id' => $tahunAjaran->id,
        'wali_kelas_id' => $teacher->id,
    ]);

    Cache::store('array')->flush();

    expect(invokeTakenGroups(8, $tahunAjaran->id, $currentKelas->id)->all())->toBe(['B']);
});
