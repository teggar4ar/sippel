# Product Requirements Document: Sistem Informasi Pencatatan Aktivitas Pembelajaran
## As-Built Documentation (Version 2.0)

---

## Document information

**Version:** 2.0 (As-Built — Updated to Reflect Actual Implementation)  
**Date:** June 3, 2026  
**Project:** SIPPEL - Web-Based Classroom Learning Activity Recording and Monitoring System  
**Author:** Student Final Project  
**Status:** Completed Implementation  
**Note:** This document reflects the actual implemented codebase. All divergences from the original PRD v1.0 are documented herein.

---

## 1. Introduction

### 1.1 Project overview

SIPPEL (Sistem Informasi Pencatatan Aktivitas Pembelajaran) is a web-based information system designed to help junior high schools (SMP) monitor and manage classroom learning activities. This system replaces manual, paper-based attendance and grading processes with a digital solution. The system employs a hybrid architecture: a FilamentPHP admin panel for school operators on desktop, and a Flux UI (Livewire) interface for teachers and students on mobile devices.

### 1.2 Purpose of this document

This document serves as the as-built specification of the SIPPEL system, outlining:
- Implemented features and functionality
- User roles and permissions
- Database structure
- Technical implementation details
- Success criteria verification

### 1.3 Project scope

**What this project INCLUDES:**
- User authentication with 3 roles (Admin, Teacher, Student)
- Multi-factor authentication (MFA) for the admin panel
- Master data management (academic years, classes, subjects, students)
- Semester transition wizard (Ganti Semester) and grade promotion wizard (Kenaikan Kelas)
- Daily learning activity recording (attendance, keaktifan/participation)
- Interactive dashboards with ApexCharts visualizations (Teacher)
- Attendance heatmap calendar and streak tracking (Student)
- Context-aware academic year selection across all interfaces
- PDF and Excel export for reports
- Dashboard caching (5-minute TTL) for performance
- Responsive web interface: FilamentPHP (Admin) + Flux UI (Teacher/Student)

**What this project does NOT include:**
- Parent/guardian portal
- Mobile native apps
- Real-time push notifications/alerts
- Automated backup systems
- Integration with external systems
- Advanced analytics or AI features
- Manual numeric grade input (nilai is auto-computed from keaktifan)

---

## 2. Problem statement and solution

### 2.1 Problem

Junior high schools face challenges with:
1. Manual record-keeping in physical logbooks (time-consuming and error-prone)
2. Difficulty compiling progress reports from scattered data
3. Limited student access to their own learning progress
4. Risk of data loss with paper-based systems
5. Difficulty tracking student progress across academic years and semesters

### 2.2 Solution

SIPPEL provides:
- **Digital data entry** for attendance and keaktifan (participation) via mobile-friendly Flux UI
- **Centralized database** storing all learning activity data with proper indexing
- **Role-based access** for admins, teachers, and students with Spatie permissions
- **Automated calculations** for attendance percentages, streaks, keaktifan averages, and composite scores
- **Interactive dashboards** with ApexCharts for teachers and heatmap calendar for students
- **Context-aware academic year switching** across teacher and student interfaces
- **Reports** in PDF and Excel format, accessible by admin and wali kelas (homeroom teachers)
- **Academic year lifecycle management** with Ganti Semester and Kenaikan Kelas wizards
- **Student portal** with personal progress tracking, attendance heatmap, and motivational messages

### 2.3 Expected outcomes

The completed system demonstrates:
- Full-stack web development (Laravel + FilamentPHP + Livewire Flux UI)
- Database design and implementation (MySQL) with 12 migrations and 9 models
- User authentication and authorization (Spatie + MFA)
- Hybrid UI architecture (Filament admin + Flux mobile)
- CRUD operations and form validation with Alpine.js
- ApexCharts.js integration for interactive dashboards
- PDF and Excel report generation
- Academic year lifecycle automation (Ganti Semester, Kenaikan Kelas)

---

## 3. User roles and permissions

### 3.1 Admin

**Role name in code:** `admin`

**Responsibilities:**
- Manage master data (academic years, classes, subjects, teachers, students)
- Create and manage user accounts
- Execute semester transitions (Ganti Semester) and grade promotions (Kenaikan Kelas)
- View system-wide reports (Student Report, Class Report)
- Access admin dashboard with system statistics, recent activities, and activity charts

**Permissions:**
- Full CRUD access to all master data via Filament resources
- User management (create, edit, deactivate)
- Access all reports and dashboards
- Execute Ganti Semester and Kenaikan Kelas wizards
- Multi-factor authentication (MFA) enforced for panel access

### 3.2 Teacher (Guru)

**Role name in code:** `teacher`

**Responsibilities:**
- Record daily learning activities for assigned classes via mobile-friendly Flux UI
- Record student attendance (Hadir, Izin, Sakit, Alpa) and keaktifan (Pasif, Cukup, Aktif, Sangat Aktif)
- View and edit own activity records
- View interactive dashboard with attendance trends, keaktifan charts, and class participation breakdowns
- Generate student and class reports (PDF + Excel) — available only to wali kelas (homeroom teachers)
- Switch between academic years via Tahun Ajaran context selector

**Permissions:**
- Create/edit/delete learning activities for assigned subjects only (within active tahun ajaran)
- View student data in assigned classes
- View teacher dashboard with charts and statistics
- Access reports only if assigned as wali kelas (homeroom teacher)
- Edit own profile (email, password)

### 3.3 Student (Siswa)

**Role name in code:** `student`

**Responsibilities:**
- View personal learning progress via mobile-friendly Flux UI
- View attendance heatmap calendar (year-long visual)
- View attendance streak and motivational messages
- View per-subject keaktifan and attendance statistics
- Browse activity history with filters (search, subject, attendance status)
- Export personal activity history as PDF

**Permissions:**
- Read-only access to personal data
- View own attendance, keaktifan, and reports
- Export own activity history PDF
- Edit own profile (email, password)
- Switch between academic years via Tahun Ajaran context selector
- Cannot modify any academic data

---

## 4. Functional requirements

### 4.1 Authentication & user management

| ID | Requirement | Priority |
|----|-------------|----------|
| FR-001 | Users can log in with email and password | Must Have |
| FR-002 | System supports three user roles: Admin, Teacher, Student | Must Have |
| FR-003 | Passwords are hashed and securely stored (bcrypt) | Must Have |
| FR-004 | Users can view and edit their profile information (email, password) | Must Have |
| FR-005 | Admin can create, edit, and deactivate user accounts | Must Have |
| FR-005a | Admin panel supports multi-factor authentication (MFA) via Filament | Must Have |

### 4.2 Master data management

| ID | Requirement | Priority |
|----|-------------|----------|
| FR-006 | Admin can create and manage academic years (nama_tahun, semester, dates) | Must Have |
| FR-006a | Admin can execute Ganti Semester wizard to transition to a new semester (clones classes, students, subjects) | Must Have |
| FR-006b | Admin can execute Kenaikan Kelas wizard to promote students (naik/tinggal/lulus decisions) with automatic class creation | Must Have |
| FR-006c | Direct creation of new academic years is disabled after initial setup; transitions use Ganti Semester or Kenaikan Kelas | Must Have |
| FR-007 | Only one academic year can be active at a time | Must Have |
| FR-008 | Admin can create classes with grade level (7-9) and group (A-Z); group letter auto-assigned to next available | Must Have |
| FR-009 | Admin can assign one homeroom teacher (wali kelas) per class | Must Have |
| FR-010 | Admin can register students with unique NIS number (exactly 10 digits) | Must Have |
| FR-010a | Admin can generate temporary NIS (prefix "9") for students without an official NIS yet | Must Have |
| FR-011 | Admin can assign students to classes (filtered to active tahun ajaran) | Must Have |
| FR-012 | Admin can create subjects and assign teachers to subject-class combinations | Must Have |

### 4.3 Learning activity management

| ID | Requirement | Priority |
|----|-------------|----------|
| FR-013 | Teacher can create learning activity with date (default today), topic, and notes via cascading filter (tingkat → grup → mata pelajaran → auto-fill kelas) | Must Have |
| FR-013a | Learning activity creation is blocked if the current tahun ajaran context is inactive | Must Have |
| FR-014 | Teacher can record attendance for each student (Hadir, Izin, Sakit, Alpa) with bulk "Tandai Semua Hadir" action | Must Have |
| FR-015 | Teacher can assign keaktifan (participation) scores on a 1-4 scale: 1=Pasif, 2=Cukup, 3=Aktif, 4=Sangat Aktif. Only available when attendance is "Hadir" | Must Have |
| FR-016 | ~~Teacher can input numeric grades (0-100)~~ **Hidden/Deprecated.** Nilai is auto-computed from keaktifan: Pasif→60, Cukup→75, Aktif→85, Sangat Aktif→95. The `nilai` column remains in the database but is not exposed in the UI. TODO: Full removal from schema | Deprecated |
| FR-017 | Teacher can add notes/feedback for individual students (only when attendance is "Hadir") | Should Have |
| FR-018 | Teacher can view and edit previously created activities (only within active tahun ajaran context) | Must Have |
| FR-019 | System displays activities in chronological order grouped by date, with inline attendance/keaktifan statistics | Must Have |

### 4.4 Calculation & aggregation

| ID | Requirement | Priority |
|----|-------------|----------|
| FR-020 | System automatically calculates attendance percentage per student (Hadir/Total × 100) | Must Have |
| FR-020a | System calculates attendance streak (consecutive "Hadir" days from most recent activity) | Must Have |
| FR-020b | System calculates composite score per subject: (attendance_pct × 0.6) + (normalized_keaktifan × 0.4) | Must Have |
| FR-021 | System automatically calculates average grades per student per subject (auto-computed from keaktifan) | Must Have |
| FR-022 | System automatically calculates average keaktifan per student with label mapping (Pasif/Cukup/Aktif/Sangat Aktif) | Must Have |

### 4.5 Student access

| ID | Requirement | Priority |
|----|-------------|----------|
| FR-023 | Student can view personal attendance history with filters (subject, attendance status, search) and pagination | Must Have |
| FR-023a | Student can view attendance heatmap calendar showing daily status (Hadir/Absent/Incomplete/No Activity/Future/Blank) for the entire academic year | Must Have |
| FR-023b | Student can view attendance streak count (consecutive "Hadir" days) | Must Have |
| FR-023c | Student receives motivational messages based on attendance percentage and keaktifan average | Must Have |
| FR-024 | Student can view per-subject keaktifan labels and composite scores on dashboard | Must Have |
| FR-025 | Student can view teacher feedback/notes in activity history | Must Have |
| FR-026 | Student can see summary of attendance breakdown (Hadir/Izin/Sakit/Alpa counts) with date range filters | Must Have |
| FR-026a | Student can export personal activity history as PDF | Must Have |

### 4.6 Reporting

| ID | Requirement | Priority |
|----|-------------|----------|
| FR-027 | System can generate student progress report showing attendance, keaktifan, and per-activity timeline | Must Have |
| FR-027a | Student report generation is restricted to wali kelas (homeroom teacher) for the teacher interface; Admin can generate any student's report | Must Have |
| FR-028 | System can generate class report showing all students' performance (sorted by attendance or name) | Must Have |
| FR-029 | Reports can be exported to PDF format (student and class reports) | Must Have |
| FR-029a | Class reports can be exported to Excel format (XLSX) via Maatwebsite/Excel | Must Have |
| FR-030 | Reports include attendance summary, keaktifan averages, and per-subject breakdowns | Must Have |

### 4.7 Dashboard

| ID | Requirement | Priority |
|----|-------------|----------|
| FR-031 | Admin dashboard shows: 6 stat cards (Admin count, Teacher count, Student count, Total Classes, Total Subjects, Activities This Month) + Recent Activities table (10 entries) + 7-day activity line chart. 60s polling, 5-min cache | Must Have |
| FR-032 | Teacher dashboard shows: 4 stat cards (Kelas Diampu, Total Siswa, Aktivitas Minggu Ini, Rata-rata Kehadiran) + 3 interactive ApexCharts (Tren Kehadiran stacked column, Keaktifan per Topik stacked bar, Distribusi Keaktifan donut) + Partisipasi per Kelas table with composite scores + filter controls (kelas, mata pelajaran, rentang waktu: semester/bulan/minggu). 5-min cache | Must Have |
| FR-033 | Student dashboard shows: 4 attendance stat cards (Hadir/Izin/Sakit/Alpa) + Attendance heatmap calendar + Attendance streak + Motivational message + Per-subject list with composite scores + Quick date filter (semester/bulan/minggu). 5-min cache | Must Have |

### 4.8 Academic year context

| ID | Requirement | Priority |
|----|-------------|----------|
| FR-034 | Teacher and student interfaces include a Tahun Ajaran selector dropdown that switches the data context for all views | Must Have |
| FR-035 | Tahun Ajaran context is stored in session and persists across page navigation | Must Have |
| FR-036 | Changing the Tahun Ajaran context triggers a full page reload to refresh all data | Must Have |

---

## 5. Non-functional requirements

### 5.1 Essential requirements

| ID | Requirement | Priority |
|----|-------------|----------|
| NFR-001 | System uses Laravel 12.x framework for backend | Must Have |
| NFR-002 | System uses FilamentPHP 4.x for admin interface | Must Have |
| NFR-003 | System uses MySQL 8.0+ database | Must Have |
| NFR-004 | Passwords are hashed using bcrypt | Must Have |
| NFR-005 | Forms have CSRF protection | Must Have |
| NFR-006 | Interface is in Indonesian language | Must Have |
| NFR-007 | Teacher and student interfaces are responsive (desktop, tablet, mobile) via Flux UI | Must Have |
| NFR-008 | System validates all form inputs with Indonesian error messages | Must Have |
| NFR-008a | Admin interface is desktop-only (FilamentPHP) | Must Have |

### 5.2 Desirable requirements

| ID | Requirement | Priority |
|----|-------------|----------|
| NFR-009 | Page loads should complete within 3 seconds | Should Have |
| NFR-010 | Error messages should be clear and helpful (Indonesian) | Should Have |
| NFR-011 | System should work on Chrome, Firefox, and Edge browsers | Should Have |
| NFR-012 | Authentication based on session Laravel (without API token) | Must Have |
| NFR-013 | Not using Sanctum or Passport for simplicity | Must Have |
| NFR-014 | Query using eager loading for general relations (students, classes, subjects) | Should Have |
| NFR-015 | Use pagination for data tables with more than 10 records (Livewire pagination) | Should Have |
| NFR-016 | All foreign key columns must be indexed for join performance | Must Have |
| NFR-017 | Frequently queried columns (email, NIS, tanggal, status, kehadiran) must be indexed | Must Have |
| NFR-018 | Database migrations must include all necessary indexes | Must Have |
| NFR-019 | Dashboard data is cached with 5-minute TTL (Cache::remember, 300s) | Must Have |
| NFR-020 | Admin panel supports multi-factor authentication (MFA) via Filament | Must Have |
| NFR-021 | Write operations (create/edit/delete activities) are blocked when tahun ajaran context is inactive | Must Have |

### 5.3 Soft deletes

| ID | Requirement | Priority |
|----|-------------|----------|
| NFR-022 | All primary tables support soft deletes (users, tahun_ajaran, kelas, siswa, mata_pelajaran, aktivitas_pembelajaran, detail_aktivitas, laporan) | Must Have |

---

## 6. Database structure

### 6.1 Core tables

The system implements the following database tables with soft deletes on all primary tables:

#### users
- User accounts for all roles (Admin, Teacher, Student)
- **Fields:** id, name, email, email_verified_at, password, jenis_kelamin (enum: L/P), app_authentication_secret (MFA), app_authentication_recovery_codes (MFA), remember_token, timestamps, softDeletes
- **Note:** Uses `name` column (with `nama` accessor for Indonesian compatibility)
- **Indexes:**
  - PRIMARY KEY: `id`
  - UNIQUE INDEX: `email` (for login and preventing duplicates)

#### tahun_ajaran (Academic Years)
- Academic year and semester information
- **Fields:** id, nama_tahun (varchar 20), semester (enum: Ganjil/Genap), tanggal_mulai (date), tanggal_selesai (date), status (boolean), timestamps, softDeletes
- **Indexes:**
  - PRIMARY KEY: `id`
  - UNIQUE INDEX: `nama_tahun, semester` (prevents duplicate year+semester combinations)
  - INDEX: `status` (for filtering active/inactive years)

#### kelas (Classes)
- Class definitions with homeroom teacher, scoped to academic year
- **Fields:** id, tingkat_kelas (tinyInteger: 7-9), grup_kelas (char 1: A-Z), wali_kelas_id (FK→users), tahun_ajaran_id (FK→tahun_ajaran), timestamps, softDeletes
- **Indexes:**
  - PRIMARY KEY: `id`
  - FOREIGN KEY: `wali_kelas_id` REFERENCES users(id) ON UPDATE CASCADE ON DELETE RESTRICT
  - FOREIGN KEY: `tahun_ajaran_id` REFERENCES tahun_ajaran(id) ON UPDATE CASCADE ON DELETE RESTRICT
  - COMPOSITE INDEX: `tingkat_kelas, grup_kelas` (for class lookups)
  - UNIQUE INDEX: `tingkat_kelas, grup_kelas, tahun_ajaran_id` (prevents duplicate class per academic year)

#### siswa (Students)
- Student records linked to users and classes
- **Fields:** id, nis (varchar 20, unique), user_id (FK→users), kelas_id (FK→kelas), timestamps, softDeletes
- **Note:** NIS is exactly 10 digits; temporary NIS uses prefix "9"
- **Indexes:**
  - PRIMARY KEY: `id`
  - UNIQUE INDEX: `nis`
  - FOREIGN KEY: `user_id` REFERENCES users(id) ON UPDATE CASCADE ON DELETE CASCADE
  - FOREIGN KEY: `kelas_id` REFERENCES kelas(id) ON UPDATE CASCADE ON DELETE RESTRICT
  - INDEX: `kelas_id` (siswa_kelas_idx)

#### mata_pelajaran (Subjects)
- Subject-class-teacher assignments
- **Fields:** id, nama_mapel (varchar 100), guru_id (FK→users), kelas_id (FK→kelas), timestamps, softDeletes
- **Indexes:**
  - PRIMARY KEY: `id`
  - FOREIGN KEY: `guru_id` REFERENCES users(id) ON UPDATE CASCADE ON DELETE RESTRICT
  - FOREIGN KEY: `kelas_id` REFERENCES kelas(id) ON UPDATE CASCADE ON DELETE CASCADE
  - COMPOSITE INDEX: `kelas_id, guru_id` (for subject-teacher queries)

#### aktivitas_pembelajaran (Learning Activities)
- Daily lesson records created by teachers
- **Fields:** id, tanggal (date), topik (varchar 255, nullable), catatan (text, nullable), kelas_id (FK→kelas), mata_pelajaran_id (FK→mata_pelajaran), guru_id (FK→users), timestamps, softDeletes
- **Indexes:**
  - PRIMARY KEY: `id`
  - FOREIGN KEY: `kelas_id` REFERENCES kelas(id) ON UPDATE CASCADE ON DELETE RESTRICT
  - FOREIGN KEY: `mata_pelajaran_id` REFERENCES mata_pelajaran(id) ON UPDATE CASCADE ON DELETE RESTRICT
  - FOREIGN KEY: `guru_id` REFERENCES users(id) ON UPDATE CASCADE ON DELETE RESTRICT
  - INDEX: `tanggal` (for chronological sorting)
  - COMPOSITE INDEX: `kelas_id, tanggal` (for class-specific date queries)
  - COMPOSITE INDEX: `kelas_id, mata_pelajaran_id, tanggal` (aktivitas_kelas_mapel_tanggal_idx)
  - COMPOSITE INDEX: `mata_pelajaran_id, tanggal` (aktivitas_mapel_tanggal_idx)

#### detail_aktivitas (Activity Details)
- Individual student records for each activity
- **Fields:** id, kehadiran (enum: hadir/izin/sakit/alpa, default alpa), nilai (decimal 5,2, nullable — auto-computed, deprecated), partisipasi (tinyInteger, nullable — keaktifan score 1-4), catatan (text, nullable), aktivitas_pembelajaran_id (FK→aktivitas_pembelajaran), siswa_id (FK→siswa), timestamps, softDeletes
- **Note:** `partisipasi` stores numeric values (1-4) but is displayed as "Keaktifan" with labels. `nilai` is auto-computed from partisipasi via resolveNilaiFromPartisipasi() and is hidden from UI.
- **Indexes:**
  - PRIMARY KEY: `id`
  - FOREIGN KEY: `aktivitas_pembelajaran_id` REFERENCES aktivitas_pembelajaran(id) ON UPDATE CASCADE ON DELETE CASCADE
  - FOREIGN KEY: `siswa_id` REFERENCES siswa(id) ON UPDATE CASCADE ON DELETE CASCADE
  - INDEX: `kehadiran` (for attendance status filtering)
  - COMPOSITE INDEX: `siswa_id, aktivitas_pembelajaran_id` (prevents duplicate entries)
  - COMPOSITE INDEX: `siswa_id, kehadiran` (detail_siswa_kehadiran_idx)
  - COMPOSITE INDEX: `aktivitas_pembelajaran_id, kehadiran` (detail_aktivitas_kehadiran_idx)

#### laporan (Reports)
- Pre-calculated summary data for faster reporting
- **Fields:** id, rata_kehadiran (float), hadir_count (integer), izin_count (integer), sakit_count (integer), alpa_count (integer), total_kehadiran (integer), rata_nilai (float, nullable — auto-computed from keaktifan), rata_partisipasi (integer, nullable), siswa_id (FK→siswa), mata_pelajaran_id (FK→mata_pelajaran), tahun_ajaran_id (FK→tahun_ajaran), timestamps, softDeletes
- **Indexes:**
  - PRIMARY KEY: `id`
  - FOREIGN KEY: `siswa_id` REFERENCES siswa(id) ON DELETE CASCADE
  - FOREIGN KEY: `mata_pelajaran_id` REFERENCES mata_pelajaran(id) ON DELETE CASCADE
  - FOREIGN KEY: `tahun_ajaran_id` REFERENCES tahun_ajaran(id) ON DELETE CASCADE
  - UNIQUE INDEX: `siswa_id, mata_pelajaran_id, tahun_ajaran_id` (laporan_unique — one report per student per subject per academic year)
  - INDEX: `tahun_ajaran_id`

#### siswa_kelas_history (Student-Class Enrollment History)
- **NEW TABLE** — Tracks which class a student was enrolled in for each academic year. Enables historical lookups even after `siswa.kelas_id` is updated.
- **Fields:** id, siswa_id (FK→siswa), kelas_id (FK→kelas), tahun_ajaran_id (FK→tahun_ajaran), timestamps
- **Indexes:**
  - PRIMARY KEY: `id`
  - FOREIGN KEY: `siswa_id` REFERENCES siswa(id) ON UPDATE CASCADE ON DELETE CASCADE
  - FOREIGN KEY: `kelas_id` REFERENCES kelas(id) ON UPDATE CASCADE ON DELETE RESTRICT
  - FOREIGN KEY: `tahun_ajaran_id` REFERENCES tahun_ajaran(id) ON UPDATE CASCADE ON DELETE RESTRICT
  - UNIQUE INDEX: `siswa_id, tahun_ajaran_id` (unique_siswa_per_tahun_ajaran — one class per student per year)
  - INDEX: `tahun_ajaran_id` (skh_tahun_ajaran_idx)

### 6.2 Key relationships

- One academic year has many classes
- One class has one homeroom teacher (wali kelas)
- One class has many students (current enrollment via siswa.kelas_id)
- One class has many subjects
- One subject-class has one teacher
- One learning activity has many detail records (one per student)
- One student has many detail records across activities
- One student has many kelas_history records (one per academic year)
- One student has many laporan records (one per subject per academic year)
- One academic year has many laporan records
- One academic year has many siswa_kelas_history records

### 6.3 Indexing strategy rationale

**Why these indexes matter:**

1. **Foreign Keys:** Automatically indexed by InnoDB for referential integrity and join performance
2. **UNIQUE Indexes:** Prevent duplicate data (email, NIS, tahun_ajaran name+semester, kelas per tahun, laporan per student-subject-year, siswa per tahun_ajaran)
3. **Single Column Indexes:** Speed up filtering, sorting, and WHERE clauses on frequently queried columns (tanggal, status, kehadiran, kelas_id, tahun_ajaran_id)
4. **Composite Indexes:** Optimize queries that filter by multiple columns simultaneously:
   - `kelas_id, tanggal` — class-specific chronological queries
   - `kelas_id, mata_pelajaran_id, tanggal` — subject+class date-range queries
   - `mata_pelajaran_id, tanggal` — subject-specific chronological queries
   - `siswa_id, aktivitas_pembelajaran_id` — prevents duplicate student entries per activity
   - `siswa_id, kehadiran` — student attendance statistics
   - `aktivitas_pembelajaran_id, kehadiran` — activity-level attendance aggregation
   - `kelas_id, guru_id` — subject-teacher assignment queries
5. **Date Indexes:** Essential for chronological sorting and date range queries in learning activities and dashboards

**Performance impact:**
- Improves query performance for data tables with pagination
- Speeds up report generation (especially with date ranges)
- Optimizes dashboard statistics calculations (teacher dashboard uses single aggregate SQL queries)
- Enables efficient filtering by class, subject, teacher, and student
- Dashboard caching (5-min TTL) further reduces database load

**Trade-offs:**
- Indexes use additional storage space (acceptable for project scale)
- Slight overhead on INSERT/UPDATE operations (minimal for this application's write volume)
- Overall benefit far outweighs the cost for read-heavy educational systems

---

## 7. User stories (Core functionality)

### 7.1 Authentication

| ID | User Story | Acceptance Criteria |
|----|------------|---------------------|
| US-001 | As a **user**, I want to log in with email and password | Login form accepts email and password; Valid credentials redirect to appropriate dashboard (admin→/app, teacher→/guru, student→/siswa); Invalid credentials show error message; Admin users may be prompted for MFA |
| US-002 | As a **user**, I want to log out securely | Logout button available; Session ends on logout; Redirected to login page |

### 7.2 Master data management (Admin)

| ID | User Story | Acceptance Criteria |
|----|------------|---------------------|
| US-003 | As an **admin**, I want to create academic years | Form has fields for name, semester, start/end dates; Can mark one academic year as active; List shows all academic years; Direct creation limited to initial setup; Subsequent transitions use Ganti Semester or Kenaikan Kelas |
| US-003a | As an **admin**, I want to execute Ganti Semester | Wizard with 3 steps: (1) new semester details, (2) wali kelas reassignment, (3) confirmation; Automatically creates new tahun_ajaran, clones all classes, migrates students, and duplicates mata_pelajaran assignments |
| US-003b | As an **admin**, I want to execute Kenaikan Kelas | Wizard with 4 steps: (1) new year details, (2) wali kelas for new classes, (3) student decisions per student (naik/tinggal/lulus), (4) confirmation; Auto-creates new classes for promoted grades; Graduating students (grade 9) are soft-deleted; Retained students stay in same grade |
| US-004 | As an **admin**, I want to create classes | Form has grade level (7-9), group (A-Z, auto-assigned to next available), homeroom teacher, tahun ajaran; System prevents duplicate class combinations per tahun_ajaran; List shows all classes with eager-loaded relations |
| US-005 | As an **admin**, I want to register students | 3-step wizard: (1) NIS (10-digit, with temporary NIS toggle), (2) user account (name, email, password, gender), (3) class placement (filtered to active tahun ajaran); NIS must be unique; Student user account is automatically created with "student" role |
| US-006 | As an **admin**, I want to create subjects | Form has subject name, teacher (filtered to "teacher" role), class; One teacher per subject-class combination; List shows subjects with eager-loaded guru and kelas |
| US-007 | As an **admin**, I want to create teacher and student user accounts | Form has name, email, password, role, gender; Email must be unique; Password is hashed before saving; Role assignment via Spatie (admin/teacher/student) |

### 7.3 Learning activity recording (Teacher)

| ID | User Story | Acceptance Criteria |
|----|------------|---------------------|
| US-008 | As a **teacher**, I want to create a daily learning activity | 2-step process: (1) select via cascading filter (tingkat kelas → grup kelas → mata pelajaran, auto-fills kelas_id), enter date (default today), topic, notes; (2) record attendance and keaktifan per student; Blocked if tahun ajaran context is inactive; Activity saved with teacher ID |
| US-009 | As a **teacher**, I want to record attendance | Student cards are shown for selected class; Can select: Hadir, Izin, Sakit, Alpa for each student via H/I/S/A buttons; "Tandai Semua Hadir" bulk action; Unset count indicator; Attendance is linked to the learning activity |
| US-010 | As a **teacher**, I want to assign keaktifan (participation) scores | Can select 1-4 for each student via Pasif/Cukup/Aktif/Sangat Aktif buttons; Only available when attendance is "Hadir"; Score is saved with activity details; Nilai is auto-computed from keaktifan (not shown in UI) |
| US-011 | ~~As a teacher, I want to input grades~~ **Removed.** Nilai is auto-computed from keaktifan and hidden from the UI. | N/A |
| US-012 | As a **teacher**, I want to add feedback notes for students | Catatan button available per student (only when attendance is "Hadir"); Notes are optional; Notes are visible to student; Blue dot indicator when notes exist |
| US-013 | As a **teacher**, I want to view my learning activities | Activities grouped by date; Can filter by subject and quick period (today/week/month); Search by topic; Pagination with "Load More"; Inline attendance/keaktifan stats per activity; Click to view/edit/delete with confirmation modal |

### 7.4 Student viewing (Student)

| ID | User Story | Acceptance Criteria |
|----|------------|---------------------|
| US-014 | As a **student**, I want to view my attendance history | Riwayat Aktivitas page with filters (search, subject, attendance status); Paginated table showing date, subject, attendance status (color-coded), keaktifan label; Can export as PDF |
| US-015 | As a **student**, I want to view my keaktifan and grades | Dashboard shows per-subject list with: attendance percentage, keaktifan label, composite score, total activities; Dashboard heatmap shows daily attendance status for the entire academic year |
| US-016 | As a **student**, I want to see my dashboard | Dashboard shows: 4 attendance stat cards (Hadir/Izin/Sakit/Alpa counts); Attendance heatmap calendar; Attendance streak counter; Motivational message based on performance; Per-subject composite scores; Quick date filter (semester/bulan/minggu) |

### 7.5 Reporting (Teacher & Admin)

| ID | User Story | Acceptance Criteria |
|----|------------|---------------------|
| US-017 | As a **wali kelas** (homeroom teacher), I want to generate a student report | Can select student (from my wali kelas only) and view preview; Report shows attendance summary, keaktifan averages, per-activity timeline; Can export to PDF; Access denied for non-wali-kelas teachers |
| US-018 | As a **wali kelas** (homeroom teacher), I want to generate a class report | Can select class, subject, sort order (kehadiran/nama); Preview shows all students with attendance and keaktifan; Can export to PDF and Excel; Uses kelasHistory for accurate historical data |
| US-018a | As an **admin**, I want to generate any student or class report | Can select any student + tahun_ajaran to preview and download PDF; Can select any class + subject + tahun_ajaran to preview and download PDF/Excel |

### 7.6 Dashboard (All users)

| ID | User Story | Acceptance Criteria |
|----|------------|---------------------|
| US-019 | As an **admin**, I want to see system overview | Dashboard shows: 6 stat cards (Admin, Guru, Siswa, Kelas, Mata Pelajaran, Aktivitas Bulan Ini); Recent Activities table (10 latest); 7-day activity line chart; 60s polling, 5-min cache |
| US-020 | As a **teacher**, I want to see my teaching overview | Dashboard shows: 4 stat cards (Kelas Diampu, Total Siswa, Aktivitas Minggu Ini, Rata-rata Kehadiran); 3 ApexCharts (Tren Kehadiran stacked column, Keaktifan per Topik stacked bar, Distribusi Keaktifan donut); Partisipasi per Kelas table with composite scores; Filter controls (kelas, mata pelajaran, rentang waktu); 5-min cache |
| US-021 | As a **student**, I want to see my learning summary | Dashboard shows: 4 stat cards (Hadir/Izin/Sakit/Alpa); Heatmap calendar; Streak counter; Motivational message; Per-subject list with composite scores; Quick date filter; 5-min cache |

### 7.7 Academic year context

| ID | User Story | Acceptance Criteria |
|----|------------|---------------------|
| US-022 | As a **teacher or student**, I want to switch between academic years | Tahun Ajaran selector dropdown in sidebar; Lists all tahun_ajaran sorted by name descending; Changing selection triggers page reload; Context persists in session |

### 7.8 Profile management

| ID | User Story | Acceptance Criteria |
|----|------------|---------------------|
| US-023 | As a **teacher**, I want to manage my profile | Can view name (read-only); Can edit email; Can change password (requires current password verification) |
| US-024 | As a **student**, I want to manage my profile | Can view name (read-only), NIS (read-only), current class (read-only, tahun ajaran aware); Can edit email; Can change password (requires current password verification) |

---

## 8. Technology stack

### 8.1 Backend
- **Framework:** Laravel 12.x (PHP 8.3)
- **Admin Panel:** FilamentPHP 4.x (for Admin interface only)
- **Authentication:** Laravel built-in Auth with session-based authentication
- **Authorization:** Spatie Laravel Permission
- **MFA:** Filament Multi-Factor Authentication (app-based)

### 8.2 Frontend

#### Admin Interface (Desktop-only)
- **UI Framework:** FilamentPHP 4.x
- **Styling:** TailwindCSS (bundled with Filament)
- **Reactivity:** Livewire 3.x (bundled with Filament)
- **JavaScript:** Alpine.js (included with Filament)
- **Charts:** FilamentPHP Chart Widgets (for admin dashboard line chart)

#### Teacher & Student Interfaces (Mobile-responsive)
- **UI Library:** Flux UI (Official Livewire component library)
- **Reactivity:** Livewire 3.x
- **Styling:** TailwindCSS (with Flux UI components)
- **JavaScript:** Alpine.js (minimal, included with Livewire; used for client-side state management in attendance forms)
- **Charts:** ApexCharts.js (for teacher dashboard: stacked column, stacked bar, donut charts)
- **Icons:** Heroicons (built into Flux UI)

**Architecture Rationale:**
- **FilamentPHP:** Ideal for complex admin operations (CRUD, master data management, wizards) on desktop
- **Flux UI:** Mobile-first, lightweight components perfect for daily teacher/student operations
- **Alpine.js:** Used for client-side state management in attendance forms (saveWithDetail pattern — all state in Alpine, single $wire call)
- **ApexCharts.js:** Full-featured interactive charts for teacher dashboard analysis
- **Hybrid approach:** Balances powerful admin tools with mobile-friendly user experiences

### 8.3 Database
- **RDBMS:** MySQL 8.0+
- **ORM:** Laravel Eloquent
- **Caching:** Laravel Cache (5-minute TTL for dashboards) + Report aggregation table (`laporan`) for performance

### 8.4 Additional packages
- **PDF Export:** DomPDF (Barryvdh/laravel-dompdf)
- **Excel Export:** Maatwebsite/Laravel-Excel
- **Charts:** ApexCharts.js (CDN)
- **Form Validation:** Laravel built-in validation
- **UI Components:** Livewire Flux (free tier)
- **Authorization:** Spatie/laravel-perission

### 8.5 Development environment
- **Local Server:** Laragon (Windows) / Docker
- **Version Control:** Git
- **Package Manager:** Composer, NPM
- **Testing:** Pest (browser testing, faker, Livewire plugins)
- **Code Quality:** Laravel Pint (formatting), Larastan (static analysis), Rector (automated refactoring)

---

## 9. UI architecture & mobile responsiveness

### 9.1 Design philosophy

SIPPEL employs a **hybrid architecture** to balance powerful desktop admin tools with mobile-friendly interfaces for daily operations:

**Admin Interface (Desktop-first):**
- Complex CRUD operations require larger screens
- FilamentPHP provides sophisticated data tables, bulk actions, multi-step wizards, and system configuration
- Admins typically work from office computers/laptops
- Desktop-only interface is acceptable for this user group
- Multi-factor authentication (MFA) enforced

**Teacher & Student Interfaces (Mobile-first):**
- Daily operations (attendance, keaktifan entry, viewing progress) done on smartphones
- Flux UI provides touch-friendly, responsive components
- Card-based layouts stack vertically on mobile devices
- Large touch targets (44px minimum) for easy thumb navigation
- Lightweight JavaScript bundle for faster loading on mobile networks
- Alpine.js used for client-side state management (attendance forms use single saveWithDetail call)
- Tahun Ajaran selector in sidebar for context switching

### 9.2 Component library comparison

| Feature | FilamentPHP (Admin) | Flux UI (Teacher/Student) |
|---------|---------------------|---------------------------|
| **Target Device** | Desktop/Laptop | Mobile/Tablet/Desktop |
| **Touch Targets** | Small (desktop mouse) | Large (44px+ for touch) |
| **Table Display** | Complex multi-column | Card-based or simple lists |
| **Navigation** | Sidebar navigation | Bottom/top navigation bars |
| **Form Layout** | Multi-column grids | Single-column stacked |
| **Bundle Size** | Heavy (full admin panel) | Lightweight (minimal JS) |
| **Learning Curve** | Moderate (Resource classes) | Simple (Blade components) |
| **Use Case** | Master data CRUD, Wizards | Daily operations, Charts |
| **Charts** | Filament Chart Widgets | ApexCharts.js |

### 9.3 Key shared component: TahunAjaranSelector

A shared Livewire component (`TahunAjaranSelector`) is embedded in both teacher and student layouts. It provides:
- Dropdown listing all academic years (sorted by nama_tahun descending)
- Session-based context storage (`tahun_ajaran_context`)
- Automatic fallback to active tahun_ajaran if no context is set
- Full page reload on change via browser event (`tahun-ajaran-changed`)
- Theme variant support (slate for teacher, teal for student)

### 9.4 Attendance form architecture (Alpine.js saveWithDetail)

The attendance entry form (create/edit) uses a bulk-save pattern:
- All student state (kehadiran, keaktifan, catatan) is managed client-side in an Alpine.js `students` object
- Buttons toggle values directly in Alpine (no Livewire round-trips)
- On save, the entire `students` object is sent to the server in a single `$wire.saveWithDetail()` call
- This eliminates N+1 network requests (one per student) and dramatically improves mobile performance

---

## 10. Success criteria

### 10.1 Functional completeness

The project is considered complete when:
- All 50 functional requirements (FR-001 through FR-036, including sub-requirements) are implemented
- All 24 user stories (US-001 to US-024) work as specified
- Three user roles function with proper permissions (admin, teacher, student)
- Teachers can record activities with cascading class selection and bulk attendance save
- Keaktifan (1-4 scale) is recorded per student; nilai is auto-computed
- Students can view heatmap calendar, streak, and motivational messages
- Admin can manage all master data including Ganti Semester and Kenaikan Kelas
- Tahun Ajaran context selector works across teacher and student interfaces
- Wali kelas can generate and export student/class reports (PDF + Excel)
- Admin can generate any student/class report

### 10.2 Technical requirements

- Application runs without critical errors
- Database structure matches specification with all required indexes (12 migrations, 9 models)
- All foreign keys have proper indexes with cascade/restrict rules
- All forms have proper validation with Indonesian error messages
- Security measures implemented (password hashing bcrypt, CSRF protection, MFA for admin)
- Teacher and student interfaces are responsive on different screen sizes (Flux UI)
- All text is in Indonesian language
- Queries use eager loading to prevent N+1 problems
- Dashboard data is cached with 5-minute TTL
- Alpine.js bulk-save eliminates N+1 network requests in attendance forms

### 10.3 Documentation

- Database schema documentation (this PRD Section 6)
- User manual (how to use the system)
- Installation guide
- Source code comments for complex logic

### 10.4 Demonstration

For final presentation, system should demonstrate:
- Complete user flow: Admin creates data → Teacher records activity → Student views progress
- Ganti Semester and Kenaikan Kelas wizards
- Tahun Ajaran context switching
- Teacher dashboard with interactive ApexCharts
- Student dashboard with heatmap calendar and streak
- Report generation and PDF/Excel export
- Responsive design on mobile devices (Teacher/Student interfaces)
- Alpine.js bulk save in attendance form

---

## 11. Optional enhancements (Bonus features)

### 11.1 Implemented (moved from Bonus)

- [x] Excel export for class reports (Maatwebsite/Laravel-Excel)
- [x] Chart visualizations on teacher dashboard (ApexCharts.js: 3 interactive charts)
- [x] Chart visualizations on admin dashboard (Filament chart widget)
- [x] Advanced filtering on data tables (teacher aktivitas list, student riwayat)

### 11.2 Nice to have (not yet implemented)

- [ ] Bulk student import via CSV/Excel
- [ ] Edit history/audit trail
- [ ] Profile photo upload for users
- [ ] Print-friendly report layouts
- [ ] Progressive Web App (PWA) features (offline mode, install on home screen)

### 11.3 Advanced (not yet implemented)

- [ ] Email notifications (e.g., low attendance alert)
- [ ] Automated backup functionality
- [ ] Password reset via email
- [ ] Multi-language support (English + Indonesian)
- [ ] Push notifications for mobile users
- [ ] Parent/guardian portal

---

## 12. References & resources

### 12.1 Official documentation
- Laravel: https://laravel.com/docs
- FilamentPHP: https://filamentphp.com/docs
- Flux UI: https://fluxui.dev/docs
- Livewire: https://livewire.laravel.com/docs
- Spatie Permission: https://spatie.be/docs/laravel-permission
- ApexCharts.js: https://apexcharts.com/docs/

### 12.2 Development resources
- Laragon (local development): https://laragon.org
- HeidiSQL (database management): https://www.heidisql.com
- Composer (package manager): https://getcomposer.org
- Pest (testing framework): https://pestphp.com
- Maatwebsite Excel: https://docs.laravel-excel.com

### 12.3 UI/UX resources
- Heroicons (icon library): https://heroicons.com
- Tailwind CSS: https://tailwindcss.com/docs
- Mobile-first design principles

---

## 13. Constraints and assumptions

### 13.1 Constraints
- **Time limit:** 16 weeks (approximately 4 months)
- **Team size:** Solo project or small team (1-3 students)
- **Budget:** Free and open-source tools only
- **Environment:** Development on local machine, deployment optional

### 13.2 Assumptions
- Developer has basic knowledge of Laravel and PHP
- Developer has access to local development environment
- MySQL database is available locally
- Internet access for package installation, documentation, and CDN resources (ApexCharts.js)

---

## 14. Risk assessment

| Risk | Impact | Mitigation |
|------|--------|------------|
| Scope creep (adding too many features) | High | Stick to Must Have requirements, track optional features separately |
| FilamentPHP learning curve | Medium | Start with official documentation, use tutorials, allocate extra time for learning |
| Database design issues | High | Review schema thoroughly before implementation, use migrations for version control |
| Time management | Medium | Follow implementation phases strictly, use project management tool |
| Data validation complexity | Medium | Use Laravel's built-in validation, test thoroughly with edge cases |
| Keaktifan/nilai data model confusion | Medium | Document clearly that nilai is auto-computed and hidden; plan full removal from schema |

---

## Appendix A: Glossary

| Term | Definition |
|------|------------|
| Admin | School administrator with full system access (role=`admin`) |
| Alpa | Absent without permission |
| Ganjil | Odd semester (typically Jul-Dec) |
| Ganti Semester | Admin wizard to transition all data to a new semester |
| Genap | Even semester (typically Jan-Jun) |
| Guru | Teacher |
| Hadir | Present (attendance status) |
| Heatmap | Calendar visualization showing daily attendance status across the academic year |
| Izin | Absent with permission |
| Keaktifan | Participation level (Pasif/Cukup/Aktif/Sangat Aktif) — displayed as "Keaktifan" in UI, stored as `partisipasi` (1-4) in DB |
| Kelas | Class (e.g., 7A, 8B) |
| Kenaikan Kelas | Admin wizard to promote students to next grade level |
| Mata Pelajaran | Subject/course |
| NIS | Student Identification Number (10 digits) |
| NIS Sementara | Temporary NIS auto-generated with prefix "9" for students without official NIS |
| Sakit | Sick leave (attendance status) |
| Siswa | Student |
| SIPPEL | Sistem Informasi Pencatatan Aktivitas Pembelajaran |
| SMP | Junior High School (grades 7-9) |
| Streak | Consecutive days of "Hadir" attendance from most recent activity |
| Tahun Ajaran | Academic year |
| Wali Kelas | Homeroom teacher |

---

## Appendix B: Reference links

- Laravel Documentation: https://laravel.com/docs
- FilamentPHP Documentation: https://filamentphp.com/docs
- Spatie Laravel Permission: https://spatie.be/docs/laravel-permission
- MySQL Documentation: https://dev.mysql.com/doc/
- Tailwind CSS: https://tailwindcss.com/docs
- ApexCharts.js Documentation: https://apexcharts.com/docs/
- Flux UI Documentation: https://fluxui.dev/docs
- Livewire Documentation: https://livewire.laravel.com/docs

---

## Appendix C: Delta Report Summary

The following is a summary of key discrepancies between the original PRD v1.0 and the as-built system:

### Schema Changes
| Change | Description |
|--------|-------------|
| `users.name` vs `users.nama` | Using `name` column with `nama` accessor for Filament compatibility |
| `tahun_ajaran` unique | `[nama_tahun, semester]` instead of just `nama_tahun` |
| `kelas` unique | `[tingkat_kelas, grup_kelas, tahun_ajaran_id]` instead of `[tingkat_kelas, grup_kelas]` |
| `laporan` columns | Added 5 stat columns: hadir_count, izin_count, sakit_count, alpa_count, total_kehadiran |
| `siswa_kelas_history` | New table for tracking enrollment per academic year |
| Soft Deletes | All primary tables use soft deletes (not in original PRD) |
| `partisipasi` scale | 1-4 (not 1-5); displayed as "Keaktifan" |
| `nilai` column | Still exists but auto-computed, hidden from UI, deprecated |
| MFA columns | `app_authentication_secret` and `app_authentication_recovery_codes` on users table |

### Feature Additions
| Feature | Classification |
|---------|---------------|
| Ganti Semester wizard | New (C2) |
| Kenaikan Kelas wizard | New (C2) |
| Tahun Ajaran Context Selector | New (C2) |
| Temporary NIS generation | New (C2) |
| NIS 10-digit validation | Modified (C3) |
| Student Attendance Heatmap | New (C2) |
| Attendance Streak | New (C2) |
| Motivational Messages | New (C2) |
| Teacher Dashboard ApexCharts (3 charts) | New (C2) |
| Teacher Dashboard composite scoring | New (C2) |
| Excel export for class reports | New (C2 — moved from Bonus) |
| Laporan restricted to wali kelas | Modified (C3) |
| Student activity history with PDF export | New (C2) |
| Student/Teacher profile pages | New (C2) |
| Alpine.js saveWithDetail bulk save | New (C6) |
| Dashboard caching (5-min TTL) | New (C2) |
| MFA for admin panel | New (C2) |
| TahunAjaran canCreate() restricted | Modified (C3) |

### Deprecated Features
| Feature | Status |
|---------|--------|
| FR-016 / US-011: Manual numeric grade input | Hidden from UI; nilai auto-computed from keaktifan; TODO: full removal from schema |

---

**Document prepared for:** Academic Final Project  
**Document version:** 2.0 (As-Built)  
**Estimated effort:** 200-250 hours of development work  
**Complexity level:** Intermediate  

---

*This PRD reflects the actual implemented SIPPEL system as of June 2026. All divergences from the original v1.0 PRD are documented in Section 4 (Functional Requirements), Section 6 (Database Structure), and Appendix C (Delta Report Summary).*