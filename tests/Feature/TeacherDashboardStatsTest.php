<?php

declare(strict_types=1);

use App\Livewire\Teacher\Dashboard;
use App\Models\Kelas;
use App\Models\MataPelajaran;
use App\Models\TahunAjaran;
use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

use function Pest\Livewire\livewire;

it('loads teacher subjects once when calculating dashboard statistics', function () {
    $teacher = User::factory()->create();
    $teacher->assignRole('teacher');
    $this->actingAs($teacher);

    $selectedYear = TahunAjaran::factory()->active()->create([
        'nama_tahun' => '2025/2026',
        'semester' => 'Genap',
    ]);
    $otherYear = TahunAjaran::factory()->create([
        'nama_tahun' => '2026/2027',
        'semester' => 'Ganjil',
    ]);
    $firstKelas = Kelas::factory()->create([
        'tahun_ajaran_id' => $selectedYear->id,
        'wali_kelas_id' => $teacher->id,
        'grup_kelas' => 'A',
    ]);
    $secondKelas = Kelas::factory()->create([
        'tahun_ajaran_id' => $selectedYear->id,
        'wali_kelas_id' => $teacher->id,
        'grup_kelas' => 'B',
    ]);
    $otherYearKelas = Kelas::factory()->create([
        'tahun_ajaran_id' => $otherYear->id,
        'wali_kelas_id' => $teacher->id,
        'grup_kelas' => 'C',
    ]);

    foreach ([
        ['Matematika', $firstKelas->id],
        ['IPA', $firstKelas->id],
        ['Bahasa Indonesia', $secondKelas->id],
        ['Bahasa Inggris', $otherYearKelas->id],
    ] as [$name, $kelasId]) {
        MataPelajaran::create([
            'nama_mapel' => $name,
            'guru_id' => $teacher->id,
            'kelas_id' => $kelasId,
        ]);
    }

    Cache::forget('teacher_dashboard_stats_v2_'.$teacher->id.'_'.$selectedYear->id);
    DB::flushQueryLog();
    DB::enableQueryLog();

    $stats = (new Dashboard)->dashboardStats();

    expect($stats['kelas_diampu'])->toBe(3);

    $subjectQueries = collect(DB::getQueryLog())
        ->pluck('query')
        ->map(fn (string $query): string => str_replace(['`', '"'], '', mb_strtolower($query)))
        ->filter(fn (string $query): bool => str_contains($query, 'from mata_pelajaran'));

    expect($subjectQueries)->toHaveCount(1);
});

it('reuses the computed subject collection for participation statistics', function () {
    $teacher = User::factory()->create();
    $teacher->assignRole('teacher');
    $this->actingAs($teacher);

    $tahunAjaran = TahunAjaran::factory()->active()->create();
    $kelas = Kelas::factory()->create([
        'tahun_ajaran_id' => $tahunAjaran->id,
        'wali_kelas_id' => $teacher->id,
    ]);
    $mataPelajaran = MataPelajaran::create([
        'nama_mapel' => 'Matematika',
        'guru_id' => $teacher->id,
        'kelas_id' => $kelas->id,
    ]);

    DB::flushQueryLog();
    DB::enableQueryLog();

    livewire(Dashboard::class)
        ->assertOk()
        ->assertSee($mataPelajaran->nama_mapel);

    $subjectQueries = collect(DB::getQueryLog())
        ->pluck('query')
        ->map(fn (string $query): string => str_replace(['`', '"'], '', mb_strtolower($query)))
        ->filter(fn (string $query): bool => str_contains($query, 'select * from mata_pelajaran'));

    expect($subjectQueries)->toHaveCount(1);
});
