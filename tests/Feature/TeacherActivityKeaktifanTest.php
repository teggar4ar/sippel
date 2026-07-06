<?php

declare(strict_types=1);

use App\Enums\Keaktifan;
use App\Livewire\Teacher\AktivitasPembelajaran\CreateAktivitas;
use App\Livewire\Teacher\AktivitasPembelajaran\EditAktivitas;
use App\Livewire\Teacher\AktivitasPembelajaran\ListAktivitas;
use App\Livewire\Teacher\Dashboard;
use App\Models\AktivitasPembelajaran;
use App\Models\DetailAktivitas;
use App\Models\Kelas;
use App\Models\Laporan;
use App\Models\MataPelajaran;
use App\Models\Siswa;
use App\Models\TahunAjaran;
use App\Models\User;
use Carbon\Carbon;
use Livewire\Livewire;

beforeEach(function (): void {
    Carbon::setTestNow('2026-07-06 10:00:00');
    $this->tahunAjaran = TahunAjaran::factory()->active()->create([
        'tanggal_mulai' => '2026-07-01',
        'tanggal_selesai' => '2026-12-31',
    ]);
    $this->teacher = User::factory()->create();
    $this->teacher->assignRole('teacher');
    $this->actingAs($this->teacher);

    $this->kelas = Kelas::factory()->create([
        'tahun_ajaran_id' => $this->tahunAjaran->id,
        'wali_kelas_id' => $this->teacher->id,
    ]);
    $this->mapel = MataPelajaran::factory()->create([
        'kelas_id' => $this->kelas->id,
        'guru_id' => $this->teacher->id,
    ]);
    $this->siswa = Siswa::factory()->create(['kelas_id' => $this->kelas->id]);
});

afterEach(function (): void {
    Carbon::setTestNow();
});

it('creates an activity using a qualitative keaktifan enum', function (): void {
    Livewire::test(CreateAktivitas::class)
        ->set('tanggal', '2026-07-06')
        ->set('mataPelajaranId', $this->mapel->id)
        ->set('kelasId', $this->kelas->id)
        ->set('topik', 'Pengujian Enum')
        ->call('saveWithDetail', [
            $this->siswa->id => [
                'siswa_id' => $this->siswa->id,
                'kehadiran' => 'Hadir',
                'keaktifan' => Keaktifan::SangatAktif->value,
                'catatan' => 'Aktif berdiskusi.',
            ],
        ])
        ->assertHasNoErrors();

    $detail = DetailAktivitas::firstOrFail();

    expect($detail->keaktifan)->toBe(Keaktifan::SangatAktif)
        ->and($detail->getAttributes())->not->toHaveKeys(['nilai', 'partisipasi']);
});

it('rejects a keaktifan value outside the enum', function (): void {
    Livewire::test(CreateAktivitas::class)
        ->set('tanggal', '2026-07-06')
        ->set('mataPelajaranId', $this->mapel->id)
        ->set('kelasId', $this->kelas->id)
        ->set('topik', 'Pengujian Enum')
        ->call('saveWithDetail', [
            $this->siswa->id => [
                'siswa_id' => $this->siswa->id,
                'kehadiran' => 'Hadir',
                'keaktifan' => 'sangat_rajin',
                'catatan' => '',
            ],
        ])
        ->assertHasErrors(['detailAktivitas.'.$this->siswa->id.'.keaktifan']);

    expect(DetailAktivitas::query()->count())->toBe(0);
});

it('clears keaktifan when an existing student is marked absent', function (): void {
    $aktivitas = AktivitasPembelajaran::factory()->create([
        'kelas_id' => $this->kelas->id,
        'mata_pelajaran_id' => $this->mapel->id,
        'guru_id' => $this->teacher->id,
        'tanggal' => '2026-07-06',
    ]);
    DetailAktivitas::create([
        'siswa_id' => $this->siswa->id,
        'aktivitas_pembelajaran_id' => $aktivitas->id,
        'kehadiran' => 'hadir',
        'keaktifan' => Keaktifan::Aktif,
    ]);

    Livewire::test(EditAktivitas::class, ['id' => $aktivitas->id])
        ->call('saveWithDetail', [
            $this->siswa->id => [
                'siswa_id' => $this->siswa->id,
                'kehadiran' => 'Izin',
                'keaktifan' => Keaktifan::SangatAktif->value,
                'catatan' => '',
            ],
        ])
        ->assertHasNoErrors();

    $detail = DetailAktivitas::firstOrFail()->refresh();

    expect($detail->kehadiran->value)->toBe('izin')
        ->and($detail->keaktifan)->toBeNull();
});

it('excludes a soft deleted activity from dashboard statistics and laporan', function (): void {
    $aktivitas = AktivitasPembelajaran::factory()->create([
        'kelas_id' => $this->kelas->id,
        'mata_pelajaran_id' => $this->mapel->id,
        'guru_id' => $this->teacher->id,
        'tanggal' => '2026-07-06',
    ]);
    DetailAktivitas::create([
        'siswa_id' => $this->siswa->id,
        'aktivitas_pembelajaran_id' => $aktivitas->id,
        'kehadiran' => 'hadir',
        'keaktifan' => Keaktifan::Aktif,
    ]);

    expect((new Dashboard)->dashboardStats()['aktivitas_minggu_ini'])->toBe(1)
        ->and(Laporan::query()->count())->toBe(1);

    Livewire::test(ListAktivitas::class)
        ->set('deleteId', $aktivitas->id)
        ->call('deleteAktivitas');

    $this->assertSoftDeleted('aktivitas_pembelajaran', ['id' => $aktivitas->id]);

    expect((new Dashboard)->dashboardStats()['aktivitas_minggu_ini'])->toBe(0)
        ->and(Laporan::query()->count())->toBe(0);
});
