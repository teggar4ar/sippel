<?php

declare(strict_types=1);

use App\Filament\Pages\KenaikanKelasPage;
use App\Models\Kelas;
use App\Models\Siswa;
use App\Models\SiswaKelasHistory;
use App\Models\TahunAjaran;
use App\Models\User;
use Illuminate\Support\Facades\DB;

use function Pest\Laravel\assertDatabaseHas;
use function Pest\Laravel\assertSoftDeleted;
use function Pest\Livewire\livewire;

beforeEach(function () {
    // Create teachers for wali kelas assignment
    $this->teacher1 = User::factory()->create(['name' => 'Teacher One']);
    $this->teacher1->assignRole('teacher');

    $this->teacher2 = User::factory()->create(['name' => 'Teacher Two']);
    $this->teacher2->assignRole('teacher');
});

it('can render the kenaikan kelas page when semester is Genap', function () {
    // Create an active Genap semester
    $tahunAjaran = TahunAjaran::factory()->create([
        'nama_tahun' => '2024/2025',
        'semester' => 'Genap',
        'status' => true,
    ]);

    foreach ([7, 8] as $grade) {
        foreach (['A', 'B'] as $group) {
            Kelas::factory()->create([
                'tahun_ajaran_id' => $tahunAjaran->id,
                'tingkat_kelas' => $grade,
                'grup_kelas' => $group,
                'wali_kelas_id' => $this->teacher1->id,
            ]);
        }
    }

    DB::flushQueryLog();
    DB::enableQueryLog();

    livewire(KenaikanKelasPage::class)
        ->assertOk()
        ->assertSee('Eksekusi Kenaikan Kelas');

    $teacherQueries = collect(DB::getQueryLog())->filter(function (array $query): bool {
        $sql = str_replace(['`', '"'], '', mb_strtolower($query['query']));

        return str_contains($sql, 'select name, id from users')
            && str_contains($sql, 'model_has_roles');
    });

    expect($teacherQueries)->toHaveCount(1);
});

it('shows error when semester is not Genap', function () {
    // Create an active Ganjil semester
    $tahunAjaran = TahunAjaran::factory()->create([
        'nama_tahun' => '2024/2025',
        'semester' => 'Ganjil',
        'status' => true,
    ]);

    Kelas::factory()->create([
        'tahun_ajaran_id' => $tahunAjaran->id,
        'tingkat_kelas' => 7,
        'grup_kelas' => 'A',
        'wali_kelas_id' => $this->teacher1->id,
    ]);

    livewire(KenaikanKelasPage::class)
        ->assertOk()
        ->assertSee('Belum Waktunya Kenaikan Kelas');
});

it('shows error when no active tahun ajaran exists', function () {
    // Delete all tahun ajaran
    TahunAjaran::query()->delete();

    livewire(KenaikanKelasPage::class)
        ->assertOk()
        ->assertSee('Tidak Ada Tahun Ajaran Aktif');
});

it('can enter data into form', function () {
    $tahunAjaran = TahunAjaran::factory()->create([
        'nama_tahun' => '2024/2025',
        'semester' => 'Genap',
        'status' => true,
    ]);

    Kelas::factory()->create([
        'tahun_ajaran_id' => $tahunAjaran->id,
        'tingkat_kelas' => 7,
        'grup_kelas' => 'A',
        'wali_kelas_id' => $this->teacher1->id,
    ]);

    livewire(KenaikanKelasPage::class)
        ->assertFormSet([
            'namaTahun' => '2025/2026',
        ])
        ->fillForm([
            'namaTahun' => '2026/2027',
            'tanggalMulai' => '2026-07-01',
            'tanggalSelesai' => '2026-12-31',
        ])
        ->assertHasNoFormErrors();
});

it('validates required fields', function () {
    $tahunAjaran = TahunAjaran::factory()->create([
        'nama_tahun' => '2024/2025',
        'semester' => 'Genap',
        'status' => true,
    ]);

    // Mount page
    livewire(KenaikanKelasPage::class)
        ->fillForm([
            'namaTahun' => '',
            'tanggalMulai' => '',
            'tanggalSelesai' => '',
        ])
        ->call('create')
        ->assertHasFormErrors(['namaTahun', 'tanggalMulai', 'tanggalSelesai']);
});

it('creates only grade 8 and 9 classes during advancement', function () {
    $tahunAjaran = TahunAjaran::factory()->create([
        'nama_tahun' => '2024/2025',
        'semester' => 'Genap',
        'status' => true,
    ]);

    Kelas::factory()->create([
        'tahun_ajaran_id' => $tahunAjaran->id,
        'tingkat_kelas' => 7,
        'grup_kelas' => 'A',
        'wali_kelas_id' => $this->teacher1->id,
    ]);

    $component = livewire(KenaikanKelasPage::class);

    // Check initial state of waliKelasAssignments in form data
    $assignments = $component->get('data.waliKelasAssignments');
    expect($assignments)->toHaveKeys(['8_A', '9_A']);
    expect($assignments)->not->toHaveKey('7_A');
});

// Test removed: selectKelas method no longer exists after migration to form-schema-based student selection

// Test removed: selectAllNaik method no longer exists after migration to form-schema-based student selection

it('can execute grade advancement', function () {
    $tahunAjaran = TahunAjaran::factory()->create([
        'nama_tahun' => '2024/2025',
        'semester' => 'Genap',
        'status' => true,
    ]);

    $kelas = Kelas::factory()->create([
        'tahun_ajaran_id' => $tahunAjaran->id,
        'tingkat_kelas' => 7,
        'grup_kelas' => 'A',
        'wali_kelas_id' => $this->teacher1->id,
    ]);

    $studentUser = User::factory()->create();
    $studentUser->assignRole('student');
    $siswa = Siswa::factory()->create(['user_id' => $studentUser->id, 'kelas_id' => $kelas->id]);

    livewire(KenaikanKelasPage::class)
        ->fillForm([
            'namaTahun' => '2025/2026',
            'semester' => 'Ganjil',
            'tanggalMulai' => '2025-07-01',
            'tanggalSelesai' => '2025-12-31',
            'waliKelasAssignments' => [
                '8_A' => $this->teacher1->id,
                '9_A' => $this->teacher2->id,
            ],
            'studentDecisions' => [$siswa->id => 'naik'],
        ])
        ->call('create')
        ->assertNotified();

    assertDatabaseHas(TahunAjaran::class, [
        'nama_tahun' => '2025/2026',
        'semester' => 'Ganjil',
        'status' => true,
    ]);

    expect($tahunAjaran->fresh()->status)->toBeFalse();

    assertDatabaseHas(Kelas::class, [
        'tingkat_kelas' => 8,
        'grup_kelas' => 'A',
        'wali_kelas_id' => $this->teacher1->id,
    ]);

    $newTahunAjaran = TahunAjaran::where('nama_tahun', '2025/2026')
        ->where('semester', 'Ganjil')
        ->firstOrFail();
    $newKelas = Kelas::where('tahun_ajaran_id', $newTahunAjaran->id)
        ->where('tingkat_kelas', 8)
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
});

it('keeps repeating students at the same grade and group in the new year', function () {
    $tahunAjaran = TahunAjaran::factory()->create([
        'nama_tahun' => '2024/2025',
        'semester' => 'Genap',
        'status' => true,
    ]);

    $kelas = Kelas::factory()->create([
        'tahun_ajaran_id' => $tahunAjaran->id,
        'tingkat_kelas' => 8,
        'grup_kelas' => 'B',
        'wali_kelas_id' => $this->teacher1->id,
    ]);

    $studentUser = User::factory()->create();
    $studentUser->assignRole('student');
    $siswa = Siswa::factory()->create([
        'user_id' => $studentUser->id,
        'kelas_id' => $kelas->id,
    ]);

    livewire(KenaikanKelasPage::class)
        ->fillForm([
            'namaTahun' => '2025/2026',
            'semester' => 'Ganjil',
            'tanggalMulai' => '2025-07-01',
            'tanggalSelesai' => '2025-12-31',
            'waliKelasAssignments' => [
                '8_A' => $this->teacher1->id,
                '9_B' => $this->teacher2->id,
            ],
            'studentDecisions' => [$siswa->id => 'tinggal'],
        ])
        ->call('create')
        ->assertNotified();

    $newTahunAjaran = TahunAjaran::where('nama_tahun', '2025/2026')
        ->where('semester', 'Ganjil')
        ->firstOrFail();
    $repeatingKelas = Kelas::where('tahun_ajaran_id', $newTahunAjaran->id)
        ->where('tingkat_kelas', 8)
        ->where('grup_kelas', 'B')
        ->firstOrFail();

    expect($siswa->fresh()->kelas_id)->toBe($repeatingKelas->id)
        ->and($repeatingKelas->wali_kelas_id)->toBe($kelas->wali_kelas_id);

    assertDatabaseHas(SiswaKelasHistory::class, [
        'siswa_id' => $siswa->id,
        'kelas_id' => $repeatingKelas->id,
        'tahun_ajaran_id' => $newTahunAjaran->id,
    ]);
});

it('soft deletes graduating students', function () {
    $tahunAjaran = TahunAjaran::factory()->create([
        'nama_tahun' => '2024/2025',
        'semester' => 'Genap',
        'status' => true,
    ]);

    $kelas9 = Kelas::factory()->create([
        'tahun_ajaran_id' => $tahunAjaran->id,
        'tingkat_kelas' => 9,
        'grup_kelas' => 'A',
        'wali_kelas_id' => $this->teacher1->id,
    ]);

    $studentUser = User::factory()->create();
    $studentUser->assignRole('student');
    $siswa = Siswa::factory()->create([
        'user_id' => $studentUser->id,
        'kelas_id' => $kelas9->id,
    ]);

    livewire(KenaikanKelasPage::class)
        ->fillForm([
            'namaTahun' => '2025/2026',
            'semester' => 'Ganjil',
            'tanggalMulai' => '2025-07-01',
            'tanggalSelesai' => '2025-12-31',
            'waliKelasAssignments' => [
                '8_A' => $this->teacher1->id,
                '9_A' => $this->teacher2->id, // dummy for validation
            ],
            'studentDecisions' => [$siswa->id => 'lulus'],
        ])
        ->call('create')
        ->assertNotified();

    assertSoftDeleted(Siswa::class, ['id' => $siswa->id]);
    assertSoftDeleted(User::class, ['id' => $studentUser->id]);
    assertDatabaseHas(SiswaKelasHistory::class, [
        'siswa_id' => $siswa->id,
        'kelas_id' => $kelas9->id,
        'tahun_ajaran_id' => $tahunAjaran->id,
    ]);
});

// Test removed: advancingCount, repeatingCount, graduatingCount computed properties no longer exist
// The statistics are now calculated in the Placeholder field using the form data directly
