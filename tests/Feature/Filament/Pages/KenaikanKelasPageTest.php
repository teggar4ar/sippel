<?php

declare(strict_types=1);

use App\Filament\Pages\KenaikanKelasPage;
use App\Models\Kelas;
use App\Models\Siswa;
use App\Models\TahunAjaran;
use App\Models\User;
use Illuminate\Support\Facades\Auth;

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

    // Create classes for grades 7, 8, 9
    Kelas::factory()->create([
        'tahun_ajaran_id' => $tahunAjaran->id,
        'tingkat_kelas' => 7,
        'grup_kelas' => 'A',
        'wali_kelas_id' => $this->teacher1->id,
    ]);

    livewire(KenaikanKelasPage::class)
        ->assertOk()
        ->assertSee('Tahun Ajaran Aktif');
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

it('can select class for student decisions', function () {
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

    livewire(KenaikanKelasPage::class)
        ->call('selectKelas', $kelas->id)
        ->assertSet('selectedKelasId', $kelas->id);
});

it('can select all students as naik kelas', function () {
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

    $studentUser1 = User::factory()->create(['name' => 'Siswa 1']);
    $studentUser1->assignRole('student');
    $siswa1 = Siswa::factory()->create(['user_id' => $studentUser1->id, 'kelas_id' => $kelas->id]);

    $studentUser2 = User::factory()->create(['name' => 'Siswa 2']);
    $studentUser2->assignRole('student');
    $siswa2 = Siswa::factory()->create(['user_id' => $studentUser2->id, 'kelas_id' => $kelas->id]);

    livewire(KenaikanKelasPage::class)
        ->call('selectKelas', $kelas->id)
        ->call('selectAllNaik')
        ->assertNotified()
        ->assertSet("data.studentDecisions.{$siswa1->id}", 'naik')
        ->assertSet("data.studentDecisions.{$siswa2->id}", 'naik');
});

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
            'studentDecisions' => [$siswa->id => 'naik']
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
            'studentDecisions' => [$siswa->id => 'lulus']
        ])
        ->call('create')
        ->assertNotified();

    assertSoftDeleted(Siswa::class, ['id' => $siswa->id]);
    assertSoftDeleted(User::class, ['id' => $studentUser->id]);
});

it('counts advancing, repeating, and graduating students correctly', function () {
    $tahunAjaran = TahunAjaran::factory()->create([
        'nama_tahun' => '2024/2025',
        'semester' => 'Genap',
        'status' => true,
    ]);

    $kelas7 = Kelas::factory()->create(['tahun_ajaran_id' => $tahunAjaran->id, 'tingkat_kelas' => 7, 'grup_kelas' => 'A', 'wali_kelas_id' => $this->teacher1->id]);
    $kelas9 = Kelas::factory()->create(['tahun_ajaran_id' => $tahunAjaran->id, 'tingkat_kelas' => 9, 'grup_kelas' => 'A', 'wali_kelas_id' => $this->teacher1->id]);

    $students = [];
    for ($i = 0; $i < 3; $i++) {
        $user = User::factory()->create();
        $user->assignRole('student');
        $students[] = Siswa::factory()->create(['user_id' => $user->id, 'kelas_id' => $kelas7->id]);
    }

    $graduatingUser = User::factory()->create();
    $graduatingUser->assignRole('student');
    $graduatingSiswa = Siswa::factory()->create(['user_id' => $graduatingUser->id, 'kelas_id' => $kelas9->id]);

    $component = livewire(KenaikanKelasPage::class)
        ->fillForm([
            'studentDecisions' => [
                $students[0]->id => 'naik',
                $students[1]->id => 'naik',
                $students[2]->id => 'tinggal',
                $graduatingSiswa->id => 'lulus',
            ]
        ]);

    expect($component->getAdvancingCountProperty())->toBe(2);
    expect($component->getRepeatingCountProperty())->toBe(1);
    expect($component->getGraduatingCountProperty())->toBe(1);
});

