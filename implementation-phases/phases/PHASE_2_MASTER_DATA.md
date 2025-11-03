# Phase 2: Master Data Management (Week 3-4)

**Objective:** Create Filament resources for all master data tables in the Admin panel.

**Estimated Time:** 30 hours

---

## Navigation Group Mappings

All resources in Phase 2 will be organized into navigation groups:

### Master Data Group (Admin Only)
- **TahunAjaran** (Academic Year) - Sort: 1
- **Kelas** (Class) - Sort: 2  
- **MataPelajaran** (Subject) - Sort: 3

### Manajemen Group (Admin Only)
- **User** - Sort: 1 (already implemented in Phase 1)
- **Siswa** (Student) - Sort: 2

### RBAC Implementation Pattern
Each resource must implement:
1. `shouldRegisterNavigation()`: Show navigation only to authorized roles
2. `canAccess()`: Prevent direct URL access by unauthorized users
3. `getNavigationGroup()`: Assign to appropriate navigation group
4. `getNavigationSort()`: Set display order within group

---

## Task 2.1: Academic year resource (Admin only)

- [x] **2.1.1** Generate resource: `php artisan make:filament-resource TahunAjaran --panel=app`

- [x] **2.1.2** Add RBAC (Admin only access):
  - Add `shouldRegisterNavigation()` method with `hasRole('admin')` check
  - Add `canAccess()` method with `hasRole('admin')` check
  - Set navigation group: `getNavigationGroup()` return 'Master Data'
  - Set navigation sort order: `getNavigationSort()` return 1

- [x] **2.1.3** Configure form schema:
  - TextInput: `nama_tahun` (required, unique constraint removed - allows same year with different semesters, max:50)
  - Select: `semester` (options: 'Ganjil', 'Genap')
  - DatePicker: `tanggal_mulai`, `tanggal_selesai` (required)
  - Toggle: `status` (default: false, only one can be active with validation)

- [x] **2.1.4** Configure table columns:
  - TextColumn: `nama_tahun`, `semester`, `tanggal_mulai`, `tanggal_selesai`
  - ToggleColumn: `status` (Active/Inactive with colors and validation)

- [x] **2.1.5** Add table filter: Filter by `status` and `semester`

- [x] **2.1.6** Implement logic: Only one academic year can have `status = true`
  - Model observer with validation
  - Form validation with live feedback
  - Toggle column validation

- [x] **2.1.7** Add bulk actions: Activate/Deactivate with validation

- [x] **2.1.8** Test CRUD operations and validation
  - ✅ Unique constraint on nama_tahun + semester combination
  - ✅ Cannot activate if another is active
  - ✅ Cannot delete active academic year
  - ✅ Cannot create active if another exists

- [x] **2.1.9** Test RBAC: Verify only admin can see and access resource

---

## Task 2.2: Class resource (Admin only)

- [x] **2.2.1** Generate resource: `php artisan make:filament-resource Kelas --panel=app`

- [x] **2.2.2** Add RBAC (Admin only access):
  - Add `shouldRegisterNavigation()` method with `hasRole('admin')` check
  - Add `canAccess()` method with `hasRole('admin')` check
  - Set navigation group: `getNavigationGroup()` return 'Master Data'
  - Set navigation sort order: `getNavigationSort()` return 2

- [x] **2.2.3** Configure form schema:
  - Select: `tingkat_kelas` (options: 7, 8, 9)
  - Select: `grup_kelas` (options: A-Z, use helper for array generation)
  - Select: `wali_kelas_id` (relationship, searchable, only teachers)
  - Select: `tahun_ajaran_id` (relationship, only active academic year by default)

- [x] **2.2.4** Configure table columns:
  - TextColumn: Combined display "7A", "8B" (accessor or custom column)
  - TextColumn: `waliKelas.nama` (relationship - using nama accessor)
  - TextColumn: `tahunAjaran.nama_tahun`

- [x] **2.2.5** Add table filters: Filter by `tingkat_kelas`, `tahun_ajaran_id`
  - ✅ Default filter shows only active academic year classes
  - ✅ Filter displays year + semester combination

- [x] **2.2.6** Add validation: Prevent duplicate `tingkat_kelas + grup_kelas` combination per academic year
  - ✅ Database unique constraint implemented

- [x] **2.2.7** Test CRUD and check homeroom teacher assignment

- [x] **2.2.8** Test RBAC: Verify only admin can see and access resource

---

## Task 2.3: Student resource (Admin only)

- [ ] **2.3.1** Generate resource: `php artisan make:filament-resource Siswa --panel=app`

- [ ] **2.3.2** Add RBAC (Admin only access):
  - Add `shouldRegisterNavigation()` method with `hasRole('admin')` check
  - Add `canAccess()` method with `hasRole('admin')` check
  - Set navigation group: `getNavigationGroup()` return 'Manajemen'
  - Set navigation sort order: `getNavigationSort()` return 2

- [ ] **2.3.3** Configure form schema (Wizard or Tabs):
  - **Step 1 - Student Data:**
    - TextInput: `nis` (required, unique, numeric, length: 10)
  - **Step 2 - User Account:**
    - TextInput: `nama` (required, max:100) - will map to user.name via accessor
    - TextInput: `email` (required, email, unique)
    - PasswordInput: `password` (required on create, min:8)
    - Select: `jenis_kelamin` (options: 'L' => 'Laki-laki', 'P' => 'Perempuan')
  - **Step 3 - Class Assignment:**
    - Select: `kelas_id` (relationship, searchable, show "7A - 2025/2026")

- [ ] **2.3.4** Implement create logic:
  - Create User first with role 'student'
  - Create Siswa record linked to User
  - Use database transaction for atomicity

- [ ] **2.3.5** Configure table columns:
  - TextColumn: `nis`, `user.nama`, `user.email` (using nama accessor)
  - TextColumn: `kelas` (combined display)
  - BadgeColumn: `user.jenis_kelamin`

- [ ] **2.3.6** Add table filters: Filter by `kelas_id`, `jenis_kelamin`

- [ ] **2.3.7** Add search: By NIS, name, email

- [ ] **2.3.8** Add bulk actions: Assign to class, Export to Excel

- [ ] **2.3.9** Test student creation and user account generation

- [ ] **2.3.10** Test RBAC: Verify only admin can see and access resource

---

## Task 2.4: Subject resource (Admin only)

- [ ] **2.4.1** Generate resource: `php artisan make:filament-resource MataPelajaran --panel=app`

- [ ] **2.4.2** Add RBAC (Admin only access):
  - Add `shouldRegisterNavigation()` method with `hasRole('admin')` check
  - Add `canAccess()` method with `hasRole('admin')` check
  - Set navigation group: `getNavigationGroup()` return 'Master Data'
  - Set navigation sort order: `getNavigationSort()` return 3

- [ ] **2.4.3** Configure form schema:
  - TextInput: `nama_mapel` (required, max:100)
  - Select: `kelas_id` (relationship, searchable, show "7A - 2025/2026")
  - Select: `guru_id` (relationship, searchable, only users with role 'teacher')

- [ ] **2.4.4** Configure table columns:
  - TextColumn: `nama_mapel`
  - TextColumn: `kelas` (combined display)
  - TextColumn: `guru.nama` (using nama accessor)

- [ ] **2.4.5** Add table filters: Filter by `kelas_id`

- [ ] **2.4.6** Group table by class: Use `->groupedBulkActions()` or custom grouping

- [ ] **2.4.7** Add validation: One teacher per subject-class combination

- [ ] **2.4.8** Test subject creation and teacher assignment

- [ ] **2.4.9** Test RBAC: Verify only admin can see and access resource

---

## Task 2.5: User management resource (Admin only)

- [ ] **2.5.1** ✅ **SKIP** - User resource already exists in `app/Filament/Resources/Users/UserResource.php`

- [ ] **2.5.2** ✅ **SKIP** - RBAC already implemented (shouldRegisterNavigation, canAccess)

- [ ] **2.5.3** Update form schema to use `nama` instead of `name`:
  - TextInput: `nama` (required) - will map to name field via accessor
  - TextInput: `email` (required, email, unique)
  - PasswordInput: `password` (required on create, min:8)
  - Select: `jenis_kelamin` (required)
  - Select: `role` (options: admin, teacher, student - assign via Spatie)

- [ ] **2.5.4** Update table columns:
  - TextColumn: `nama`, `email` (using nama accessor)
  - BadgeColumn: `roles.name` (via Spatie relationship)
  - BadgeColumn: `jenis_kelamin`
  - ToggleColumn: `is_active` (if implementing soft deactivation)

- [ ] **2.5.5** Verify table filters: Filter by role

- [ ] **2.5.6** Verify role assignment on create/update

- [ ] **2.5.7** Add bulk actions: Deactivate accounts (if needed)

- [ ] **2.5.8** Test user creation for all roles

- [ ] **2.5.9** Test RBAC: Verify only admin can see and access resource (already done)

---

## Task 2.6: Add Laporan (Report) table and model

**✅ COMPLETED IN PHASE 1:** This task has been moved back to Phase 1 (Tasks 1.3.8 and 1.4.7) and is now complete.

- [x] **2.6.1** ✅ Create `laporan` migration - **COMPLETED**
- [x] **2.6.2** ✅ Create `Laporan` model - **COMPLETED**
- [x] **2.6.3** ✅ Update `Siswa` model to add `hasMany('laporan')` relationship - **COMPLETED**
- [x] **2.6.4** ✅ Update `MataPelajaran` model to add `hasMany('laporan')` relationship - **COMPLETED**
- [x] **2.6.5** ✅ Update `TahunAjaran` model to add `hasMany('laporan')` relationship - **COMPLETED**
- [x] **2.6.6** ✅ Run migration - **COMPLETED**
- [x] **2.6.7** ✅ Test relationships - **COMPLETED**

---

## Task 2.7: Index verification and optimization

- [ ] **2.7.1** Run `SHOW INDEX FROM users;` - verify email unique index

- [ ] **2.7.2** Run `SHOW INDEX FROM tahun_ajaran;` - verify nama_tahun unique, status index

- [ ] **2.7.3** Run `SHOW INDEX FROM kelas;` - verify all FK indexes and composite

- [ ] **2.7.4** Run `SHOW INDEX FROM siswa;` - verify NIS unique, FK indexes

- [ ] **2.7.5** Run `SHOW INDEX FROM mata_pelajaran;` - verify FK indexes

- [ ] **2.7.6** Run `SHOW INDEX FROM laporan;` - verify all FK indexes and unique composite

- [ ] **2.7.7** Test query performance with Laravel Debugbar

- [ ] **2.7.8** Verify eager loading in Filament resources (check query count)

---

## ✅ Phase 2 Completion Checklist

- [x] TahunAjaran resource created and tested ✅
- [x] Kelas resource created and tested ✅
- [x] Laporan table and model created ✅ (completed in Phase 1)
- [ ] Siswa resource created with wizard/tabs
- [ ] MataPelajaran resource created and tested
- [ ] User management resource updated
- [ ] All validations implemented
- [ ] All table filters working
- [ ] Bulk actions functional
- [ ] Database indexes verified
- [ ] Query performance optimized

---

## 🎯 Success Criteria

Phase 2 is complete when:
1. ✅ Admin can create and manage academic years
2. ✅ Admin can create classes with homeroom teachers
3. ✅ Admin can register students (creates user account automatically)
4. ✅ Admin can create subject assignments
5. ✅ Admin can manage all user accounts
6. ✅ Laporan table and model exist for report caching ⚠️
7. ✅ All CRUD operations work correctly
8. ✅ All validations prevent invalid data
9. ✅ Database indexes are verified and optimized

---

## 📝 Notes

- Use Filament's Wizard or Tabs component for student registration
- Implement database transactions for student creation
- Use eager loading to prevent N+1 queries
- Verify relationships work in both directions
- Test with Laravel Debugbar to monitor queries

---

**Previous Phase:** [← Phase 1: Foundation](./PHASE_1_FOUNDATION.md)  
**Next Phase:** [Phase 3: Core Functionality →](./PHASE_3_CORE_FUNCTIONALITY.md)
