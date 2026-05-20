<?php

declare(strict_types=1);

use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;

Route::redirect('/', '/app/login');

// Health check for Cloud Run and local smoke tests with database connectivity check
Route::get('/health', function () {
    try {
        DB::connection()->getPdo();

        return response()->json(['status' => 'ok']);
    } catch (Exception $e) {
        report($e);

        return response()->json(['status' => 'error'], 503);
    }
});

// API endpoint for checking role authorization (used by bfcache guard)
Route::get('/app/api/check-role', function () {
    if (! Auth::check()) {
        return response()->json(['authorized' => false], 401);
    }

    /** @var User $user */
    $user = Auth::user();

    // Only admin users are authorized to access /app
    if ($user->hasRole('admin')) {
        return response()->json(['authorized' => true]);
    }

    // Teachers and students are not authorized — return 403.
    // The client-side inactivity-timer.js will redirect to login on any non-2xx response.
    return response()->json(['authorized' => false], 403);
})->middleware(['web']);

/*
|--------------------------------------------------------------------------
| Teacher Routes (Livewire + Flux UI)
|--------------------------------------------------------------------------
|
| These routes are for the teacher interface using Flux UI components.
| All routes require authentication and the 'teacher' role.
|
*/
Route::middleware(['auth', 'role:teacher'])->prefix('teacher')->name('teacher.')->group(function () {
    Route::get('/', App\Livewire\Teacher\Dashboard::class)->name('dashboard');
    Route::get('/aktivitas', App\Livewire\Teacher\AktivitasPembelajaran\ListAktivitas::class)
        ->name('aktivitas.list');
    Route::get('/aktivitas/create', App\Livewire\Teacher\AktivitasPembelajaran\CreateAktivitas::class)
        ->name('aktivitas.create');
    Route::get('/aktivitas/{id}/edit', App\Livewire\Teacher\AktivitasPembelajaran\EditAktivitas::class)
        ->name('aktivitas.edit');
    Route::get('/aktivitas/{id}', App\Livewire\Teacher\AktivitasPembelajaran\ViewAktivitas::class)
        ->name('aktivitas.view');
    Route::get('/laporan', App\Livewire\Teacher\Laporan::class)->name('laporan');
    Route::get('/profil', App\Livewire\Teacher\TeacherProfile::class)->name('profil');
});

/*
|--------------------------------------------------------------------------
| Student Routes (Livewire + Flux UI)
|--------------------------------------------------------------------------
|
| These routes are for the student interface using Flux UI components.
| All routes require authentication and the 'student' role.
|
*/
Route::middleware(['auth', 'role:student'])->prefix('student')->name('student.')->group(function () {
    Route::get('/', App\Livewire\Student\Dashboard::class)->name('dashboard');
    Route::get('/riwayat', App\Livewire\Student\RiwayatAktivitas::class)->name('riwayat');
    Route::get('/profil', App\Livewire\Student\Profil::class)->name('profil');
});

/*
|--------------------------------------------------------------------------
| Report Export Routes
|--------------------------------------------------------------------------
|
| Routes for exporting reports to Excel format.
| Only teachers and operators can export class reports.
|
*/
Route::middleware(['auth', 'role:teacher|operator'])->group(function () {
    Route::post('/reports/class/export', [App\Http\Controllers\ClassReportExportController::class, 'export'])
        ->name('reports.class.export');
});

/*
|--------------------------------------------------------------------------
| Report Preview Routes (Local Development Only)
|--------------------------------------------------------------------------
|
| These routes allow previewing report templates during development.
| They are only available in local environment.
|
*/
if (app()->environment('local')) {
    Route::prefix('reports/preview')->name('reports.preview.')->group(function () {
        Route::get('/student/{siswa}', function (App\Models\Siswa $siswa) {
            $tahunAjaran = App\Models\TahunAjaran::where('status', true)->first();
            $laporanData = App\Models\Laporan::where('siswa_id', $siswa->id)
                ->where('tahun_ajaran_id', $tahunAjaran?->id)
                ->with(['mataPelajaran', 'tahunAjaran'])
                ->get();

            return view('reports.student-report', [
                'siswa' => $siswa->load(['user', 'kelas.waliKelas']),
                'tahunAjaran' => $tahunAjaran,
                'laporanData' => $laporanData,
            ]);
        })->name('student');

        Route::get('/class/{kelas}/{mataPelajaran}', function (
            App\Models\Kelas $kelas,
            App\Models\MataPelajaran $mataPelajaran
        ) {
            $tahunAjaran = App\Models\TahunAjaran::where('status', true)->first();
            $laporanData = App\Models\Laporan::where('tahun_ajaran_id', $tahunAjaran?->id)
                ->where('mata_pelajaran_id', $mataPelajaran->id)
                ->whereHas('siswa', fn ($q) => $q->where('kelas_id', $kelas->id))
                ->with(['siswa.user', 'mataPelajaran'])
                ->get()
                ->sortByDesc('rata_nilai')
                ->values();

            return view('reports.class-report', [
                'kelas' => $kelas->load('waliKelas'),
                'mataPelajaran' => $mataPelajaran->load('guru'),
                'tahunAjaran' => $tahunAjaran,
                'laporanData' => $laporanData,
            ]);
        })->name('class');
    });
}
