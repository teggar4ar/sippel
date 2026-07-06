<?php

declare(strict_types=1);

use App\Enums\Keaktifan;
use App\Models\AktivitasPembelajaran;
use App\Models\DetailAktivitas;
use App\Models\Kelas;
use App\Models\Laporan;
use App\Models\MataPelajaran;
use App\Models\Siswa;
use App\Models\TahunAjaran;
use App\Models\User;
use App\Services\LaporanCalculatorService;
use Illuminate\Support\Facades\Schema;

it('uses only the new keaktifan columns', function (): void {
    expect(Schema::hasColumns('detail_aktivitas', ['keaktifan']))->toBeTrue()
        ->and(Schema::hasColumn('detail_aktivitas', 'nilai'))->toBeFalse()
        ->and(Schema::hasColumn('detail_aktivitas', 'partisipasi'))->toBeFalse()
        ->and(Schema::hasColumns('laporan', ['rata_keaktifan']))->toBeTrue()
        ->and(Schema::hasColumn('laporan', 'rata_nilai'))->toBeFalse()
        ->and(Schema::hasColumn('laporan', 'rata_partisipasi'))->toBeFalse();
});

it('casts detail and report keaktifan columns to enums', function (): void {
    [$tahunAjaran, $kelas, $mapel, $siswa, $guru] = createKeaktifanContext();
    $aktivitas = createKeaktifanActivity($kelas, $mapel, $guru, '2026-07-01');

    $detail = DetailAktivitas::create([
        'siswa_id' => $siswa->id,
        'aktivitas_pembelajaran_id' => $aktivitas->id,
        'kehadiran' => 'hadir',
        'keaktifan' => Keaktifan::Aktif,
    ])->refresh();

    $laporan = Laporan::firstOrFail();
    $laporan->update(['rata_keaktifan' => Keaktifan::Cukup]);
    $laporan->refresh();

    expect($detail->keaktifan)->toBe(Keaktifan::Aktif)
        ->and($detail->label_keaktifan)->toBe('Aktif')
        ->and($laporan->rata_keaktifan)->toBe(Keaktifan::Cukup);
});

it('calculates attendance and stores the average keaktifan category without grades', function (): void {
    [$tahunAjaran, $kelas, $mapel, $siswa, $guru] = createKeaktifanContext();

    $observations = [
        ['date' => '2026-07-01', 'attendance' => 'hadir', 'keaktifan' => Keaktifan::Pasif],
        ['date' => '2026-07-02', 'attendance' => 'hadir', 'keaktifan' => Keaktifan::SangatAktif],
        ['date' => '2026-07-03', 'attendance' => 'izin', 'keaktifan' => null],
    ];

    foreach ($observations as $observation) {
        $aktivitas = createKeaktifanActivity($kelas, $mapel, $guru, $observation['date']);
        DetailAktivitas::withoutEvents(
            fn (): DetailAktivitas => DetailAktivitas::create([
                'siswa_id' => $siswa->id,
                'aktivitas_pembelajaran_id' => $aktivitas->id,
                'kehadiran' => $observation['attendance'],
                'keaktifan' => $observation['keaktifan'],
            ])
        );
    }

    $result = app(LaporanCalculatorService::class)->recalculateForCombination(
        $siswa->id,
        $mapel->id,
        $tahunAjaran->id,
    );

    $laporan = Laporan::firstOrFail();

    expect($result)->toBe('created')
        ->and($laporan->hadir_count)->toBe(2)
        ->and($laporan->izin_count)->toBe(1)
        ->and($laporan->total_kehadiran)->toBe(3)
        ->and($laporan->rata_kehadiran)->toBe(66.67)
        ->and($laporan->rata_keaktifan)->toBe(Keaktifan::Aktif)
        ->and($siswa->getAverageKeaktifan($mapel->id, tahunAjaranId: $tahunAjaran->id))->toBe(2.5)
        ->and($siswa->getAverageKeaktifanLabel($mapel->id, tahunAjaranId: $tahunAjaran->id))->toBe('Aktif');
});

/**
 * @return array{TahunAjaran, Kelas, MataPelajaran, Siswa, User}
 */
function createKeaktifanContext(): array
{
    $tahunAjaran = TahunAjaran::factory()->active()->create([
        'tanggal_mulai' => '2026-07-01',
        'tanggal_selesai' => '2026-12-31',
    ]);
    $guru = User::factory()->create();
    $kelas = Kelas::factory()->create([
        'tahun_ajaran_id' => $tahunAjaran->id,
        'wali_kelas_id' => $guru->id,
    ]);
    $mapel = MataPelajaran::factory()->create([
        'kelas_id' => $kelas->id,
        'guru_id' => $guru->id,
    ]);
    $siswa = Siswa::factory()->create(['kelas_id' => $kelas->id]);

    return [$tahunAjaran, $kelas, $mapel, $siswa, $guru];
}

function createKeaktifanActivity(Kelas $kelas, MataPelajaran $mapel, User $guru, string $date): AktivitasPembelajaran
{
    return AktivitasPembelajaran::factory()->create([
        'kelas_id' => $kelas->id,
        'mata_pelajaran_id' => $mapel->id,
        'guru_id' => $guru->id,
        'tanggal' => $date,
    ]);
}
