# SIPPEL - Copilot Instructions

## Project Overview

SIPPEL (Sistem Informasi Pencatatan Aktivitas Pembelajaran) is a school learning activity recording and monitoring system for Indonesian junior high schools. Built with **Laravel 12.x**, **FilamentPHP 4.x**, and **Livewire Flux UI**.

## Architecture

### Three-Panel Authentication System
- **Single login** at `/app/login` (FilamentPHP) → role-based redirect via `App\Http\Responses\LoginResponse`
- **Admin (`/app`)**: FilamentPHP admin panel - full CRUD for master data
- **Teacher (`/teacher`)**: Livewire + Flux UI - record daily learning activities
- **Student (`/student`)**: Livewire + Flux UI - view personal progress (read-only)

### Key Domain Models (Indonesian naming)
- `TahunAjaran` - Academic year (only one active at a time)
- `Kelas` - Class/classroom with homeroom teacher (`wali_kelas`)
- `Siswa` - Student (has `nis` unique identifier)
- `MataPelajaran` - Subject/course
- `AktivitasPembelajaran` - Learning activity record
- `DetailAktivitas` - Per-student attendance, grades, participation

### Data Flow
```
Teacher creates AktivitasPembelajaran → DetailAktivitas per student
↓
Siswa model auto-calculates: attendance %, average grades
↓
Laporan (reports) generated as PDF via dompdf
```

## Code Conventions

### PHP Style (enforced by Pint)
- **Always** use `declare(strict_types=1);` at file top
- Classes should be `final` unless designed for inheritance
- Use Indonesian model names but English method/variable names
- Explicit table names in models: `protected $table = 'siswa';`

### Filament Resources Structure
```
app/Filament/Resources/
├── Users/
│   ├── UserResource.php      # Main resource class
│   ├── Schemas/UserForm.php  # Form definition
│   ├── Tables/UsersTable.php # Table definition
│   └── Pages/                # CRUD pages
```
Separate form schemas and table definitions into dedicated classes.

### Livewire Components (Teacher/Student)
- Use `#[Layout('layouts.teacher')]` attribute for layout
- Views in `resources/views/livewire/{teacher,student}/`
- Routes defined in `routes/web.php` with role middleware

### Role-Based Access
```php
// Check roles using Spatie Permission
$user->hasRole('admin')
$user->hasRole('teacher')
$user->hasRole('student')

// In Filament resources
public static function canAccess(): bool
{
    return Auth::user()->hasRole('admin');
}
```

## Developer Commands

```bash
composer dev          # Starts server, queue, pail, vite concurrently
composer review       # Run pint → rector → phpstan → pest
composer pint         # Fix code style
composer phpstan      # Static analysis (level 5)
composer pest         # Run tests in parallel
php artisan test      # Run tests (clears config first)
```

## Key Files Reference

- [app/Http/Responses/LoginResponse.php](app/Http/Responses/LoginResponse.php) - Role-based redirect logic
- [app/Providers/AppServiceProvider.php](app/Providers/AppServiceProvider.php) - Model observers registration
- [database/seeders/RolePermissionSeeder.php](database/seeders/RolePermissionSeeder.php) - Available roles/permissions
- [routes/web.php](routes/web.php) - Teacher and Student route definitions
- [resources/views/layouts/teacher.blade.php](resources/views/layouts/teacher.blade.php) - Flux UI layout template

## Testing

Uses **PEST 4.x** with browser testing support:
- Unit tests in `tests/Unit/`
- Feature tests in `tests/Feature/`
- Browser tests in `tests/Browser/`

## Implementation Status

Refer to `implementation-phases/` for detailed phase documentation:
- Phases 1-2: ✅ Foundation & master data complete
- Phases 3-4: 🔄 Teacher/Student Flux UI migration in progress
- Phases 5-8: 🔵 Reporting, dashboards, testing pending
