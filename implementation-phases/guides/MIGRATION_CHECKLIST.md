# 🔄 Migration Checklist: FilamentPHP → Livewire + FluxUI

**Purpose:** Track the complete migration of teacher and student roles from FilamentPHP to Livewire + FluxUI, while keeping admin on FilamentPHP.

**Architecture:** Hybrid UI - Admin (FilamentPHP desktop) + Teacher/Student (FluxUI mobile-first)

---

## 📋 Pre-Migration Audit (Before Phase 3)

### Environment & Codebase Status
- [ ] **Phase 1 completed**: Database, models, authentication working
- [ ] **Phase 2 completed**: All FilamentPHP admin resources functional
  - [ ] TahunAjaran Resource (Academic Years)
  - [ ] Kelas Resource (Classes)
  - [ ] MataPelajaran Resource (Subjects)
  - [ ] Siswa Resource (Students)
  - [ ] Users Resource (All users)

### Existing FilamentPHP Artifacts Documentation
- [ ] **List all resources** in `app/Filament/Resources/`:
  ```bash
  ls -la app/Filament/Resources/
  # Document findings: ___________________
  ```

- [ ] **List all pages** in `app/Filament/Pages/`:
  ```bash
  ls -la app/Filament/Pages/
  # Document findings: ___________________
  ```

- [ ] **Check for teacher/student-specific resources**:
  - [ ] No TeacherResource.php exists
  - [ ] No StudentResource.php exists (Siswa is for admin management)
  - [ ] No teacher/student dashboard pages

### Backup & Rollback Preparation
- [ ] **Git commit** all Phase 1 & 2 work:
  ```bash
  git add .
  git commit -m "chore: complete Phase 1 & 2 - ready for Flux migration"
  git tag pre-flux-migration
  ```

- [ ] **Database backup** created:
  ```bash
  mysqldump -u root sippel_db > backup_pre_migration_$(date +%Y%m%d).sql
  # Backup location: ___________________
  ```

- [ ] **Rollback plan documented**:
  - [ ] Steps to restore database from backup
  - [ ] Git commands to revert to `pre-flux-migration` tag
  - [ ] Identified critical breaking points

---

## 🚀 Migration Phase (Phase 3 & 4)

### Authentication & Routing (Phase 3.0 - 3.1)

#### Middleware
- [ ] **RedirectBasedOnRole middleware** created:
  ```bash
  php artisan make:middleware RedirectBasedOnRole
  ```
- [ ] Middleware logic implemented (role-based redirect)
- [ ] Middleware registered in `bootstrap/app.php`
- [ ] Middleware tested with all three roles

#### Custom Login Page
- [ ] **Custom Login page** created: `app/Filament/Pages/Auth/Login.php`
- [ ] `getRedirectUrl()` method returns role-based routes
- [ ] AdminPanelProvider updated to use custom login
- [ ] Login tested: Admin → `/app`, Teacher → `/teacher`, Student → `/student`

#### User Model Update
- [ ] **`canAccessPanel()` updated** to admin-only:
  ```php
  public function canAccessPanel(Panel $panel): bool
  {
      return $this->hasRole('admin');
  }
  ```
- [ ] Tested: Teachers/students cannot access `/app` after login
- [ ] Tested: Direct URL access to `/app` redirects non-admins

#### Route Definitions
- [ ] **Teacher routes** defined in `routes/web.php`:
  - [ ] `/teacher` - Dashboard
  - [ ] `/teacher/aktivitas` - Activity list
  - [ ] `/teacher/aktivitas/create` - Create activity
  - [ ] `/teacher/aktivitas/{id}/edit` - Edit activity
  - [ ] `/teacher/aktivitas/{id}` - View activity
  - [ ] `/teacher/laporan` - Reports

- [ ] **Student routes** defined in `routes/web.php`:
  - [ ] `/student` - Dashboard
  - [ ] `/student/kehadiran` - Attendance history
  - [ ] `/student/nilai` - Grade history
  - [ ] `/student/profil` - Profile (optional)

- [ ] **Route middleware** tested:
  - [ ] Teacher can access `/teacher` routes only
  - [ ] Student can access `/student` routes only
  - [ ] Cross-role access returns 403

### FluxUI Installation (Phase 3.2)

#### Package Installation
- [ ] **Flux UI Free package** installed:
  ```bash
  composer require livewire/flux
  ```
- [ ] `composer.json` shows `livewire/flux` dependency
- [ ] Dependency check passed: `composer show --tree | grep flux`

#### Configuration & Verification
- [ ] **Flux installation command** executed (if exists):
  ```bash
  php artisan flux:install
  ```
- [ ] Flux components available in `vendor/livewire/flux/resources/views/components/`
- [ ] Test route created: `/test-flux`
- [ ] Test view with `<flux:button>` renders correctly

#### Asset Compilation
- [ ] **Frontend assets rebuilt**:
  ```bash
  npm run build
  ```
- [ ] Flux styles loading in browser DevTools
- [ ] No console errors related to Flux

#### Cache Clearing
- [ ] **Application caches cleared**:
  ```bash
  php artisan config:clear
  php artisan route:clear
  php artisan view:clear
  php artisan optimize:clear
  ```

### Layout & Navigation (Phase 3.2)

#### Teacher Layout
- [ ] **Teacher base layout** created: `resources/views/layouts/teacher.blade.php`
- [ ] `@fluxStyles` included in `<head>`
- [ ] `@fluxScripts` included before `</body>`
- [ ] `@vite` directives included for CSS/JS
- [ ] Flux header with mobile toggle implemented
- [ ] Collapsible sidebar for mobile created
- [ ] Navigation items added (Dashboard, Aktivitas, Laporan)
- [ ] User profile dropdown with logout working

#### Student Layout
- [ ] **Student base layout** created: `resources/views/layouts/student.blade.php`
- [ ] Same Flux directives as teacher layout
- [ ] Flux header with mobile toggle implemented
- [ ] Collapsible sidebar for mobile created
- [ ] Navigation items added (Dashboard, Kehadiran, Nilai)
- [ ] User profile dropdown with logout working

#### Mobile Responsiveness Testing
- [ ] Tested on desktop browser (Chrome, Firefox)
- [ ] Tested on actual iPhone/iOS device
- [ ] Tested on actual Android device
- [ ] Sidebar collapses correctly on mobile
- [ ] Touch targets verified (44px minimum)
- [ ] Navigation toggle works on mobile

### Livewire Components (Phase 3.3 - 3.7)

#### Teacher Components
- [ ] **Directory structure** created:
  ```
  app/Livewire/Teacher/
  ├── Dashboard.php
  └── AktivitasPembelajaran/
      ├── CreateAktivitas.php
      ├── ListAktivitas.php
      ├── EditAktivitas.php
      └── ViewAktivitas.php
  ```

- [ ] All teacher components generated via `php artisan make:livewire`
- [ ] Component namespaces verified: `App\Livewire\Teacher\*`
- [ ] Views generated in `resources/views/livewire/teacher/`

#### Student Components (Phase 4)
- [ ] **Directory structure** created:
  ```
  app/Livewire/Student/
  ├── Dashboard.php
  ├── RiwayatKehadiran.php
  ├── RiwayatNilai.php
  └── Profil.php
  ```

- [ ] All student components generated
- [ ] Component namespaces verified: `App\Livewire\Student\*`
- [ ] Views generated in `resources/views/livewire/student/`

### Security & Policies (Phase 4.5)

- [ ] **DetailAktivitas policy** created:
  ```bash
  php artisan make:policy DetailAktivitasPolicy --model=DetailAktivitas
  ```
- [ ] Policy methods implemented (view, create, update, delete)
- [ ] Students can only view their own data
- [ ] Unauthorized access returns 403

### Testing & Validation

#### Authentication Flow
- [ ] Admin login → redirects to `/app` (FilamentPHP)
- [ ] Teacher login → redirects to `/teacher` (FluxUI)
- [ ] Student login → redirects to `/student` (FluxUI)

#### Access Control
- [ ] Teacher cannot access `/app` routes
- [ ] Student cannot access `/app` routes
- [ ] Teacher cannot access `/student` routes
- [ ] Student cannot access `/teacher` routes
- [ ] Admin can still access `/app` normally

#### Migration-Specific Tests
- [ ] No FilamentPHP navigation visible to teachers
- [ ] No FilamentPHP navigation visible to students
- [ ] Teachers see only FluxUI interface
- [ ] Students see only FluxUI interface
- [ ] No broken links from old panel structure

---

## 🧹 Post-Migration Cleanup (Phase 8.1)

### FilamentPHP Artifact Cleanup

#### Resource Cleanup
- [ ] **Check for unused resources**:
  ```bash
  ls -la app/Filament/Resources/
  ```
- [ ] Verified: Only admin resources exist (TahunAjaran, Kelas, MataPelajaran, Siswa, Users)
- [ ] No TeacherResource.php found
- [ ] No StudentResource.php found (if it existed before)

#### Page Cleanup
- [ ] **Check for unused pages**:
  ```bash
  ls -la app/Filament/Pages/
  ```
- [ ] Expected findings:
  - [ ] `Auth/` directory (custom Login) ✅
  - [ ] `StudentReport.php` (Phase 5) ✅
  - [ ] `ClassReport.php` (Phase 5) ✅
  - [ ] No TeacherDashboard.php
  - [ ] No StudentDashboard.php

#### Import Cleanup
- [ ] **Search for Filament imports in Livewire**:
  ```bash
  grep -r "use Filament\\\\" app/Livewire/Teacher/
  grep -r "use Filament\\\\" app/Livewire/Student/
  ```
- [ ] Expected: No Filament imports found
- [ ] All Livewire components use only Livewire and Flux UI

#### AdminPanelProvider Review
- [ ] **Navigation groups** reviewed:
  ```php
  // Current groups:
  - 'Master Data' ✅ (Admin uses)
  - 'Manajemen' ✅ (Admin uses)
  - 'Pembelajaran' ⚠️ (Check if still needed)
  - 'Laporan' ✅ (Admin uses)
  ```
- [ ] Removed unused navigation groups
- [ ] Only admin-relevant groups remain

#### Middleware Cleanup
- [ ] **Check for obsolete middleware**:
  ```bash
  grep -r "TeacherPanelMiddleware" .
  grep -r "StudentPanelMiddleware" .
  ```
- [ ] Expected: No references found
- [ ] Verified `bootstrap/app.php` only has `RedirectBasedOnRole`

#### Panel Provider Cleanup
- [ ] **Verify no old panel providers**:
  ```bash
  grep -r "TeacherPanelProvider" .
  grep -r "StudentPanelProvider" .
  ```
- [ ] Expected: No references found
- [ ] `bootstrap/providers.php` only includes `AdminPanelProvider` (or `AppPanelProvider`)

### Dual-UI Architecture Verification

#### Admin Side (FilamentPHP)
- [ ] **No Flux components in admin**:
  ```bash
  grep -r "flux:" app/Filament/Resources/
  grep -r "flux:" app/Filament/Pages/ | grep -v "Auth"
  ```
- [ ] Expected: No Flux UI components (except possibly in Auth)
- [ ] Admin uses only FilamentPHP components

#### Teacher/Student Side (FluxUI)
- [ ] **No Filament imports in teacher/student**:
  ```bash
  grep -r "Filament\\\\" app/Livewire/Teacher/
  grep -r "Filament\\\\" app/Livewire/Student/
  ```
- [ ] Expected: No Filament imports
- [ ] Teacher/Student use only Livewire and Flux UI

### URL & Link Cleanup
- [ ] **Search for old URLs**:
  ```bash
  grep -r "/admin" resources/views/
  grep -r "/teacher-panel" resources/views/
  grep -r "/student-panel" resources/views/
  ```
- [ ] Expected: No references to old panel URLs
- [ ] All URLs updated to: `/app`, `/teacher`, `/student`

---

## ✅ Migration Complete Verification

### Final Checklist
- [ ] ✅ Admin accesses FilamentPHP at `/app`
- [ ] ✅ Teacher accesses FluxUI at `/teacher`
- [ ] ✅ Student accesses FluxUI at `/student`
- [ ] ✅ Single login URL (`/app/login`) for all roles
- [ ] ✅ No cross-contamination of UI libraries
- [ ] ✅ No obsolete FilamentPHP artifacts remain
- [ ] ✅ All migrations successfully completed
- [ ] ✅ All tests passing

### Documentation Updates
- [ ] Updated README.md with new architecture
- [ ] Updated deployment guide with dual-UI setup
- [ ] Updated user manual with role-specific instructions
- [ ] Migration notes documented for future reference

---

## 🔙 Rollback Procedure (If Needed)

**If migration fails or critical issues found:**

1. **Restore Git State:**
   ```bash
   git checkout pre-flux-migration
   git branch -D main  # Or current branch
   git checkout -b main  # Recreate from tag
   ```

2. **Restore Database:**
   ```bash
   mysql -u root sippel_db < backup_pre_migration_YYYYMMDD.sql
   ```

3. **Clear All Caches:**
   ```bash
   php artisan config:clear
   php artisan route:clear
   php artisan view:clear
   composer dump-autoload
   ```

4. **Revert Composer Dependencies:**
   ```bash
   composer install  # Will reinstall from composer.lock
   ```

5. **Document Issues:**
   - Create `MIGRATION_ISSUES.md`
   - Document what went wrong
   - Plan remediation steps

---

## 📊 Progress Tracking

```
Pre-Migration Audit:       [░░░░░░░░░░] 0%
Authentication & Routing:  [░░░░░░░░░░] 0%
FluxUI Installation:       [░░░░░░░░░░] 0%
Layout & Navigation:       [░░░░░░░░░░] 0%
Teacher Components:        [░░░░░░░░░░] 0%
Student Components:        [░░░░░░░░░░] 0%
Security & Policies:       [░░░░░░░░░░] 0%
Testing & Validation:      [░░░░░░░░░░] 0%
Post-Migration Cleanup:    [░░░░░░░░░░] 0%

Overall Migration: [░░░░░░░░░░] 0% Complete
```

**Last Updated:** _________________  
**Migration Status:** Not Started | In Progress | Completed | Rolled Back

---

**Note:** Check off each item as you complete it. This checklist ensures no steps are missed during the critical migration process.
