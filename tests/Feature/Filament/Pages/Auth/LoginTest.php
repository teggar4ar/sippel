<?php

declare(strict_types=1);

/** @var Tests\TestCase $this */

use App\Filament\Pages\Auth\Login;
use App\Models\Kelas;
use App\Models\Siswa;
use App\Models\TahunAjaran;
use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

use function Pest\Livewire\livewire;

test('an unauthenticated user can access the login page', function (): void {
    Auth::logout();

    $this->get(Filament::getLoginUrl())
        ->assertOk();
});

test('an unauthenticated user can not access the admin panel', function (): void {
    Auth::logout();

    $this->get('app')
        ->assertRedirect(Filament::getLoginUrl());
});

test('an unauthenticated user can login with email', function (): void {
    Auth::logout();
    Filament::auth()->logout();

    livewire(Login::class)
        ->set('data.identifier', config('app.default_user.email'))
        ->set('data.password', config('app.default_user.password'))
        ->call('authenticate')
        ->assertHasNoFormErrors();
});

test('a student can login with NIS', function (): void {
    Auth::logout();
    Filament::auth()->logout();

    // Create test data: TahunAjaran -> Kelas -> User (with student role) -> Siswa
    $tahunAjaran = TahunAjaran::factory()->create(['status' => true]);

    $waliKelas = User::factory()->create();
    $waliKelas->assignRole('teacher');

    $kelas = Kelas::factory()->create([
        'tahun_ajaran_id' => $tahunAjaran->id,
        'wali_kelas_id' => $waliKelas->id,
    ]);

    $studentUser = User::factory()->create([
        'password' => Hash::make('student123'),
    ]);
    $studentUser->assignRole('student');

    $siswa = Siswa::create([
        'nis' => '123456789',
        'user_id' => $studentUser->id,
        'kelas_id' => $kelas->id,
    ]);

    // Login with NIS instead of email
    livewire(Login::class)
        ->set('data.identifier', '123456789')
        ->set('data.password', 'student123')
        ->call('authenticate')
        ->assertHasNoFormErrors();

    // Verify the student user is authenticated
    expect(Auth::check())->toBeTrue();
    expect(Auth::id())->toBe($studentUser->id);
});

test('invalid NIS shows error message', function (): void {
    Auth::logout();
    Filament::auth()->logout();

    livewire(Login::class)
        ->set('data.identifier', '999999999')
        ->set('data.password', 'wrongpassword')
        ->call('authenticate')
        ->assertHasFormErrors(['identifier']);
});

test('an authenticated user can access the admin panel', function (): void {
    $this->get('app')
        ->assertOk();
});

test('an authenticated user can logout', function (): void {
    $this->assertAuthenticated();

    // Disable CSRF validation for this test
    $this->withoutMiddleware(Illuminate\Foundation\Http\Middleware\VerifyCsrfToken::class);

    $this->post(Filament::getLogoutUrl())
        ->assertRedirect(Filament::getLoginUrl());
});
