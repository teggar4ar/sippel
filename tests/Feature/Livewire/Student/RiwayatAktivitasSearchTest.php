<?php

declare(strict_types=1);

use App\Livewire\Student\RiwayatAktivitas;
use App\Models\AktivitasPembelajaran;
use App\Models\DetailAktivitas;
use App\Models\Kelas;
use App\Models\MataPelajaran;
use App\Models\Siswa;
use App\Models\TahunAjaran;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;

afterEach(function (): void {
    DB::statement('PRAGMA case_sensitive_like = false');
});

it('finds student activity history regardless of search term casing', function (): void {
    DB::statement('PRAGMA case_sensitive_like = true');

    $tahunAjaran = TahunAjaran::factory()->active()->create();
    $kelas = Kelas::factory()->create([
        'tahun_ajaran_id' => $tahunAjaran->id,
    ]);

    $studentUser = User::factory()->create();
    $studentUser->assignRole('student');

    $siswa = Siswa::factory()->create([
        'user_id' => $studentUser->id,
        'kelas_id' => $kelas->id,
    ]);

    $mataPelajaran = MataPelajaran::factory()->create([
        'nama_mapel' => 'Matematika',
        'kelas_id' => $kelas->id,
    ]);

    $aktivitas = AktivitasPembelajaran::factory()->create([
        'topik' => 'Persamaan Linear',
        'kelas_id' => $kelas->id,
        'mata_pelajaran_id' => $mataPelajaran->id,
    ]);

    DetailAktivitas::create([
        'kehadiran' => 'hadir',
        'keaktifan' => 'aktif',
        'siswa_id' => $siswa->id,
        'aktivitas_pembelajaran_id' => $aktivitas->id,
    ]);

    $this->actingAs($studentUser);

    Livewire::test(RiwayatAktivitas::class)
        ->set('search', 'matematika')
        ->assertSee('Persamaan Linear')
        ->set('search', 'persamaan')
        ->assertSee('Persamaan Linear');
});
