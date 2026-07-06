# SIPPEL - Sistem Informasi Pencatatan Aktivitas Pembelajaran

[![Laravel](https://img.shields.io/badge/Laravel-12.x-FF2D20?style=flat-square&logo=laravel)](https://laravel.com)
[![Filament](https://img.shields.io/badge/FilamentPHP-4.x-F59E0B?style=flat-square)](https://filamentphp.com)
[![PHP](https://img.shields.io/badge/PHP-8.3+-777BB4?style=flat-square&logo=php)](https://php.net)
[![Livewire](https://img.shields.io/badge/Livewire-3.x-FB70A9?style=flat-square)](https://livewire.laravel.com)

**SIPPEL** adalah sistem informasi pencatatan dan pemantauan aktivitas pembelajaran untuk sekolah menengah pertama di Indonesia. Dibangun menggunakan Laravel 12.x, FilamentPHP 4.x, dan Livewire Flux UI untuk memberikan pengalaman yang responsif dan user-friendly.

> [!NOTE]  
> Membutuhkan **PHP 8.3** atau lebih tinggi.

## 📋 Daftar Isi

- [Gambaran Umum](#-gambaran-umum)
- [Fitur Utama](#-fitur-utama)
- [Teknologi yang Digunakan](#️-teknologi-yang-digunakan)
- [Instalasi](#-instalasi)
- [Arsitektur](#️-arsitektur)
- [Penggunaan](#-penggunaan)
- [Pengembangan](#-pengembangan)
- [Testing](#-testing)
- [Deployment](#-deployment)
- [Kontribusi](#-kontribusi)

## 🎯 Gambaran Umum

SIPPEL merupakan sistem yang memungkinkan:

- **Guru** untuk mencatat aktivitas pembelajaran harian, kehadiran, dan keaktifan siswa
- **Siswa** untuk melihat progress pembelajaran dan catatan aktivitas pribadi
- **Admin** untuk mengelola data master (pengguna, mata pelajaran, kelas, tahun ajaran)

Sistem ini mendukung proses pembelajaran dengan menyediakan tracking yang komprehensif terhadap aktivitas belajar mengajar di tingkat SMP.

## ✨ Fitur Utama

### 🔐 Sistem Autentikasi Multi-Panel
- **Single login** di `/app/login` dengan redirect otomatis berdasarkan role
- **Panel Admin** (`/app`): FilamentPHP admin panel untuk CRUD data master
- **Panel Guru** (`/teacher`): Interface Livewire + Flux UI untuk mencatat aktivitas pembelajaran
- **Panel Siswa** (`/student`): Interface read-only untuk melihat progress pembelajaran

### 📚 Manajemen Pembelajaran
- **Tahun Ajaran**: Sistem periode akademik dengan hanya satu tahun aktif
- **Kelas**: Manajemen kelas dengan wali kelas
- **Mata Pelajaran**: Pengelolaan subjek pembelajaran
- **Aktivitas Pembelajaran**: Pencatatan detail kegiatan belajar mengajar
- **Kehadiran & Keaktifan**: Pencatatan status kehadiran dan kategori keaktifan per siswa

### 📊 Pelaporan
- **Laporan PDF**: Generate laporan menggunakan DomPDF
- **Dashboard Analytics**: Statistik pembelajaran dan progress siswa
- **Monitoring Real-time**: Tracking aktivitas pembelajaran harian

### 🌍 Lokalisasi Indonesia
- Interface lengkap dalam Bahasa Indonesia
- Pesan validasi dan error dalam Bahasa Indonesia
- Format tanggal dan waktu sesuai standar Indonesia

## 🛠️ Teknologi yang Digunakan

### Backend
- **Laravel 12.x** - PHP Framework
- **PHP 8.3+** - Server-side language
- **MySQL/PostgreSQL** - Database engine
- **FilamentPHP 4.x** - Admin panel dengan SPA mode

### Frontend
- **Livewire 3.x** - Full-stack framework
- **Flux UI** - Component library untuk Teacher/Student panel
- **Alpine.js** - Minimal framework untuk interaktivitas
- **Tailwind CSS** - Utility-first CSS framework

### Development Tools
- **PEST 4.x** - Testing framework dengan browser testing
- **PHPStan** - Static analysis (level 5)
- **Laravel Pint** - Code style fixer
- **Rector** - Automated refactoring

### Authentication & Authorization
- **Spatie Permission** - Role dan permission management
- **Laravel Sanctum** - API authentication

### Deployment
- **Docker** - Containerization dengan multi-stage build
- **nginx** - Web server configuration
- **Supervisord** - Process management

## 📦 Instalasi

### Prasyarat
- PHP 8.3+
- Composer
- Node.js & NPM
- MySQL/PostgreSQL
- Git

### Quick Start

```bash
# Clone repository
git clone <repository-url> sippel
cd sippel

# Install dependencies
composer install
npm install

# Environment setup
cp .env.example .env
php artisan key:generate

# Database setup
php artisan migrate
php artisan db:seed

# Build assets
npm run build

# Start development server
composer dev
```

### Docker Development

```bash
# Build dan jalankan dengan Docker
docker build -t sippel .
docker run -d -p 80:80 --name sippel-app sippel

# Atau gunakan docker-compose (jika tersedia)
docker-compose up -d
```

## 🏗️ Arsitektur

### Model Domain Utama

```php
// Model utama dengan penamaan Indonesia
TahunAjaran     // Academic year (hanya satu yang aktif)
Kelas           // Classroom dengan homeroom teacher
Siswa           // Student dengan NIS identifier
MataPelajaran   // Subject/course
AktivitasPembelajaran  // Learning activity record
DetailAktivitas        // Kehadiran dan keaktifan per siswa
```

### Flow Data

```
Guru membuat AktivitasPembelajaran
    ↓
DetailAktivitas per siswa (kehadiran, keaktifan)
    ↓
Auto-kalkulasi di model Siswa (% kehadiran dan ringkasan keaktifan)
    ↓
Generate Laporan PDF via DomPDF
```

### Structure Folder Filament Resources

```
app/Filament/Resources/
├── Users/
│   ├── UserResource.php      # Resource utama
│   ├── Schemas/UserForm.php  # Form definition
│   ├── Tables/UsersTable.php # Table definition
│   └── Pages/                # CRUD pages
```

## 📱 Penggunaan

### Login dan Role

1. **Admin**: Akses ke `/app` untuk mengelola data master
2. **Guru**: Akses ke `/teacher` untuk mencatat aktivitas pembelajaran
3. **Siswa**: Akses ke `/student` untuk melihat progress pembelajaran

### Panel Guru - Mencatat Aktivitas

1. Buka halaman **Buat Aktivitas**
2. Pilih **Mata Pelajaran** dan **Kelas**
3. Isi detail aktivitas pembelajaran
4. Catat **Kehadiran** dan **Keaktifan** per siswa
5. **Simpan** data aktivitas

### Panel Admin - Kelola Data Master

1. Manajemen **Tahun Ajaran** (hanya satu aktif)
2. Setup **Mata Pelajaran** dan **Kelas**
3. Kelola **User** dengan role assignment
4. Monitor **Laporan** sistem

### Panel Siswa - Lihat Progress

1. Dashboard **Ringkasan Pembelajaran**
2. Detail **Aktivitas** yang diikuti
3. **Riwayat Kehadiran** dan keaktifan
4. **Ringkasan** aktivitas pembelajaran personal

## 🔧 Pengembangan

### Commands Utama

```bash
# Development workflow lengkap
composer dev          # Start server, queue, pail, vite concurrently

# Quality assurance
composer review       # Run pint → rector → phpstan → pest
composer pint         # Fix code style
composer phpstan      # Static analysis
composer pest         # Run tests in parallel

# Testing
php artisan test      # Run tests (clear config first)
```

### Konvensi Code

#### Style PHP (enforced by Pint)
```php
<?php

declare(strict_types=1);

namespace App\Models;

// Always use final unless designed for inheritance
final class Siswa extends Model
{
    // Explicit table names
    protected $table = 'siswa';
    
    // Indonesian model names, English methods
    public function getFullNameAttribute(): string
    {
        return "{$this->nama_depan} {$this->nama_belakang}";
    }
}
```

#### Livewire Components
```php
#[Layout('layouts.teacher')]
class CreateAktivitas extends Component
{
    // Component logic
}
```

#### Role-based Access
```php
// Check roles menggunakan Spatie Permission
$user->hasRole('admin');
$user->hasRole('teacher'); 
$user->hasRole('student');

// Dalam Filament resources
public static function canAccess(): bool
{
    return Auth::user()->hasRole('admin');
}
```

### File Referensi Penting

- [app/Http/Responses/LoginResponse.php](app/Http/Responses/LoginResponse.php) - Logic redirect berdasarkan role
- [app/Providers/AppServiceProvider.php](app/Providers/AppServiceProvider.php) - Registrasi model observers
- [routes/web.php](routes/web.php) - Definisi route Teacher dan Student
- [resources/views/layouts/teacher.blade.php](resources/views/layouts/teacher.blade.php) - Layout template Flux UI

## 🧪 Testing

Menggunakan **PEST 4.x** dengan dukungan browser testing:

```bash
# Run all tests
./vendor/bin/pest

# Run specific test suite
./vendor/bin/pest --testsuite=Feature
./vendor/bin/pest --testsuite=Unit

# Run with coverage
./vendor/bin/pest --coverage
```

### Test Structure
```
tests/
├── Unit/           # Unit tests untuk model dan helper
├── Feature/        # Feature tests untuk controller dan API
└── Browser/        # Browser tests dengan Laravel Dusk
```

## 🚀 Deployment

### Production Setup

```bash
# Optimize untuk production
php artisan optimize
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Migration dan seeding
php artisan migrate --force
php artisan db:seed --class=RolePermissionSeeder
```

### Docker Production

```dockerfile
# Multi-stage build sudah dikonfigurasi
# Vendor stage → Assets stage → Runtime stage
# Dengan nginx, PHP-FPM, dan supervisord
```

### Environment Variables

```env
# Database
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=sippel
DB_USERNAME=sippel
DB_PASSWORD=password

# Application
APP_NAME="SIPPEL"
APP_ENV=production
APP_KEY=base64:your-app-key
APP_DEBUG=false
APP_TIMEZONE=Asia/Jakarta

# Filament
FILAMENT_THEME=sippel
```

## 🤝 Kontribusi

1. Fork repository
2. Buat feature branch (`git checkout -b feature/AmazingFeature`)
3. Commit perubahan (`git commit -m 'Add some AmazingFeature'`)
4. Push ke branch (`git push origin feature/AmazingFeature`)
5. Buka Pull Request

### Guidelines Kontribusi

- Ikuti konvensi penamaan dan style code yang ada
- Tambahkan test untuk fitur baru
- Update dokumentasi jika diperlukan
- Gunakan Bahasa Indonesia untuk UI dan pesan error

## 📄 Lisensi

Project ini menggunakan lisensi [MIT License](LICENSE).

## 👥 Tim Pengembangan

- **Developer**: Tegar Ardiansyah
- **Thesis Advisor**: Dr. Lukman Rosyidi, M.T., M.M.
- **Thesis Examiner**: Dr. Suhendi, S.T., S.Kom., M.M.S.I.  
- **Institution**: Sekolah Tinggi Teknologi Terpadu Nurul Fikri (STT-NF)
- **Partner**: SMPIT Al-Itqon Tajurhalang Bogor

## 📞 Dukungan

Untuk pertanyaan teknis dan dukungan:
- **Email**: teggarar123@gmail.com
- **Documentation**: `docs/`
- **Issues**: GitHub Issues

---

Dibuat dengan ❤️ untuk pendidikan Indonesia
