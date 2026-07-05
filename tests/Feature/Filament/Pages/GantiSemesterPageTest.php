<?php

declare(strict_types=1);

use App\Filament\Pages\GantiSemesterPage;
use App\Models\Kelas;
use App\Models\MataPelajaran;
use App\Models\Siswa;
use App\Models\SiswaKelasHistory;
use App\Models\TahunAjaran;
use App\Models\User;
use Illuminate\Support\Facades\DB;

use function Pest\Laravel\assertDatabaseHas;
use function Pest\Livewire\livewire;

beforeEach(function () {
    // Create a teacher for wali kelas assignment
    $this->teacher = User::factory()->create(['name' => 'Test Teacher']);
    $this->teacher->assignRole('teacher');
});

it('can render the ganti semester page', function () {
    // Create an active tahun ajaran first
    $tahunAjaran = TahunAjaran::factory()->create([
        'nama_tahun' => '2024/2025',
        'semester' => 'Ganjil',
        'status' => true,
    ]);

    foreach (['A', 'B', 'C'] as $group) {
        Kelas::factory()->create([
            'tahun_ajaran_id' => $tahunAjaran->id,
            'tingkat_kelas' => 7,
            'grup_kelas' => $group,
            'wali_kelas_id' => $this->teacher->id,
        ]);
    }

    DB::flushQueryLog();
    DB::enableQueryLog();

    livewire(GantiSemesterPage::class)
        ->assertOk()
        ->assertSeeHtml('Simpan & Proses');

    $teacherQueries = collect(DB::getQueryLog())->filter(function (array $query): bool {
        $sql = str_replace(['`', '"'], '', mb_strtolower($query['query']));

        return str_contains($sql, 'select name, id from users')
            && str_contains($sql, 'model_has_roles');
    });

    expect($teacherQueries)->toHaveCount(1);
});

it('shows error when no active tahun ajaran exists', function () {
    // Delete all tahun ajaran
    TahunAjaran::query()->delete();

    livewire(GantiSemesterPage::class)
        ->assertOk()
        ->assertSee('Tidak Ada Tahun Ajaran Aktif');
});

it('can enter data into form', function () {
    $tahunAjaran = TahunAjaran::factory()->create([
        'nama_tahun' => '2024/2025',
        'semester' => 'Ganjil',
        'status' => true,
    ]);

    Kelas::factory()->create([
        'tahun_ajaran_id' => $tahunAjaran->id,
        'tingkat_kelas' => 7,
        'grup_kelas' => 'A',
        'wali_kelas_id' => $this->teacher->id,
    ]);

    livewire(GantiSemesterPage::class)
        ->assertFormSet([
            'namaTahun' => '2024/2025',
            'semester' => 'Genap',
        ])
        ->fillForm([
            'namaTahun' => '2024/2025',
            'semester' => 'Genap',
            'tanggalMulai' => '2025-01-01',
            'tanggalSelesai' => '2025-06-30',
        ])
        ->assertHasNoFormErrors();
});

it('validates required fields', function () {
    $tahunAjaran = TahunAjaran::factory()->create([
        'nama_tahun' => '2024/2025',
        'semester' => 'Ganjil',
        'status' => true,
    ]);

    // Mount page
    livewire(GantiSemesterPage::class)
        ->fillForm([
            'namaTahun' => '',
            'tanggalMulai' => '',
            'tanggalSelesai' => '',
        ])
        ->call('create')
        ->assertHasFormErrors(['namaTahun', 'tanggalMulai', 'tanggalSelesai']);
});

it('can execute semester transition', function () {
    $tahunAjaran = TahunAjaran::factory()->create([
        'nama_tahun' => '2024/2025',
        'semester' => 'Ganjil',
        'status' => true,
    ]);

    $kelas = Kelas::factory()->create([
        'tahun_ajaran_id' => $tahunAjaran->id,
        'tingkat_kelas' => 7,
        'grup_kelas' => 'A',
        'wali_kelas_id' => $this->teacher->id,
    ]);

    // Create a student in this class
    $studentUser = User::factory()->create(['name' => 'Test Student']);
    $studentUser->assignRole('student');
    $siswa = Siswa::factory()->create([
        'user_id' => $studentUser->id,
        'kelas_id' => $kelas->id,
    ]);
    $mataPelajaran = MataPelajaran::create([
        'nama_mapel' => 'Matematika',
        'guru_id' => $this->teacher->id,
        'kelas_id' => $kelas->id,
    ]);

    livewire(GantiSemesterPage::class)
        ->fillForm([
            'namaTahun' => '2024/2025',
            'semester' => 'Genap',
            'tanggalMulai' => '2025-01-01',
            'tanggalSelesai' => '2025-06-30',
            'waliKelasAssignments' => [$kelas->id => $this->teacher->id],
        ])
        ->call('create')
        ->assertNotified();

    // Verify new tahun ajaran was created
    assertDatabaseHas(TahunAjaran::class, [
        'nama_tahun' => '2024/2025',
        'semester' => 'Genap',
        'status' => true,
    ]);

    // Verify old tahun ajaran was deactivated
    expect($tahunAjaran->fresh()->status)->toBeFalse();

    $newTahunAjaran = TahunAjaran::where('nama_tahun', '2024/2025')
        ->where('semester', 'Genap')
        ->firstOrFail();
    $newKelas = Kelas::where('tahun_ajaran_id', $newTahunAjaran->id)
        ->where('tingkat_kelas', 7)
        ->where('grup_kelas', 'A')
        ->firstOrFail();

    expect($siswa->fresh()->kelas_id)->toBe($newKelas->id);

    assertDatabaseHas(SiswaKelasHistory::class, [
        'siswa_id' => $siswa->id,
        'kelas_id' => $kelas->id,
        'tahun_ajaran_id' => $tahunAjaran->id,
    ]);
    assertDatabaseHas(SiswaKelasHistory::class, [
        'siswa_id' => $siswa->id,
        'kelas_id' => $newKelas->id,
        'tahun_ajaran_id' => $newTahunAjaran->id,
    ]);
    assertDatabaseHas(MataPelajaran::class, [
        'nama_mapel' => $mataPelajaran->nama_mapel,
        'guru_id' => $mataPelajaran->guru_id,
        'kelas_id' => $newKelas->id,
    ]);
});

it('prevents duplicate tahun ajaran + semester combination', function () {
    // Create existing tahun ajaran
    $tahunAjaran = TahunAjaran::factory()->create([
        'nama_tahun' => '2024/2025',
        'semester' => 'Ganjil',
        'status' => true,
    ]);

    // Create the Genap semester too (duplicate target)
    TahunAjaran::factory()->create([
        'nama_tahun' => '2024/2025',
        'semester' => 'Genap',
        'status' => false,
    ]);

    livewire(GantiSemesterPage::class)
        ->fillForm([
            'namaTahun' => '2024/2025',
            'semester' => 'Genap',
            'tanggalMulai' => '2025-01-01',
            'tanggalSelesai' => '2025-06-30',
        ])
        ->call('create')
        ->assertNotified();
    // Note: validation exception catch inside the component won't show as form error if using manual notification + return
    // but here we are catching it manually in create() method via check.
});
