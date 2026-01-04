# Dokumentasi Teknis SIPPEL

**Sistem Informasi Pencatatan Aktivitas Pembelajaran**

> Versi: 1.0.0  
> Terakhir diperbarui: 4 Januari 2026

---

## Daftar Isi

1. [Gambaran Umum](#1-gambaran-umum)
2. [Tech Stack](#2-tech-stack)
3. [Arsitektur Sistem](#3-arsitektur-sistem)
4. [Struktur Database](#4-struktur-database)
5. [Model Domain](#5-model-domain)
6. [Autentikasi & Otorisasi](#6-autentikasi--otorisasi)
7. [Struktur Direktori](#7-struktur-direktori)
8. [Konvensi Kode](#8-konvensi-kode)
9. [Alur Kerja Aplikasi](#9-alur-kerja-aplikasi)
10. [Perintah Development](#10-perintah-development)
11. [Testing](#11-testing)
12. [Konfigurasi](#12-konfigurasi)
13. [Troubleshooting](#13-troubleshooting)

---

## 1. Gambaran Umum

SIPPEL adalah sistem informasi untuk mencatat dan memantau aktivitas pembelajaran di Sekolah Menengah Pertama (SMP) di Indonesia. Sistem ini memungkinkan:

- **Admin**: Mengelola data master (tahun ajaran, kelas, siswa, mata pelajaran, pengguna)
- **Guru**: Mencatat aktivitas pembelajaran harian (kehadiran, nilai, partisipasi siswa)
- **Siswa**: Melihat progres pembelajaran pribadi (read-only)

### Fitur Utama

| Fitur | Deskripsi |
|-------|-----------|
| Manajemen Tahun Ajaran | Satu tahun ajaran aktif pada satu waktu |
| Manajemen Kelas | Kelas dengan wali kelas (homeroom teacher) |
| Pencatatan Aktivitas | Rekam kehadiran, nilai, dan partisipasi per pertemuan |
| Laporan Otomatis | Generate laporan PDF via DomPDF |
| NIS Sementara | Siswa tanpa NIS resmi dapat menggunakan NIS sementara (prefix `9`) |

---

## 2. Tech Stack

### Backend

| Teknologi | Versi | Fungsi |
|-----------|-------|--------|
| PHP | 8.3.x | Runtime |
| Laravel | 12.x | Framework utama |
| FilamentPHP | 4.x | Admin panel |
| Livewire | 3.x | Komponen reaktif |
| Spatie Permission | 6.x | Role-based access control |
| DomPDF | - | Generate laporan PDF |

### Frontend

| Teknologi | Fungsi |
|-----------|--------|
| Flux UI | Komponen UI untuk Teacher/Student panel |
| Tailwind CSS | Styling |
| Alpine.js | Interaktivitas (via Livewire) |
| Vite | Asset bundling |

### Database

| Teknologi | Fungsi |
|-----------|--------|
| MySQL/MariaDB | Database utama |

### Development Tools

| Tool | Fungsi |
|------|--------|
| Laravel Pint | Code style fixer (PSR-12) |
| PHPStan | Static analysis (level 5) |
| Rector | Automated refactoring |
| Pest PHP | Testing framework |

---

## 3. Arsitektur Sistem

### Three-Panel Authentication System

SIPPEL menggunakan sistem autentikasi tiga panel dengan single login:

```
┌─────────────────────────────────────────────────────────────┐
│                    SINGLE LOGIN                              │
│                   /app/login                                 │
│                  (FilamentPHP)                               │
└─────────────────────┬───────────────────────────────────────┘
                      │
                      ▼
           ┌─────────────────────┐
           │   LoginResponse.php │
           │  (Role-based redirect)│
           └─────────┬───────────┘
                     │
        ┌────────────┼────────────┐
        ▼            ▼            ▼
┌───────────┐ ┌───────────┐ ┌───────────┐
│   ADMIN   │ │  TEACHER  │ │  STUDENT  │
│   /app    │ │  /teacher │ │  /student │
│ FilamentPHP│ │ Livewire  │ │ Livewire  │
│           │ │ + Flux UI │ │ + Flux UI │
└───────────┘ └───────────┘ └───────────┘
```

### Alur Redirect Login

```php
// app/Http/Responses/LoginResponse.php

if ($user->hasRole('admin')) {
    return redirect('/app');           // FilamentPHP Admin Panel
} elseif ($user->hasRole('teacher')) {
    return redirect('/teacher');       // Livewire Teacher Dashboard
} elseif ($user->hasRole('student')) {
    return redirect('/student');       // Livewire Student Dashboard
}
```

### Data Flow

```
┌─────────────────────────────────────────────────────────────┐
│                      GURU                                    │
│         Membuat AktivitasPembelajaran                       │
└─────────────────────┬───────────────────────────────────────┘
                      │
                      ▼
┌─────────────────────────────────────────────────────────────┐
│               DetailAktivitas                                │
│      (Per siswa: kehadiran, nilai, partisipasi)             │
└─────────────────────┬───────────────────────────────────────┘
                      │
                      ▼
┌─────────────────────────────────────────────────────────────┐
│                     SISWA                                    │
│    Auto-calculate: % kehadiran, rata-rata nilai             │
└─────────────────────┬───────────────────────────────────────┘
                      │
                      ▼
┌─────────────────────────────────────────────────────────────┐
│                    LAPORAN                                   │
│              Generate PDF via DomPDF                         │
└─────────────────────────────────────────────────────────────┘
```

---

## 4. Struktur Database

### Entity Relationship Diagram

```
┌─────────────────┐       ┌─────────────────┐
│   tahun_ajaran  │       │      users      │
├─────────────────┤       ├─────────────────┤
│ id              │       │ id              │
│ nama_tahun      │       │ name            │
│ semester        │       │ email           │
│ status (bool)   │       │ password        │
│ tanggal_mulai   │       │ jenis_kelamin   │
│ tanggal_selesai │       └────────┬────────┘
└────────┬────────┘                │
         │                         │
         │ 1:N                     │ 1:1
         ▼                         ▼
┌─────────────────┐       ┌─────────────────┐
│      kelas      │       │      siswa      │
├─────────────────┤       ├─────────────────┤
│ id              │◄──────│ id              │
│ tahun_ajaran_id │  N:1  │ nis (unique)    │
│ tingkat_kelas   │       │ user_id         │
│ grup_kelas      │       │ kelas_id        │
│ wali_kelas_id   │       └────────┬────────┘
└────────┬────────┘                │
         │                         │
         │ 1:N                     │ 1:N
         ▼                         ▼
┌─────────────────┐       ┌─────────────────┐
│ mata_pelajaran  │       │detail_aktivitas │
├─────────────────┤       ├─────────────────┤
│ id              │       │ id              │
│ kode            │       │ aktivitas_id    │
│ nama            │       │ siswa_id        │
│ deskripsi       │       │ kehadiran       │
└────────┬────────┘       │ nilai           │
         │                │ partisipasi     │
         │ N:1            │ catatan         │
         ▼                └─────────────────┘
┌─────────────────────────┐        ▲
│ aktivitas_pembelajaran  │        │
├─────────────────────────┤   1:N  │
│ id                      │────────┘
│ kelas_id                │
│ mata_pelajaran_id       │
│ guru_id                 │
│ tanggal                 │
│ jam_mulai               │
│ jam_selesai             │
│ topik                   │
│ deskripsi               │
│ metode_pembelajaran     │
└─────────────────────────┘
```

### Tabel Utama

#### `tahun_ajaran`
| Kolom | Tipe | Deskripsi |
|-------|------|-----------|
| id | bigint | Primary key |
| nama_tahun | string | Contoh: "2025/2026" |
| semester | enum | 'Ganjil' atau 'Genap' |
| status | boolean | Hanya satu yang aktif |
| tanggal_mulai | date | Awal tahun ajaran |
| tanggal_selesai | date | Akhir tahun ajaran |

#### `kelas`
| Kolom | Tipe | Deskripsi |
|-------|------|-----------|
| id | bigint | Primary key |
| tahun_ajaran_id | bigint | Foreign key |
| tingkat_kelas | enum | '7', '8', '9' |
| grup_kelas | string | 'A', 'B', 'C', dll |
| wali_kelas_id | bigint | FK ke users (teacher) |

#### `siswa`
| Kolom | Tipe | Deskripsi |
|-------|------|-----------|
| id | bigint | Primary key |
| nis | string(10) | Unique, prefix '9' = sementara |
| user_id | bigint | Foreign key ke users |
| kelas_id | bigint | Foreign key ke kelas |

#### `aktivitas_pembelajaran`
| Kolom | Tipe | Deskripsi |
|-------|------|-----------|
| id | bigint | Primary key |
| kelas_id | bigint | Foreign key |
| mata_pelajaran_id | bigint | Foreign key |
| guru_id | bigint | FK ke users (teacher) |
| tanggal | date | Tanggal aktivitas |
| jam_mulai | time | Waktu mulai |
| jam_selesai | time | Waktu selesai |
| topik | string | Topik pembelajaran |
| deskripsi | text | Deskripsi aktivitas |
| metode_pembelajaran | string | Ceramah, Diskusi, dll |

#### `detail_aktivitas`
| Kolom | Tipe | Deskripsi |
|-------|------|-----------|
| id | bigint | Primary key |
| aktivitas_pembelajaran_id | bigint | Foreign key |
| siswa_id | bigint | Foreign key |
| kehadiran | enum | 'Hadir', 'Izin', 'Sakit', 'Alpha' |
| nilai | integer | 0-100 (nullable) |
| partisipasi | enum | 'Sangat Aktif', 'Aktif', 'Cukup', 'Kurang' |
| catatan | text | Catatan per siswa (nullable) |

---

## 5. Model Domain

### Lokasi Model
```
app/Models/
├── AktivitasPembelajaran.php
├── DetailAktivitas.php
├── Kelas.php
├── Laporan.php
├── MataPelajaran.php
├── Siswa.php
├── TahunAjaran.php
└── User.php
```

### Model: TahunAjaran

```php
// app/Models/TahunAjaran.php

final class TahunAjaran extends Model
{
    protected $table = 'tahun_ajaran';
    
    // Relasi
    public function kelas(): HasMany;
    
    // Observer: TahunAjaranObserver
    // - Memastikan hanya satu tahun ajaran yang aktif
}
```

### Model: Kelas

```php
// app/Models/Kelas.php

final class Kelas extends Model
{
    protected $table = 'kelas';
    
    // Relasi
    public function tahunAjaran(): BelongsTo;
    public function waliKelas(): BelongsTo;  // User dengan role teacher
    public function siswa(): HasMany;
    public function aktivitasPembelajaran(): HasMany;
    
    // Accessor
    public function getNamaLengkapAttribute(): string
    {
        return "{$this->tingkat_kelas}{$this->grup_kelas}";
    }
}
```

### Model: Siswa

```php
// app/Models/Siswa.php

final class Siswa extends Model
{
    protected $table = 'siswa';
    
    // Relasi
    public function user(): BelongsTo;
    public function kelas(): BelongsTo;
    public function detailAktivitas(): HasMany;
    
    // Observer: SiswaObserver
    // - Validasi NIS (10 digit)
    // - Deteksi NIS sementara (prefix '9')
    
    // Helper Methods
    public function isTemporaryNis(): bool
    {
        return str_starts_with($this->nis, '9');
    }
}
```

### Model: AktivitasPembelajaran

```php
// app/Models/AktivitasPembelajaran.php

final class AktivitasPembelajaran extends Model
{
    protected $table = 'aktivitas_pembelajaran';
    
    // Relasi
    public function kelas(): BelongsTo;
    public function mataPelajaran(): BelongsTo;
    public function guru(): BelongsTo;  // User dengan role teacher
    public function detailAktivitas(): HasMany;
}
```

### Model: DetailAktivitas

```php
// app/Models/DetailAktivitas.php

final class DetailAktivitas extends Model
{
    protected $table = 'detail_aktivitas';
    
    // Relasi
    public function aktivitasPembelajaran(): BelongsTo;
    public function siswa(): BelongsTo;
    
    // Enum Values
    const KEHADIRAN = ['Hadir', 'Izin', 'Sakit', 'Alpha'];
    const PARTISIPASI = ['Sangat Aktif', 'Aktif', 'Cukup', 'Kurang'];
}
```

---

## 6. Autentikasi & Otorisasi

### Roles

| Role | Akses | Panel |
|------|-------|-------|
| `admin` | Full CRUD semua data | `/app` (FilamentPHP) |
| `teacher` | CRUD aktivitas pembelajaran | `/teacher` (Livewire) |
| `student` | Read-only data pribadi | `/student` (Livewire) |

### Middleware

```php
// routes/web.php

// Teacher Routes
Route::middleware(['auth', 'role:teacher'])->prefix('teacher')->group(function () {
    Route::get('/', TeacherDashboard::class)->name('teacher.dashboard');
    // ...
});

// Student Routes
Route::middleware(['auth', 'role:student'])->prefix('student')->group(function () {
    Route::get('/', StudentDashboard::class)->name('student.dashboard');
    // ...
});
```

### Filament Resource Access Control

```php
// Contoh: app/Filament/Resources/Users/UserResource.php

public static function canAccess(): bool
{
    return Auth::user()->hasRole('admin');
}
```

### Password Handling

Password di-hash otomatis oleh model `User`:

```php
// app/Models/User.php

protected function casts(): array
{
    return [
        'password' => 'hashed',  // Auto-hash saat save
    ];
}
```

> ⚠️ **Penting**: Jangan gunakan `Hash::make()` di form karena akan menyebabkan double-hashing.

---

## 7. Struktur Direktori

### Struktur Utama

```
app/
├── Console/Commands/           # Artisan commands
├── Filament/
│   ├── Pages/
│   │   └── Auth/
│   │       └── Login.php       # Custom login page
│   └── Resources/
│       ├── Kelas/
│       │   ├── KelasResource.php
│       │   ├── Pages/
│       │   ├── Schemas/
│       │   └── Tables/
│       ├── Siswas/
│       ├── TahunAjarans/
│       ├── MataPelajarans/
│       └── Users/
├── Http/
│   ├── Controllers/
│   ├── Middleware/
│   │   └── RedirectBasedOnRole.php
│   └── Responses/
│       └── LoginResponse.php    # Role-based redirect
├── Livewire/
│   ├── Student/                 # Student panel components
│   └── Teacher/                 # Teacher panel components
│       └── AktivitasPembelajaran/
│           ├── CreateAktivitas.php
│           ├── EditAktivitas.php
│           └── ListAktivitas.php
├── Models/                      # Eloquent models
├── Observers/                   # Model observers
├── Policies/                    # Authorization policies
└── Providers/
    └── AppServiceProvider.php   # Service registration
```

### Struktur Filament Resource

Setiap resource Filament memiliki struktur terpisah:

```
app/Filament/Resources/{Resource}/
├── {Resource}Resource.php      # Main resource class
├── Pages/
│   ├── Create{Resource}.php    # Create page
│   ├── Edit{Resource}.php      # Edit page
│   └── List{Resource}s.php     # List/index page
├── Schemas/
│   └── {Resource}Form.php      # Form schema definition
└── Tables/
    └── {Resource}sTable.php    # Table definition
```

### Views

```
resources/views/
├── components/                  # Blade components
├── layouts/
│   ├── teacher.blade.php        # Teacher panel layout (Flux UI)
│   └── student.blade.php        # Student panel layout (Flux UI)
├── livewire/
│   ├── teacher/                 # Teacher Livewire views
│   └── student/                 # Student Livewire views
└── filament/                    # Filament customizations
```

---

## 8. Konvensi Kode

### PHP Style Guide

Mengikuti PSR-12 dengan Laravel Pint:

```php
<?php

declare(strict_types=1);  // WAJIB di setiap file

namespace App\Models;

final class Siswa extends Model  // 'final' kecuali untuk inheritance
{
    protected $table = 'siswa';  // Explicit table name
    
    // Method names: camelCase
    public function getFullName(): string
    {
        return $this->user->name;
    }
}
```

### Penamaan

| Jenis | Konvensi | Contoh |
|-------|----------|--------|
| Model | PascalCase (Indonesia) | `TahunAjaran`, `MataPelajaran` |
| Table | snake_case | `tahun_ajaran`, `mata_pelajaran` |
| Method | camelCase (English) | `getActiveYear()`, `calculateGrade()` |
| Variable | camelCase | `$tahunAjaran`, `$siswaList` |

### Filament Form Fields

```php
// Contoh field dengan dehydration untuk disabled state
TextInput::make('nis')
    ->required()
    ->disabled(fn($get) => $get('use_temporary'))
    ->dehydrated();  // PENTING: ensure value is submitted
```

### Livewire Components

```php
<?php

declare(strict_types=1);

namespace App\Livewire\Teacher;

use Livewire\Component;
use Livewire\Attributes\Layout;

#[Layout('layouts.teacher')]  // Layout attribute
final class Dashboard extends Component
{
    public function render()
    {
        return view('livewire.teacher.dashboard');
    }
}
```

---

## 9. Alur Kerja Aplikasi

### Alur Pencatatan Aktivitas Pembelajaran

```
┌─────────────────────────────────────────────────────────────┐
│ 1. Guru Login → Redirect ke /teacher                        │
└─────────────────────┬───────────────────────────────────────┘
                      │
                      ▼
┌─────────────────────────────────────────────────────────────┐
│ 2. Guru pilih kelas dan mata pelajaran                      │
└─────────────────────┬───────────────────────────────────────┘
                      │
                      ▼
┌─────────────────────────────────────────────────────────────┐
│ 3. Guru buat aktivitas pembelajaran baru:                   │
│    - Tanggal, jam mulai/selesai                             │
│    - Topik dan deskripsi                                    │
│    - Metode pembelajaran                                    │
└─────────────────────┬───────────────────────────────────────┘
                      │
                      ▼
┌─────────────────────────────────────────────────────────────┐
│ 4. Sistem auto-generate DetailAktivitas untuk setiap siswa  │
│    di kelas tersebut                                        │
└─────────────────────┬───────────────────────────────────────┘
                      │
                      ▼
┌─────────────────────────────────────────────────────────────┐
│ 5. Guru isi kehadiran, nilai, partisipasi per siswa         │
└─────────────────────┬───────────────────────────────────────┘
                      │
                      ▼
┌─────────────────────────────────────────────────────────────┐
│ 6. Data tersimpan → Siswa dapat melihat di panel mereka     │
└─────────────────────────────────────────────────────────────┘
```

### Alur NIS Sementara

```
┌─────────────────────────────────────────────────────────────┐
│ Admin membuat siswa baru                                     │
└─────────────────────┬───────────────────────────────────────┘
                      │
            ┌─────────┴─────────┐
            ▼                   ▼
    ┌───────────────┐   ┌───────────────┐
    │ NIS tersedia  │   │ NIS belum ada │
    └───────┬───────┘   └───────┬───────┘
            │                   │
            │           ┌───────▼───────┐
            │           │ Centang opsi  │
            │           │ "NIS Sementara"│
            │           └───────┬───────┘
            │                   │
            │           ┌───────▼───────┐
            │           │ Generate NIS: │
            │           │ 9XXXXXXXXX    │
            │           └───────┬───────┘
            │                   │
            └─────────┬─────────┘
                      │
                      ▼
┌─────────────────────────────────────────────────────────────┐
│ Siswa terdaftar dengan NIS                                  │
│ (NIS sementara dapat diubah nanti)                          │
└─────────────────────────────────────────────────────────────┘
```

---

## 10. Perintah Development

### Perintah Utama

```bash
# Start development server (concurrent: server, queue, logs, vite)
composer dev

# Run full code review (pint → rector → phpstan → pest)
composer review

# Individual tools
composer pint          # Fix code style
composer phpstan       # Static analysis
composer rector        # Automated refactoring
composer pest          # Run tests
composer pest-parallel # Run tests in parallel
```

### Artisan Commands

```bash
# Clear all caches
php artisan optimize:clear

# Build optimized cache
php artisan optimize

# Database
php artisan migrate              # Run migrations
php artisan migrate:fresh --seed # Fresh install with seeders
php artisan db:seed              # Run seeders only

# Filament
php artisan filament:optimize    # Cache Filament components
```

### Database Seeding

```bash
# Fresh database with all seeders
php artisan migrate:fresh --seed

# Output admin credentials:
# ╔══════════════════════════════════════════════════════════╗
# ║           ADMIN CREDENTIALS (SAVE THIS!)                 ║
# ╠══════════════════════════════════════════════════════════╣
# ║  Email    : admin@sippel.sch.id                          ║
# ║  Password : [RANDOM_PASSWORD]                            ║
# ╚══════════════════════════════════════════════════════════╝
```

> ⚠️ Password admin di-generate random setiap kali seeding. Simpan dengan aman!

---

## 11. Testing

### Framework

Menggunakan **Pest PHP v4** dengan Filament testing helpers.

### Struktur Test

```
tests/
├── Pest.php                     # Pest configuration
├── TestCase.php                 # Base test case
├── Browser/                     # Browser tests (Playwright)
├── Feature/
│   └── Filament/
│       ├── Pages/
│       │   └── Auth/
│       │       └── LoginTest.php
│       └── Resources/
│           └── UserResourceTest.php
└── Unit/                        # Unit tests
```

### Menjalankan Tests

```bash
# Run all tests
composer pest

# Run specific test file
./vendor/bin/pest tests/Feature/Filament/Resources/UserResourceTest.php

# Run with coverage
./vendor/bin/pest --coverage

# Run in parallel (may have race conditions)
composer pest-parallel
```

### Contoh Test (Filament Resource)

```php
<?php

declare(strict_types=1);

use App\Filament\Resources\Users\UserResource;
use App\Filament\Resources\Users\Pages\CreateUser;
use App\Models\User;

it('can create a user', function (): void {
    /** @var \Tests\TestCase $this */
    $admin = User::factory()->create();
    $admin->assignRole('admin');

    $this->actingAs($admin);

    Livewire::test(CreateUser::class)
        ->set('data.name', 'Test User')
        ->set('data.email', 'test@example.com')
        ->set('data.password', 'password123')
        ->set('data.roles', ['teacher'])
        ->call('create')
        ->assertHasNoErrors();

    $this->assertDatabaseHas('users', [
        'email' => 'test@example.com',
    ]);
});
```

---

## 12. Konfigurasi

### Environment Variables

```env
# Application
APP_NAME=SIPPEL
APP_ENV=local
APP_DEBUG=true
APP_URL=http://sippel.test

# Database
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=sippel
DB_USERNAME=root
DB_PASSWORD=

# Cache & Session (Rekomendasi: file untuk development)
CACHE_STORE=file
SESSION_DRIVER=file

# Queue
QUEUE_CONNECTION=database

# Filament
FILAMENT_PATH=app
```

### Performance Tips

1. **Cache Driver**: Gunakan `file` untuk development, `redis` untuk production
2. **Session Driver**: Gunakan `file` untuk development, `redis` untuk production
3. **Optimize Autoload**: 
   ```bash
   composer dump-autoload --optimize
   ```
4. **Cache Config**:
   ```bash
   php artisan optimize
   ```

---

## 13. Troubleshooting

### Error: "Undefined array key" saat Create

**Penyebab**: Field yang `disabled()` tidak ter-dehydrate.

**Solusi**: Tambahkan `->dehydrated()` pada field:
```php
TextInput::make('nis')
    ->disabled(fn($get) => $get('use_temporary'))
    ->dehydrated();  // Tambahkan ini
```

### Error: View Cache / Component Not Found

**Penyebab**: Cache view corrupt.

**Solusi**:
```bash
php artisan view:clear
php artisan optimize:clear
```

### Error: Password Tidak Valid Setelah Edit User

**Penyebab**: Double-hashing (form hash + model cast hash).

**Solusi**: Jangan gunakan `Hash::make()` di form jika model sudah memiliki cast `'password' => 'hashed'`.

### Error: Global Search Tidak Bekerja

**Penyebab**: `recordTitleAttribute` menggunakan computed attribute (bukan kolom database).

**Solusi**: Override method di Resource:
```php
public static function getGloballySearchableAttributes(): array
{
    return ['kolom_database_1', 'kolom_database_2'];
}

public static function getGlobalSearchResultTitle(Model $record): string
{
    return $record->computed_attribute;
}
```

### Error: Admin Panel Lambat

**Penyebab**: Cache/Session menggunakan database driver.

**Solusi**: Ubah ke file driver di `.env`:
```env
CACHE_STORE=file
SESSION_DRIVER=file
```

---

## Referensi

- [Laravel Documentation](https://laravel.com/docs)
- [FilamentPHP Documentation](https://filamentphp.com/docs)
- [Livewire Documentation](https://livewire.laravel.com/docs)
- [Flux UI Documentation](https://fluxui.dev/docs)
- [Spatie Permission](https://spatie.be/docs/laravel-permission)
- [Pest PHP](https://pestphp.com/docs)

---

*Dokumentasi ini dibuat untuk tim pengembang SIPPEL. Untuk pertanyaan, hubungi tim teknis.*
