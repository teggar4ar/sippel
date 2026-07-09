<?php

declare(strict_types=1);

use App\Livewire\Teacher\AktivitasPembelajaran\ListAktivitas;
use App\Models\AktivitasPembelajaran;
use App\Models\Kelas;
use App\Models\MataPelajaran;
use App\Models\TahunAjaran;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;

afterEach(function (): void {
    DB::statement('PRAGMA case_sensitive_like = false');
});

it('finds teacher activity history regardless of search term casing', function (): void {
    DB::statement('PRAGMA case_sensitive_like = true');

    $tahunAjaran = TahunAjaran::factory()->active()->create();
    $kelas = Kelas::factory()->create([
        'tahun_ajaran_id' => $tahunAjaran->id,
    ]);

    $teacher = User::factory()->create();
    $teacher->assignRole('teacher');

    $mataPelajaran = MataPelajaran::factory()->create([
        'nama_mapel' => 'Matematika',
        'guru_id' => $teacher->id,
        'kelas_id' => $kelas->id,
    ]);

    AktivitasPembelajaran::factory()->create([
        'topik' => 'Persamaan Linear',
        'kelas_id' => $kelas->id,
        'mata_pelajaran_id' => $mataPelajaran->id,
        'guru_id' => $teacher->id,
    ]);

    $this->actingAs($teacher);

    Livewire::test(ListAktivitas::class)
        ->set('search', 'persamaan')
        ->assertSee('Persamaan Linear');
});
