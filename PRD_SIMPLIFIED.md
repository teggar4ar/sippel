# Product Requirements Document: SIPPEL (Sistem Informasi Pemantauan Pembelajaran Kelas)
## Simplified Version for Final Project

---

## Document information

**Version:** 1.0 (Simplified for Academic Final Project)  
**Date:** October 19, 2025  
**Project:** SIPPEL - Web-Based Classroom Learning Activity Monitoring System  
**Author:** Student Final Project  
**Status:** Final Project Specification  
**Project Duration:** 3-4 months  

---

## 1. Introduction

### 1.1 Project overview

SIPPEL (Sistem Informasi Pemantauan Pembelajaran Kelas) is a web-based information system designed to help junior high schools (SMP) monitor and manage classroom learning activities. This system will replace manual, paper-based attendance and grading processes with a digital solution.

### 1.2 Purpose of this document

This document serves as the complete specification for developing SIPPEL as a final project, outlining:
- Core features and functionality
- User roles and permissions
- Database structure
- Technical implementation requirements
- Success criteria for project completion

### 1.3 Project scope

**What this project WILL include:**
- User authentication with 3 roles (Admin, Teacher, Student)
- Master data management (academic years, classes, subjects, students)
- Daily learning activity recording (attendance, grades, participation)
- Basic reports and dashboards
- PDF export for reports
- Responsive web interface using FilamentPHP

**What this project will NOT include:**
- Parent/guardian portal
- Mobile native apps
- Real-time notifications/alerts
- Automated backup systems
- Integration with external systems
- Advanced analytics or AI features

---

## 2. Problem statement and solution

### 2.1 Problem

Junior high schools currently face challenges with:
1. Manual record-keeping in physical logbooks (time-consuming and error-prone)
2. Difficulty compiling progress reports from scattered data
3. Limited student access to their own learning progress
4. Risk of data loss with paper-based systems

### 2.2 Solution

SIPPEL will provide:
- **Digital data entry** for attendance, grades, and participation
- **Centralized database** storing all learning activity data
- **Role-based access** for admins, teachers, and students
- **Automated calculations** for averages and attendance percentages
- **Simple reports** in PDF format
- **Student portal** to view personal progress

### 2.3 Expected outcomes

Upon completion, this project will demonstrate:
- Full-stack web development skills (Laravel + FilamentPHP)
- Database design and implementation (MySQL)
- User authentication and authorization
- CRUD operations and form validation
- Report generation
- Responsive UI development

---

## 3. User roles and permissions

### 3.1 Admin (Operator Sekolah)

**Responsibilities:**
- Manage master data (academic years, classes, subjects, teachers, students)
- Create and manage user accounts
- View system-wide reports
- Configure system settings

**Permissions:**
- Full CRUD access to all master data
- User management (create, edit, deactivate)
- Access all reports and dashboards

### 3.2 Teacher (Guru)

**Responsibilities:**
- Record daily learning activities for assigned classes
- Input student attendance, grades, and participation
- View and edit own activity records
- Generate class and student reports

**Permissions:**
- Create/edit learning activities for assigned subjects only
- View student data in assigned classes
- Export reports for own classes

### 3.3 Student (Siswa)

**Responsibilities:**
- View personal learning progress
- Check attendance history
- Review grades and participation scores

**Permissions:**
- Read-only access to personal data
- View own attendance, grades, and reports
- Cannot modify any data

---

## 4. Functional requirements

### 4.1 Authentication & user management

| ID | Requirement | Priority |
|----|-------------|----------|
| FR-001 | Users can log in with email and password | Must Have |
| FR-002 | System supports three user roles: Admin, Teacher, Student | Must Have |
| FR-003 | Passwords are hashed and securely stored | Must Have |
| FR-004 | Users can view and edit their profile information | Must Have |
| FR-005 | Admin can create, edit, and deactivate user accounts | Must Have |

### 4.2 Master data management

| ID | Requirement | Priority |
|----|-------------|----------|
| FR-006 | Admin can create and manage academic years (nama_tahun, semester, dates) | Must Have |
| FR-007 | Only one academic year can be active at a time | Must Have |
| FR-008 | Admin can create classes with grade level (7-9) and group (A-Z) | Must Have |
| FR-009 | Admin can assign one homeroom teacher (wali kelas) per class | Must Have |
| FR-010 | Admin can register students with unique NIS number | Must Have |
| FR-011 | Admin can assign students to classes | Must Have |
| FR-012 | Admin can create subjects and assign teachers to subject-class combinations | Must Have |

### 4.3 Learning activity management

| ID | Requirement | Priority |
|----|-------------|----------|
| FR-013 | Teacher can create learning activity with date, topic, and notes | Must Have |
| FR-014 | Teacher can record attendance for each student (Hadir, Izin, Sakit, Alpa) | Must Have |
| FR-015 | Teacher can assign participation scores (1-5 scale) | Must Have |
| FR-016 | Teacher can input numeric grades (0-100) | Must Have |
| FR-017 | Teacher can add notes/feedback for individual students | Should Have |
| FR-018 | Teacher can view and edit previously created activities | Must Have |
| FR-019 | System displays activities in chronological order | Must Have |

### 4.4 Calculation & aggregation

| ID | Requirement | Priority |
|----|-------------|----------|
| FR-020 | System automatically calculates attendance percentage per student | Must Have |
| FR-021 | System automatically calculates average grades per student per subject | Must Have |
| FR-022 | System automatically calculates average participation per student | Must Have |

### 4.5 Student access

| ID | Requirement | Priority |
|----|-------------|----------|
| FR-023 | Student can view personal attendance history | Must Have |
| FR-024 | Student can view grades and participation scores | Must Have |
| FR-025 | Student can view teacher feedback/notes | Must Have |
| FR-026 | Student can see summary of attendance percentage and average grades | Must Have |

### 4.6 Reporting

| ID | Requirement | Priority |
|----|-------------|----------|
| FR-027 | System can generate student progress report showing attendance, grades, participation | Must Have |
| FR-028 | System can generate class report showing all students' performance | Must Have |
| FR-029 | Reports can be exported to PDF format | Must Have |
| FR-030 | Reports include attendance summary and grade averages | Must Have |

### 4.7 Dashboard

| ID | Requirement | Priority |
|----|-------------|----------|
| FR-031 | Admin dashboard shows total classes, teachers, and students | Must Have |
| FR-032 | Teacher dashboard shows classes taught and recent activities | Must Have |
| FR-033 | Student dashboard shows attendance percentage and average grades | Must Have |

Note: The MVP dashboard displays core metrics (sum, average, percentage) and simple graphs; no advanced analytics or complex filters.

---

## 5. Non-functional requirements

### 5.1 Essential requirements

| ID | Requirement | Priority |
|----|-------------|----------|
| NFR-001 | System must use Laravel framework for backend | Must Have |
| NFR-002 | System must use FilamentPHP for admin interface | Must Have |
| NFR-003 | System must use MySQL database | Must Have |
| NFR-004 | Passwords must be hashed using bcrypt | Must Have |
| NFR-005 | Forms must have CSRF protection | Must Have |
| NFR-006 | Interface must be in Indonesian language | Must Have |
| NFR-007 | Interface must be responsive (desktop, tablet, mobile) | Must Have |
| NFR-008 | System must validate all form inputs | Must Have |

### 5.2 Desirable requirements

| ID | Requirement | Priority |
|----|-------------|----------|
| NFR-009 | Page loads should complete within 3 seconds | Should Have |
| NFR-010 | Error messages should be clear and helpful | Should Have |
| NFR-011 | System should work on Chrome, Firefox, and Edge browsers | Should Have |
| NFR-012 | Authentication based on session Laravel (without API token) | Must Have |
| NFR-013 | Not using Sanctum or Passport for simplicity | Must Have |
| NFR-014 | Query using eager loading for general relations (students, classes, subjects) | Should Have |
| NFR-015 | Use pagination for data tables with more than 20 records | Should Have |
| NFR-016 | All foreign key columns must be indexed for join performance | Must Have |
| NFR-017 | Frequently queried columns (email, NIS, tanggal, status) must be indexed | Must Have |
| NFR-018 | Database migrations must include all necessary indexes | Must Have |

---

## 6. Database structure

### 6.1 Core tables

The system will implement the following database tables with proper indexing:

#### users
- User accounts for all roles (Admin, Teacher, Student)
- **Fields:** id, nama, email, password, jenis_kelamin
- **Indexes:**
  - PRIMARY KEY: `id`
  - UNIQUE INDEX: `email` (for login and preventing duplicates)

#### tahun_ajaran (Academic Years)
- Academic year and semester information
- **Fields:** id, nama_tahun, semester, tanggal_mulai, tanggal_selesai, status
- **Indexes:**
  - PRIMARY KEY: `id`
  - UNIQUE INDEX: `nama_tahun` (prevents duplicate academic year names)
  - INDEX: `status` (for filtering active/inactive years)

#### kelas (Classes)
- Class definitions with homeroom teacher
- **Fields:** id, tingkat_kelas, grup_kelas, wali_kelas_id, tahun_ajaran_id
- **Indexes:**
  - PRIMARY KEY: `id`
  - INDEX: `tingkat_kelas` (for filtering by grade level)
  - INDEX: `grup_kelas` (for filtering by group)
  - FOREIGN KEY: `wali_kelas_id` REFERENCES users(id)
  - FOREIGN KEY: `tahun_ajaran_id` REFERENCES tahun_ajaran(id)
  - COMPOSITE INDEX: `tingkat_kelas, grup_kelas` (for unique class identification)

#### siswa (Students)
- Student records linked to users and classes
- **Fields:** id, nis, user_id, kelas_id
- **Indexes:**
  - PRIMARY KEY: `id`
  - UNIQUE INDEX: `nis` (student identification number must be unique)
  - FOREIGN KEY: `user_id` REFERENCES users(id)
  - FOREIGN KEY: `kelas_id` REFERENCES kelas(id)
  - INDEX: `kelas_id` (for quickly finding students by class)

#### mata_pelajaran (Subjects)
- Subject-class-teacher assignments
- **Fields:** id, nama_mapel, guru_id, kelas_id
- **Indexes:**
  - PRIMARY KEY: `id`
  - FOREIGN KEY: `guru_id` REFERENCES users(id)
  - FOREIGN KEY: `kelas_id` REFERENCES kelas(id)
  - INDEX: `kelas_id` (for filtering subjects by class)
  - INDEX: `guru_id` (for filtering subjects by teacher)

#### aktivitas_pembelajaran (Learning Activities)
- Daily lesson records created by teachers
- **Fields:** id, tanggal, topik, catatan, kelas_id, mata_pelajaran_id, guru_id
- **Indexes:**
  - PRIMARY KEY: `id`
  - FOREIGN KEY: `kelas_id` REFERENCES kelas(id)
  - FOREIGN KEY: `mata_pelajaran_id` REFERENCES mata_pelajaran(id)
  - FOREIGN KEY: `guru_id` REFERENCES users(id)
  - INDEX: `tanggal` (for chronological sorting and date range queries)
  - INDEX: `kelas_id` (for filtering activities by class)
  - INDEX: `mata_pelajaran_id` (for filtering activities by subject)
  - INDEX: `guru_id` (for filtering activities by teacher)
  - COMPOSITE INDEX: `kelas_id, tanggal` (for class-specific date queries)

#### detail_aktivitas (Activity Details)
- Individual student records for each activity
- **Fields:** id, kehadiran, nilai, partisipasi, catatan, aktivitas_pembelajaran_id, siswa_id
- **Indexes:**
  - PRIMARY KEY: `id`
  - FOREIGN KEY: `aktivitas_pembelajaran_id` REFERENCES aktivitas_pembelajaran(id)
  - FOREIGN KEY: `siswa_id` REFERENCES siswa(id)
  - INDEX: `aktivitas_pembelajaran_id` (for quickly loading all student details for an activity)
  - INDEX: `siswa_id` (for quickly loading all activities for a student)
  - INDEX: `kehadiran` (for attendance status filtering and statistics)
  - COMPOSITE INDEX: `siswa_id, aktivitas_pembelajaran_id` (prevents duplicate entries)

#### laporan (Reports - Optional/Bonus)
- Pre-calculated summary data for faster reporting
- **Fields:** id, rata_kehadiran, rata_nilai, rata_partisipasi, siswa_id, mata_pelajaran_id, tahun_ajaran_id
- **Indexes:**
  - PRIMARY KEY: `id`
  - FOREIGN KEY: `siswa_id` REFERENCES siswa(id)
  - FOREIGN KEY: `mata_pelajaran_id` REFERENCES mata_pelajaran(id)
  - FOREIGN KEY: `tahun_ajaran_id` REFERENCES tahun_ajaran(id)
  - COMPOSITE INDEX: `siswa_id, mata_pelajaran_id, tahun_ajaran_id` (unique combination for report lookup)

### 6.2 Key relationships

- One academic year has many classes
- One class has one homeroom teacher (wali kelas)
- One class has many students
- One class has many subjects
- One subject-class has one teacher
- One learning activity has many detail records (one per student)
- One student has many detail records across activities

### 6.3 Indexing strategy rationale

**Why these indexes matter:**

1. **Foreign Keys:** Automatically indexed by InnoDB for referential integrity and join performance
2. **UNIQUE Indexes:** Prevent duplicate data (email, NIS, academic year names)
3. **Single Column Indexes:** Speed up filtering, sorting, and WHERE clauses on frequently queried columns
4. **Composite Indexes:** Optimize queries that filter by multiple columns simultaneously
5. **Date Indexes:** Essential for chronological sorting and date range queries in learning activities

**Performance impact:**
- Improves query performance for data tables with pagination
- Speeds up report generation (especially with date ranges)
- Optimizes dashboard statistics calculations
- Enables efficient filtering by class, subject, teacher, and student

**Trade-offs:**
- Indexes use additional storage space (acceptable for project scale)
- Slight overhead on INSERT/UPDATE operations (minimal for this application's write volume)
- Overall benefit far outweighs the cost for read-heavy educational systems

---

## 7. User stories (Core functionality)

### 7.1 Authentication

| ID | User Story | Acceptance Criteria |
|----|------------|---------------------|
| US-001 | As a **user**, I want to log in with email and password | • Login form accepts email and password<br>• Valid credentials redirect to appropriate dashboard<br>• Invalid credentials show error message |
| US-002 | As a **user**, I want to log out securely | • Logout button available<br>• Session ends on logout<br>• Redirected to login page |

### 7.2 Master data management (Admin)

| ID | User Story | Acceptance Criteria |
|----|------------|---------------------|
| US-003 | As an **admin**, I want to create academic years | • Form has fields for name, semester, start/end dates<br>• Can mark one academic year as active<br>• List shows all academic years |
| US-004 | As an **admin**, I want to create classes | • Form has grade level (7-9), group (A-Z), homeroom teacher<br>• System prevents duplicate class combinations<br>• List shows all classes |
| US-005 | As an **admin**, I want to register students | • Form has NIS, name, gender, class<br>• NIS must be unique<br>• Student account is automatically created |
| US-006 | As an **admin**, I want to create subjects | • Form has subject name, teacher, class<br>• One teacher per subject-class combination<br>• List shows subjects grouped by class |
| US-007 | As an **admin**, I want to create teacher and student user accounts | • Form has name, email, password, role, gender<br>• Email must be unique<br>• Password is hashed before saving |

### 7.3 Learning activity recording (Teacher)

| ID | User Story | Acceptance Criteria |
|----|------------|---------------------|
| US-008 | As a **teacher**, I want to create a daily learning activity | • Form has date (default today), topic, notes, class, subject<br>• Only my assigned subjects are shown<br>• Activity is saved with my teacher ID |
| US-009 | As a **teacher**, I want to record attendance | • Student list is shown for selected class<br>• Can select: Hadir, Izin, Sakit, Alpa for each student<br>• Attendance is linked to the learning activity |
| US-010 | As a **teacher**, I want to assign participation scores | • Can enter score 1-5 for each student<br>• Field is optional<br>• Score is saved with activity details |
| US-011 | As a **teacher**, I want to input grades | • Can enter grade 0-100 for each student<br>• Validation prevents invalid values<br>• Grade is saved with activity details |
| US-012 | As a **teacher**, I want to add feedback notes for students | • Text area available for each student<br>• Notes are optional<br>• Notes are visible to student |
| US-013 | As a **teacher**, I want to view my learning activities | • Table shows activities sorted by date<br>• Can filter by class and subject<br>• Can click to view/edit details |

### 7.4 Student viewing (Student)

| ID | User Story | Acceptance Criteria |
|----|------------|---------------------|
| US-014 | As a **student**, I want to view my attendance history | • Table shows date, subject, status<br>• Status is color-coded<br>• Can filter by subject |
| US-015 | As a **student**, I want to view my grades | • Table shows date, subject, grade, participation<br>• Can filter by subject<br>• Average is calculated and displayed |
| US-016 | As a **student**, I want to see my dashboard | • Shows attendance percentage<br>• Shows average grade<br>• Shows list of subjects |

### 7.5 Reporting (Teacher & Admin)

| ID | User Story | Acceptance Criteria |
|----|------------|---------------------|
| US-017 | As a **teacher**, I want to generate a student report | • Can select student and date range<br>• Report shows attendance summary, grades, participation<br>• Can export to PDF |
| US-018 | As a **teacher**, I want to generate a class report | • Can select class, subject, date range<br>• Report shows all students in class<br>• Shows attendance and grade averages<br>• Can export to PDF |

### 7.6 Dashboard (All users)

| ID | User Story | Acceptance Criteria |
|----|------------|---------------------|
| US-019 | As an **admin**, I want to see system overview | • Dashboard shows total classes<br>• Shows total teachers<br>• Shows total students |
| US-020 | As a **teacher**, I want to see my teaching overview | • Dashboard shows my classes<br>• Shows recent activities<br>• Quick link to create new activity |
| US-021 | As a **student**, I want to see my learning summary | • Dashboard shows attendance percentage<br>• Shows average grade<br>• Shows recent activities |

---

## 8. Technology stack

### 8.1 Backend
- **Framework:** Laravel 12.x (PHP 8.3)
- **Admin Panel:** FilamentPHP 4.x (for Admin interface only)
- **Authentication:** Laravel built-in Auth with session-based authentication
- **Authorization:** Spatie Laravel Permission

### 8.2 Frontend

#### **Admin Interface (Desktop-only)**
- **UI Framework:** FilamentPHP 4.x
- **Styling:** TailwindCSS (bundled with Filament)
- **Reactivity:** Livewire 3.x (bundled with Filament)
- **JavaScript:** Alpine.js (included with Filament)
- **Charts:** FilamentPHP Chart Widgets (for admin dashboards)

#### **Teacher & Student Interfaces (Mobile-responsive)**
- **UI Library:** Flux UI (Official Livewire component library)
- **Reactivity:** Livewire 3.x
- **Styling:** TailwindCSS (with Flux UI components)
- **JavaScript:** Alpine.js (minimal, included with Livewire)
- **Icons:** Heroicons (built into Flux UI)

**Architecture Rationale:**
- **FilamentPHP:** Ideal for complex admin operations (CRUD, master data management) on desktop
- **Flux UI:** Mobile-first, lightweight components perfect for daily teacher/student operations
- **Hybrid approach:** Balances powerful admin tools with mobile-friendly user experiences

### 8.3 Database
- **RDBMS:** MySQL 8.0+
- **ORM:** Laravel Eloquent
- **Caching:** Report aggregation table (`laporan`) for performance

### 8.4 Additional packages
- **PDF Export:** DomPDF (for student/class reports)
- **Form Validation:** Laravel built-in validation
- **UI Components:** Livewire Flux (free tier sufficient)

### 8.5 Development environment
- **Local Server:** Laragon (Windows) / Laravel Valet (Mac) / Docker
- **Version Control:** Git
- **Package Manager:** Composer, NPM
- **Testing:** Pest (browser testing, faker, Livewire plugins)
- **Code Quality:** Laravel Pint (formatting), Larastan (static analysis)

---

## 9. UI architecture & mobile responsiveness

### 9.1 Design philosophy

SIPPEL employs a **hybrid architecture** to balance powerful desktop admin tools with mobile-friendly interfaces for daily operations:

**Admin Interface (Desktop-first):**
- Complex CRUD operations require larger screens
- FilamentPHP provides sophisticated data tables, bulk actions, and system configuration
- Admins typically work from office computers/laptops
- Desktop-only interface is acceptable for this user group

**Teacher & Student Interfaces (Mobile-first):**
- Daily operations (attendance, grade entry, viewing progress) done on smartphones
- Flux UI provides touch-friendly, responsive components
- Card-based layouts stack vertically on mobile devices
- Large touch targets (44px minimum) for easy thumb navigation
- Lightweight JavaScript bundle for faster loading on mobile networks

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
| **Use Case** | Master data CRUD | Daily operations |

### 9.3 Responsive design strategy

**Teacher Interface Example:**
```blade
<!-- Mobile-optimized attendance form with Flux UI -->
<flux:card>
    <flux:heading>Absensi Kelas 7A - Matematika</flux:heading>
    
    @foreach($students as $student)
        <div class="py-3 border-b"> <!-- Large touch target -->
            <flux:text class="font-medium">{{ $student->user->nama }}</flux:text>
            <flux:radio.group wire:model="attendance.{{ $student->id }}" variant="segmented">
                <flux:radio value="hadir">Hadir</flux:radio>
                <flux:radio value="izin">Izin</flux:radio>
                <flux:radio value="sakit">Sakit</flux:radio>
                <flux:radio value="alpa">Alpa</flux:radio>
            </flux:radio.group>
        </div>
    @endforeach
    
    <flux:button wire:click="save" variant="primary" class="w-full mt-4">
        Simpan Absensi
    </flux:button>
</flux:card>
```

**Student Interface Example:**
```blade
<!-- Mobile-friendly dashboard with Flux UI -->
<div class="space-y-4">
    <flux:card>
        <flux:badge variant="solid" icon="check-circle" class="mb-2">
            Status Hari Ini: Hadir
        </flux:badge>
        <flux:text class="text-sm text-zinc-500">
            Senin, 2 November 2025
        </flux:text>
    </flux:card>
    
    <flux:card>
        <flux:heading size="lg">Nilai Terakhir</flux:heading>
        <div class="mt-3 space-y-2">
            <flux:text>Matematika: <strong>85</strong></flux:text>
            <flux:text>Bahasa Indonesia: <strong>90</strong></flux:text>
            <flux:text>IPA: <strong>88</strong></flux:text>
        </div>
    </flux:card>
    
    <flux:button icon="document-arrow-down" class="w-full">
        Download Laporan
    </flux:button>
</div>
```

### 9.4 Implementation timeline impact

**Additional Development Time:**
- Install Flux UI: ~1 hour
- Design teacher mobile layouts: ~4 hours
- Build teacher interface with Flux: ~6 hours
- Build student interface with Flux: ~4 hours
- Testing on actual devices: ~3 hours
- **Total: ~18 hours** (easily fits within 3-4 month timeline)

**Time Saved:**
- ❌ No need to fight FilamentPHP mobile limitations
- ❌ No custom CSS hacks for responsive tables
- ❌ No complex Alpine.js for mobile adaptations
- ✅ Components work out of the box on mobile

**Note:** Detailed implementation phases are documented separately in `implementation-phases/` directory

---

## 10. Database performance best practices

### 10.1 Query optimization guidelines

**Must implement:**

1. **Use Eager Loading** to prevent N+1 query problems:
   ```php
   // Good - Eager loading
   $activities = AktivitasPembelajaran::with(['kelas', 'mataPelajaran', 'guru'])->get();
   
   // Bad - N+1 problem
   $activities = AktivitasPembelajaran::all();
   foreach ($activities as $activity) {
       echo $activity->kelas->nama; // Triggers separate query each time
   }
   ```

2. **Use select() to fetch only needed columns:**
   ```php
   // Good - Only needed columns
   User::select('id', 'nama', 'email')->where('role', 'teacher')->get();
   
   // Avoid - Fetching all columns unnecessarily
   User::all();
   ```

3. **Use indexes for frequently queried columns:**
   ```php
   // Ensure migrations have proper indexes
   $table->index('tanggal'); // For date-based queries
   $table->index(['kelas_id', 'tanggal']); // Composite index for common query patterns
   ```

4. **Limit result sets when appropriate:**
   ```php
   // Good - Paginate or limit
   AktivitasPembelajaran::latest()->paginate(20);
   
   // Bad - Fetching thousands of records
   AktivitasPembelajaran::all();
   ```

###10.2 Index management

**Ensure these indexes exist** (verify with `SHOW INDEX FROM table_name`):

| Table | Index Type | Columns | Purpose |
|-------|------------|---------|---------|
| `users` | UNIQUE | `email` | Fast login lookups |
| `tahun_ajaran` | UNIQUE | `nama_tahun`, `semester` | Prevent duplicates |
| `tahun_ajaran` | INDEX | `status` | Filter active year |
| `kelas` | FOREIGN KEY | `wali_kelas_id` | Join to users |
| `kelas` | FOREIGN KEY | `tahun_ajaran_id` | Join to academic years |
| `kelas` | COMPOSITE UNIQUE | `tingkat_kelas`, `grup_kelas`, `tahun_ajaran_id` | Prevent duplicate classes |
| `siswa` | UNIQUE | `nis` | Student ID lookups |
| `siswa` | FOREIGN KEY | `user_id`, `kelas_id` | Joins |
| `mata_pelajaran` | FOREIGN KEY | `guru_id`, `kelas_id` | Joins |
| `aktivitas_pembelajaran` | INDEX | `tanggal` | Date filtering |
| `aktivitas_pembelajaran` | COMPOSITE | `kelas_id`, `tanggal` | Common query pattern |
| `aktivitas_pembelajaran` | FOREIGN KEY | All FKs | Joins |
| `detail_aktivitas` | INDEX | `kehadiran` | Attendance reports |
| `detail_aktivitas` | COMPOSITE | `siswa_id`, `aktivitas_pembelajaran_id` | Student activity lookup |
| `laporan` | COMPOSITE UNIQUE | `siswa_id`, `mata_pelajaran_id`, `tahun_ajaran_id` | One report per combination |

### 10.3 Query performance testing

**Use Laravel Debugbar** (already installed in boilerplate) to monitor:
- Number of queries per page load (aim for < 20)
- Query execution time (aim for < 100ms per query)
- N+1 query detection

**Testing commands:**
```bash
# Enable Debugbar in .env
DEBUGBAR_ENABLED=true

# Run performance tests
php artisan test --filter=PerformanceTest
```

---

## 11. Success criteria

### 11.1 Functional completeness

The project is considered complete when:
- ✅ All 33 functional requirements (FR-001 to FR-033) are implemented
- ✅ All 21 user stories (US-001 to US-021) work as specified
- ✅ Three user roles function with proper permissions
- ✅ Teachers can record activities and generate reports
- ✅ Students can view their progress
- ✅ Admin can manage all master data

### 11.2 Technical requirements

- ✅ Application runs without critical errors
- ✅ Database structure matches specification with all required indexes
- ✅ All foreign keys have proper indexes
- ✅ All forms have proper validation
- ✅ Security measures implemented (password hashing, CSRF protection)
- ✅ Interface is responsive on different screen sizes (Flux UI for Teacher/Student)
- ✅ All text is in Indonesian language
- ✅ Queries use eager loading to prevent N+1 problems

### 11.3 Documentation

- ✅ Database schema documentation
- ✅ User manual (how to use the system)
- ✅ Installation guide
- ✅ Source code comments for complex logic

### 11.4 Demonstration

For final presentation, system should demonstrate:
- ✅ Complete user flow: Admin creates data → Teacher records activity → Student views progress
- ✅ Report generation and PDF export
- ✅ Dashboard statistics and calculations
- ✅ Responsive design on mobile devices (Teacher/Student interfaces)

---

## 12. Optional enhancements (Bonus features)

If time permits, these features can be added for additional credit:

### 12.1 Nice to have
- [ ] Bulk student import via CSV/Excel
- [ ] Excel export (in addition to PDF)
- [ ] Advanced filtering on data tables
- [ ] Edit history/audit trail
- [ ] Profile photo upload for users
- [ ] Print-friendly report layouts
- [ ] Progressive Web App (PWA) features (offline mode, install on home screen)

### 12.2 Advanced
- [ ] Email notifications (e.g., low attendance alert)
- [ ] Chart visualizations on dashboards
- [ ] Automated backup functionality
- [ ] Password reset via email
- [ ] Multi-language support (English + Indonesian)
- [ ] Push notifications for mobile users
- [ ] Parent/guardian portal

---

## 13. References & resources

### 13.1 Official documentation
- Laravel: https://laravel.com/docs
- FilamentPHP: https://filamentphp.com/docs
- Flux UI: https://fluxui.dev/docs
- Livewire: https://livewire.laravel.com/docs
- Spatie Permission: https://spatie.be/docs/laravel-permission

### 13.2 Development resources
- Laragon (local development): https://laragon.org
- HeidiSQL (database management): https://www.heidisql.com
- Composer (package manager): https://getcomposer.org
- Pest (testing framework): https://pestphp.com

### 13.3 UI/UX resources
- Heroicons (icon library): https://heroicons.com
- Tailwind CSS: https://tailwindcss.com/docs
- Mobile-first design principles

---

## 14. Project submission checklist

### 14.1 Code & documentation
- [ ] Source code uploaded to repository
- [ ] Database SQL file included
- [ ] Installation guide (README.md)
- [ ] User manual (PDF)
- [ ] Database schema diagram
- [ ] Implementation phases documentation

### 14.2 Demo preparation
- [ ] Test data seeded (3 classes, 30 students, 20+ activities)
- [ ] All user roles tested
- [ ] Reports generated successfully
- [ ] Mobile responsiveness tested on actual devices
- [ ] Screenshots/screen recordings prepared

### 14.3 Presentation materials
- [ ] PowerPoint slides prepared
- [ ] Live demo environment ready
- [ ] Backup demo video (in case of technical issues)
- [ ] Q&A preparation

---

## 15. Timeline summary

**Total Duration:** 3-4 months (~190 hours)

**Phase Breakdown:**
1. Foundation (Weeks 1-2): 15 hours
2. Master Data (Weeks 3-4): 30 hours
3. Core Functionality (Weeks 5-7): 40 hours
4. Student Interface (Weeks 8-9): 20 hours
5. Reporting (Weeks 10-11): 25 hours
6. Dashboards & Polish (Weeks 12-13): 20 hours
7. Testing & Documentation (Weeks 14-15): 25 hours
8. Final Submission (Week 16): 15 hours

**Key Milestones:**
- Week 2: Database and models complete
- Week 4: All master data resources functional
- Week 7: Teachers can record activities
- Week 9: Students can view progress
- Week 11: PDF reports working
- Week 13: All dashboards complete
- Week 15: Testing and documentation done
- Week 16: Final presentation

**Note:** Detailed implementation phases with task breakdowns are in `implementation-phases/` directory.

---

**Document End**
- [ ] **1.2.4** Publish Spatie config: `php artisan vendor:publish --provider="Spatie\Permission\PermissionServiceProvider"`
- [ ] **1.2.5** Install DomPDF for reports: `composer require barryvdh/laravel-dompdf`
- [ ] **1.2.6** Publish DomPDF config (optional): `php artisan vendor:publish --provider="Barryvdh\DomPDF\ServiceProvider"`

#### Task 1.3: Database migrations (Core tables)
- [ ] **1.3.1** Modify existing User model migration (`0001_01_01_000000_create_users_table.php`):
  - Change `name` to `nama` (or keep as `name` and add accessor)
  - Add field: `jenis_kelamin` (enum: 'L', 'P')
  - Note: Email unique index already exists ✅
  - Note: 2FA fields already exist (`app_authentication_secret`, `app_authentication_recovery_codes`) ✅
- [ ] **1.3.2** Create `tahun_ajaran` migration: `php artisan make:migration create_tahun_ajaran_table`
  - Fields: `nama_tahun`, `semester`, `tanggal_mulai`, `tanggal_selesai`, `status`
  - Indexes: PRIMARY, UNIQUE(`nama_tahun`), INDEX(`status`)
- [ ] **1.3.3** Create `kelas` migration: `php artisan make:migration create_kelas_table`
  - Fields: `tingkat_kelas`, `grup_kelas`, `wali_kelas_id`, `tahun_ajaran_id`
  - Indexes: PRIMARY, FK(`wali_kelas_id`), FK(`tahun_ajaran_id`), COMPOSITE(`tingkat_kelas`, `grup_kelas`)
- [ ] **1.3.4** Create `siswa` migration: `php artisan make:migration create_siswa_table`
  - Fields: `nis`, `user_id`, `kelas_id`
  - Indexes: PRIMARY, UNIQUE(`nis`), FK(`user_id`), FK(`kelas_id`)
- [ ] **1.3.5** Create `mata_pelajaran` migration: `php artisan make:migration create_mata_pelajaran_table`
  - Fields: `nama_mapel`, `guru_id`, `kelas_id`
  - Indexes: PRIMARY, FK(`guru_id`), FK(`kelas_id`), INDEX(`kelas_id`, `guru_id`)
- [ ] **1.3.6** Create `aktivitas_pembelajaran` migration: `php artisan make:migration create_aktivitas_pembelajaran_table`
  - Fields: `tanggal`, `topik`, `catatan`, `kelas_id`, `mata_pelajaran_id`, `guru_id`
  - Indexes: PRIMARY, FK(all), INDEX(`tanggal`), COMPOSITE(`kelas_id`, `tanggal`)
- [ ] **1.3.7** Create `detail_aktivitas` migration: `php artisan make:migration create_detail_aktivitas_table`
  - Fields: `kehadiran`, `nilai`, `partisipasi`, `catatan`, `aktivitas_pembelajaran_id`, `siswa_id`
  - Indexes: PRIMARY, FK(both), INDEX(`kehadiran`), COMPOSITE(`siswa_id`, `aktivitas_pembelajaran_id`)
- [ ] **1.3.8** Run migrations: `php artisan migrate`
- [ ] **1.3.9** Verify indexes: `SHOW INDEX FROM [table_name]` for each table

#### Task 1.4: Create Eloquent models
- [ ] **1.4.1** Create `TahunAjaran` model: `php artisan make:model TahunAjaran`
  - Add relationships: `hasMany('kelas')`
  - Protected `$fillable`, `$casts` (dates)
- [ ] **1.4.2** Create `Kelas` model: `php artisan make:model Kelas`
  - `belongsTo('tahunAjaran')`
  - `belongsTo('waliKelas', User::class, 'wali_kelas_id')`
  - `hasMany('siswa')`
  - `hasMany('mataPelajaran')`
- [ ] **1.4.3** Create `Siswa` model: `php artisan make:model Siswa`
  - `belongsTo('user')`
  - `belongsTo('kelas')`
  - `hasMany('detailAktivitas')`
- [ ] **1.4.4** Create `MataPelajaran` model: `php artisan make:model MataPelajaran`
  - `belongsTo('guru', User::class, 'guru_id')`
  - `belongsTo('kelas')`
  - `hasMany('aktivitasPembelajaran')`
- [ ] **1.4.5** Create `AktivitasPembelajaran` model: `php artisan make:model AktivitasPembelajaran`
  - `belongsTo('kelas')`
  - `belongsTo('mataPelajaran')`
  - `belongsTo('guru', User::class, 'guru_id')`
  - `hasMany('detailAktivitas')`
- [ ] **1.4.6** Create `DetailAktivitas` model: `php artisan make:model DetailAktivitas`
  - `belongsTo('aktivitasPembelajaran')`
  - `belongsTo('siswa')`
- [ ] **1.4.7** Update existing `User` model (`app/Models/User.php`):
  - Add `hasOne('siswa')` relationship
  - Add `hasMany('kelasAsWali', Kelas::class, 'wali_kelas_id')` relationship
  - Add `hasMany('mataPelajaranAsGuru', MataPelajaran::class, 'guru_id')` relationship
  - Note: Keep existing Filament auth traits ✅

#### Task 1.5: Authentication and authorization setup
- [ ] **1.5.1** ✅ **SKIP** - Basic authentication already configured in boilerplate
- [ ] **1.5.2** Run Spatie migration: `php artisan migrate` (for roles/permissions tables)
- [ ] **1.5.3** Create database seeder: `php artisan make:seeder RolePermissionSeeder`
  - Create 3 roles: 'admin', 'teacher', 'student'
  - Assign basic permissions (can be expanded later)
- [ ] **1.5.4** Add `HasRoles` trait to User model:
  ```php
  use Spatie\Permission\Traits\HasRoles;
  
  class User extends Authenticatable {
      use HasFactory, Notifiable, HasRoles;
  ```
- [ ] **1.5.5** Create seeder: `php artisan make:seeder UserSeeder` for test accounts:
  - Admin: `admin@sippel.sch.id` / password: `admin123`
  - Teacher: `teacher@sippel.sch.id` / password: `teacher123`
  - Student: `student@sippel.sch.id` / password: `student123`
- [ ] **1.5.6** Update `DatabaseSeeder.php` to call both seeders
- [ ] **1.5.7** Run seeders: `php artisan db:seed`
- [ ] **1.5.8** Test login with each account type at `/admin`

#### Task 1.6: Create Filament panels for each role
- [ ] **1.6.1** ✅ **SKIP** - Admin panel already exists and configured in `AdminPanelProvider.php`
- [ ] **1.6.2** Update Admin panel access control:
  - Modify `canAccessPanel()` in User model to check role: `$this->hasRole('admin')`
  - Or keep in `AdminPanelProvider` with `->authGuard()` and middleware
- [ ] **1.6.3** Disable 2FA for simplicity (optional):
  - Remove or comment out `multiFactorAuthentication()` in `AdminPanelProvider.php`
  - Or keep it for admin panel only
- [ ] **1.6.4** Update Admin panel navigation groups in `AdminPanelProvider.php`:
  - Add: `->navigationGroups(['Master Data', 'Manajemen'])` or configure per resource
- [ ] **1.6.5** Create Teacher panel: `php artisan make:filament-panel teacher`
  - Path: `/teacher`
  - Configure in generated `TeacherPanelProvider.php`:
    ```php
    ->id('teacher')
    ->path('teacher')
    ->login()
    ->profile()
    ->authGuard('web')
    ->colors(['primary' => Color::Blue])
    ```
  - Add middleware for role check or use `canAccessPanel()` with `hasRole('teacher')`
  - Add navigation groups: "Pembelajaran", "Laporan"
- [ ] **1.6.6** Create Student panel: `php artisan make:filament-panel student`
  - Path: `/student`
  - Configure in generated `StudentPanelProvider.php`:
    ```php
    ->id('student')
    ->path('student')
    ->login()
    ->profile()
    ->authGuard('web')
    ->colors(['primary' => Color::Green])
    ```
  - Add middleware for role check or use `canAccessPanel()` with `hasRole('student')`
  - Navigation: "Dashboard", "Data Saya"
- [ ] **1.6.7** Customize login pages for each panel (Indonesian labels):
  - Create custom login pages if needed or use Filament's translation
- [ ] **1.6.8** Test panel access and role-based redirects:
  - Admin can access `/admin`
  - Teacher can access `/teacher`
  - Student can access `/student`
  - Each role is blocked from other panels

---

### Phase 2: Master data management (Week 3-4)

#### Task 2.1: Academic year resource (Admin only)
- [ ] **2.1.1** Generate resource: `php artisan make:filament-resource TahunAjaran --panel=admin`
- [ ] **2.1.2** Configure form schema:
  - TextInput: `nama_tahun` (required, unique, max:50)
  - Select: `semester` (options: 'Ganjil', 'Genap')
  - DatePicker: `tanggal_mulai`, `tanggal_selesai` (required)
  - Toggle: `status` (default: false, only one can be active)
- [ ] **2.1.3** Configure table columns:
  - TextColumn: `nama_tahun`, `semester`, `tanggal_mulai`, `tanggal_selesai`
  - BadgeColumn: `status` (Active/Inactive with colors)
- [ ] **2.1.4** Add table filter: Filter by `status` and `semester`
- [ ] **2.1.5** Implement logic: Only one academic year can have `status = true`
  - Use model observer or form action
- [ ] **2.1.6** Add bulk actions: Activate/Deactivate
- [ ] **2.1.7** Test CRUD operations and validation

#### Task 2.2: Class resource (Admin only)
- [ ] **2.2.1** Generate resource: `php artisan make:filament-resource Kelas --panel=admin`
- [ ] **2.2.2** Configure form schema:
  - Select: `tingkat_kelas` (options: 7, 8, 9)
  - Select: `grup_kelas` (options: A-Z, use helper for array generation)
  - Select: `wali_kelas_id` (relationship, searchable, only teachers)
  - Select: `tahun_ajaran_id` (relationship, only active academic year by default)
- [ ] **2.2.3** Configure table columns:
  - TextColumn: Combined display "7A", "8B" (accessor or custom column)
  - TextColumn: `waliKelas.name` (relationship)
  - TextColumn: `tahunAjaran.nama_tahun`
- [ ] **2.2.4** Add table filters: Filter by `tingkat_kelas`, `tahun_ajaran_id`
- [ ] **2.2.5** Add validation: Prevent duplicate `tingkat_kelas + grup_kelas` combination per academic year
- [ ] **2.2.6** Test CRUD and check homeroom teacher assignment

#### Task 2.3: Student resource (Admin only)
- [ ] **2.3.1** Generate resource: `php artisan make:filament-resource Siswa --panel=admin`
- [ ] **2.3.2** Configure form schema (Wizard or Tabs):
  - **Step 1 - Student Data:**
    - TextInput: `nis` (required, unique, numeric, length: 10)
  - **Step 2 - User Account:**
    - TextInput: `nama` (required, max:100)
    - TextInput: `email` (required, email, unique)
    - PasswordInput: `password` (required on create, min:8)
    - Select: `jenis_kelamin` (options: 'L' => 'Laki-laki', 'P' => 'Perempuan')
  - **Step 3 - Class Assignment:**
    - Select: `kelas_id` (relationship, searchable, show "7A - 2025/2026")
- [ ] **2.3.3** Implement create logic:
  - Create User first with role 'student'
  - Create Siswa record linked to User
  - Use database transaction for atomicity
- [ ] **2.3.4** Configure table columns:
  - TextColumn: `nis`, `user.nama`, `user.email`
  - TextColumn: `kelas` (combined display)
  - BadgeColumn: `user.jenis_kelamin`
- [ ] **2.3.5** Add table filters: Filter by `kelas_id`, `jenis_kelamin`
- [ ] **2.3.6** Add search: By NIS, name, email
- [ ] **2.3.7** Add bulk actions: Assign to class, Export to Excel
- [ ] **2.3.8** Test student creation and user account generation

#### Task 2.4: Subject resource (Admin only)
- [ ] **2.4.1** Generate resource: `php artisan make:filament-resource MataPelajaran --panel=admin`
- [ ] **2.4.2** Configure form schema:
  - TextInput: `nama_mapel` (required, max:100)
  - Select: `kelas_id` (relationship, searchable, show "7A - 2025/2026")
  - Select: `guru_id` (relationship, searchable, only users with role 'teacher')
- [ ] **2.4.3** Configure table columns:
  - TextColumn: `nama_mapel`
  - TextColumn: `kelas` (combined display)
  - TextColumn: `guru.nama`
- [ ] **2.4.4** Add table filters: Filter by `kelas_id`
- [ ] **2.4.5** Group table by class: Use `->groupedBulkActions()` or custom grouping
- [ ] **2.4.6** Add validation: One teacher per subject-class combination
- [ ] **2.4.7** Test subject creation and teacher assignment

#### Task 2.5: User management resource (Admin only)
- [ ] **2.5.1** Generate resource: `php artisan make:filament-resource User --panel=admin`
- [ ] **2.5.2** Configure form schema:
  - TextInput: `nama` (required)
  - TextInput: `email` (required, email, unique)
  - PasswordInput: `password` (required on create, min:8)
  - Select: `jenis_kelamin` (required)
  - Select: `role` (options: admin, teacher, student - assign via Spatie)
- [ ] **2.5.3** Configure table columns:
  - TextColumn: `nama`, `email`
  - BadgeColumn: `roles.name` (via Spatie relationship)
  - BadgeColumn: `jenis_kelamin`
  - ToggleColumn: `is_active` (if implementing soft deactivation)
- [ ] **2.5.4** Add table filters: Filter by role
- [ ] **2.5.5** Implement role assignment on create/update
- [ ] **2.5.6** Add bulk actions: Deactivate accounts
- [ ] **2.5.7** Test user creation for all roles

#### Task 2.6: Index verification and optimization
- [ ] **2.6.1** Run `SHOW INDEX FROM users;` - verify email unique index
- [ ] **2.6.2** Run `SHOW INDEX FROM tahun_ajaran;` - verify nama_tahun unique, status index
- [ ] **2.6.3** Run `SHOW INDEX FROM kelas;` - verify all FK indexes and composite
- [ ] **2.6.4** Run `SHOW INDEX FROM siswa;` - verify NIS unique, FK indexes
- [ ] **2.6.5** Run `SHOW INDEX FROM mata_pelajaran;` - verify FK indexes
- [ ] **2.6.6** Test query performance with Laravel Debugbar
- [ ] **2.6.7** Verify eager loading in Filament resources (check query count)

---

### Phase 3: Core functionality - Learning activities (Week 5-7)

#### Task 3.1: Learning activity resource (Teacher panel)
- [ ] **3.1.1** Generate resource: `php artisan make:filament-resource AktivitasPembelajaran --panel=teacher`
- [ ] **3.1.2** Configure form schema - Step 1 (Activity Info):
  - DatePicker: `tanggal` (default: today, required)
  - Select: `mata_pelajaran_id` (only subjects assigned to logged-in teacher)
  - Select: `kelas_id` (auto-filled from selected subject's class, disabled)
  - TextInput: `topik` (required, max:200)
  - Textarea: `catatan` (optional, max:500)
- [ ] **3.1.3** Add scope to MataPelajaran select:
  - Filter by `guru_id = auth()->id()`
  - Use `->relationship()` with query modification
- [ ] **3.1.4** Configure table columns:
  - TextColumn: `tanggal` (date format: 'd M Y')
  - TextColumn: `mataPelajaran.nama_mapel`
  - TextColumn: `kelas` (combined display)
  - TextColumn: `topik`
  - TextColumn: `detailAktivitas_count` (student count)
- [ ] **3.1.5** Add table filters:
  - Filter by `mata_pelajaran_id`
  - Filter by `tanggal` (date range)
- [ ] **3.1.6** Default sort: `tanggal DESC`
- [ ] **3.1.7** Test activity creation by teacher

#### Task 3.2: Attendance recording (Repeater/Table in form)
- [ ] **3.2.1** Add form schema - Step 2 (Attendance & Grades):
  - Use Repeater or TableRepeater component
  - Load all students from selected class automatically
  - For each student row:
    - Hidden: `siswa_id`
    - TextInput: `nama` (disabled, display only)
    - Select: `kehadiran` (options: Hadir, Izin, Sakit, Alpa)
    - TextInput: `nilai` (numeric, min:0, max:100, nullable)
    - Select: `partisipasi` (options: 1-5, nullable)
    - Textarea: `catatan` (nullable)
- [ ] **3.2.2** Implement auto-population logic:
  - On `mata_pelajaran_id` change, fetch all students from class
  - Pre-fill repeater with student list
  - Set default `kehadiran` to 'Hadir'
- [ ] **3.2.3** Add form action to save all details:
  - Create `AktivitasPembelajaran` record
  - Create `DetailAktivitas` records for all students
  - Use database transaction
- [ ] **3.2.4** Add validation:
  - At least one student must have data
  - `nilai` must be 0-100 if provided
  - `partisipasi` must be 1-5 if provided
- [ ] **3.2.5** Test bulk attendance recording

#### Task 3.3: Edit functionality for activities
- [ ] **3.3.1** Configure edit form:
  - Load existing `DetailAktivitas` records
  - Pre-fill repeater with existing data
  - Allow editing all fields
- [ ] **3.3.2** Implement update logic:
  - Update `AktivitasPembelajaran` record
  - Update existing `DetailAktivitas` records
  - Use `updateOrCreate` for efficiency
- [ ] **3.3.3** Add view action: Display activity details in modal
- [ ] **3.3.4** Test edit and update functionality

#### Task 3.4: Automatic calculations (Model accessors/scopes)
- [ ] **3.4.1** Create `Siswa` model method: `getAttendancePercentageAttribute()`
  - Calculate: (Total 'Hadir' / Total activities) × 100
  - Use eager loading to prevent N+1
- [ ] **3.4.2** Create `Siswa` model method: `getAverageGradeAttribute()`
  - Calculate: SUM(nilai) / COUNT(nilai) per subject
  - Filter out null grades
- [ ] **3.4.3** Create `Siswa` model method: `getAverageParticipationAttribute()`
  - Calculate: SUM(partisipasi) / COUNT(partisipasi)
  - Filter out null participation
- [ ] **3.4.4** Test calculations with sample data
- [ ] **3.4.5** Verify query performance (use `with()` for relationships)

#### Task 3.5: Activity list and management
- [ ] **3.5.1** Add search functionality: Search by topic, date
- [ ] **3.5.2** Add bulk actions: Delete multiple activities
- [ ] **3.5.3** Add custom action: Duplicate activity (copy to new date)
- [ ] **3.5.4** Add summary widget above table:
  - Total activities this month
  - Average class attendance
  - Most active subject
- [ ] **3.5.5** Test all teacher workflows end-to-end

---

### Phase 4: Student interface (Week 8-9)

#### Task 4.1: Student dashboard
- [ ] **4.1.1** Create custom dashboard page: `php artisan make:filament-page Dashboard --type=custom --panel=student`
- [ ] **4.1.2** Create stat widgets:
  - **Attendance percentage** (use Siswa model accessor)
  - **Average grade** (overall, from all subjects)
  - **Total activities** (count of DetailAktivitas records)
- [ ] **4.1.3** Add chart widget (optional):
  - Line chart: Grades over time
  - Use FilamentPHP ChartWidget
- [ ] **4.1.4** Display recent activities (latest 5):
  - Table or list with date, subject, attendance, grade
- [ ] **4.1.5** Add quick links: "Lihat Kehadiran", "Lihat Nilai"
- [ ] **4.1.6** Test dashboard with student account

#### Task 4.2: Attendance history view
- [ ] **4.2.1** Create custom page or resource: `RiwayatKehadiran`
- [ ] **4.2.2** Configure table:
  - Columns: `tanggal`, `mataPelajaran.nama_mapel`, `kehadiran`, `topik`
  - Use `DetailAktivitas` model with relationships
  - Filter by logged-in student: `siswa_id = auth()->user()->siswa->id`
- [ ] **4.2.3** Add filters:
  - Filter by `mata_pelajaran_id`
  - Filter by `kehadiran` status
  - Date range filter
- [ ] **4.2.4** Add color coding for attendance:
  - Hadir: Green badge
  - Izin: Blue badge
  - Sakit: Yellow badge
  - Alpa: Red badge
- [ ] **4.2.5** Add summary stats above table:
  - Total Hadir, Izin, Sakit, Alpa
  - Percentage for each status
- [ ] **4.2.6** Test attendance history view

#### Task 4.3: Grade history view
- [ ] **4.3.1** Create custom page or resource: `RiwayatNilai`
- [ ] **4.3.2** Configure table:
  - Columns: `tanggal`, `mataPelajaran.nama_mapel`, `nilai`, `partisipasi`, `catatan`
  - Filter by logged-in student
- [ ] **4.3.3** Add filters:
  - Filter by `mata_pelajaran_id`
  - Date range filter
- [ ] **4.3.4** Add summary stats per subject:
  - Average grade per subject
  - Highest/lowest grade
  - Average participation per subject
- [ ] **4.3.5** Add visual indicators:
  - Color-coded grades (e.g., < 60: red, 60-80: yellow, > 80: green)
  - Star icons for participation (1-5 stars)
- [ ] **4.3.6** Test grade history view

#### Task 4.4: Permissions and access control
- [ ] **4.4.1** Add policy for `DetailAktivitas`:
  - Student can only `view` their own records
  - Student cannot `create`, `update`, or `delete`
- [ ] **4.4.2** Add policy for `AktivitasPembelajaran`:
  - Student can only `view` activities from their class
- [ ] **4.4.3** Test unauthorized access attempts:
  - Student trying to access other student's data
  - Student trying to edit/delete records
- [ ] **4.4.4** Verify middleware on student panel routes

---

### Phase 5: Reporting (Week 10-11)

#### Task 5.1: Report layout design
- [ ] **5.1.1** Create Blade view: `resources/views/reports/student-report.blade.php`
  - Header: School logo, name, address
  - Student info: NIS, Name, Class
  - Date range: From - To
  - Sections: Attendance summary, Grades table, Participation summary
  - Footer: Generated date, signature placeholders
- [ ] **5.1.2** Create Blade view: `resources/views/reports/class-report.blade.php`
  - Header: School info, Class name, Subject
  - Table: Student list with attendance %, average grade, average participation
  - Summary: Class average, attendance rate
  - Footer: Teacher signature, date
- [ ] **5.1.3** Add CSS styling for print-friendly layout
- [ ] **5.1.4** Test views with sample data

#### Task 5.2: Student report generation (Teacher & Admin)
- [ ] **5.2.1** Create custom action in `AktivitasPembelajaran` resource:
  - Action: "Cetak Laporan Siswa"
  - Form: Select student, date range
- [ ] **5.2.2** Create controller method: `generateStudentReport()`
  - Query `DetailAktivitas` for selected student and date range
  - Calculate attendance percentage, average grades, average participation
  - Group data by subject
  - Return view with data
- [ ] **5.2.3** Add DomPDF integration:
  - Install: `composer require barryvdh/laravel-dompdf`
  - Configure PDF options (paper size: A4, orientation: portrait)
  - Generate PDF from Blade view
- [ ] **5.2.4** Add download action: Return PDF as download
- [ ] **5.2.5** Test report generation with various date ranges

#### Task 5.3: Class report generation (Teacher & Admin)
- [ ] **5.3.1** Create custom action in `Kelas` resource:
  - Action: "Cetak Laporan Kelas"
  - Form: Select subject, date range
- [ ] **5.3.2** Create controller method: `generateClassReport()`
  - Query all students in class
  - For each student, calculate metrics for selected subject and date range
  - Calculate class averages
  - Return view with data
- [ ] **5.3.3** Add sorting options: Sort by name, attendance, or grade
- [ ] **5.3.4** Add PDF export using DomPDF
- [ ] **5.3.5** Test class report with full class data

#### Task 5.4: Report access in Student panel
- [ ] **5.4.1** Add custom page in Student panel: "Laporan Saya"
- [ ] **5.4.2** Create form:
  - Select: Subject (optional, default: all subjects)
  - DatePicker: Date range (default: current semester)
- [ ] **5.4.3** Display report preview (HTML)
- [ ] **5.4.4** Add "Download PDF" button
- [ ] **5.4.5** Test student self-report generation

#### Task 5.5: Report accuracy testing
- [ ] **5.5.1** Create test data: 3 students, 10 activities each
- [ ] **5.5.2** Manually calculate expected values
- [ ] **5.5.3** Generate reports and verify calculations match
- [ ] **5.5.4** Test edge cases:
  - Student with no activities
  - Student with all absences
  - Student with missing grades
- [ ] **5.5.5** Fix any calculation discrepancies

---

### Phase 6: Dashboards & polish (Week 12-13)

#### Task 6.1: Admin dashboard enhancements
- [ ] **6.1.1** Create stat widgets:
  - Total classes (current academic year)
  - Total teachers (users with role 'teacher')
  - Total students (current academic year)
  - Total activities (this month)
- [ ] **6.1.2** Add chart widgets:
  - Bar chart: Activities per subject (this month)
  - Line chart: Daily activity count (last 30 days)
- [ ] **6.1.3** Add table widget: Recent activities (latest 10)
- [ ] **6.1.4** Add quick action buttons:
  - "Tambah Tahun Ajaran"
  - "Tambah Kelas"
  - "Tambah Siswa"
- [ ] **6.1.5** Test admin dashboard performance

#### Task 6.2: Teacher dashboard enhancements
- [ ] **6.2.1** Create stat widgets:
  - Classes taught (count)
  - Activities this week (count)
  - Students taught (total unique students)
- [ ] **6.2.2** Display subject list:
  - Card or table showing all assigned subjects
  - With class name and student count
- [ ] **6.2.3** Add recent activities table (latest 5)
  - With quick edit action
- [ ] **6.2.4** Add quick action button: "Buat Aktivitas Baru"
- [ ] **6.2.5** Add reminder widget (optional):
  - "Classes without activities this week"
- [ ] **6.2.6** Test teacher dashboard

#### Task 6.3: Student dashboard finalization
- [ ] **6.3.1** Refine stat widgets styling
- [ ] **6.3.2** Add progress indicators:
  - Attendance rate with progress bar
  - Grade average with color indicator
- [ ] **6.3.3** Add subject breakdown table:
  - List of subjects with individual stats
- [ ] **6.3.4** Add motivational messages based on performance
- [ ] **6.3.5** Test student dashboard responsiveness

#### Task 6.4: UI/UX improvements
- [ ] **6.4.1** Customize Filament theme colors:
  - Primary color: School theme color
  - Update `resources/css/filament/admin/theme.css`
- [ ] **6.4.2** Add school logo to navigation bar
- [ ] **6.4.3** Translate all remaining English labels to Indonesian:
  - Check all forms, tables, filters
  - Update Filament language files if needed
- [ ] **6.4.4** Improve form layouts:
  - Use sections, tabs, or wizards for long forms
  - Add helpful hints and placeholders
- [ ] **6.4.5** Add confirmation dialogs for destructive actions
- [ ] **6.4.6** Test UI on different screen sizes

#### Task 6.5: Responsive testing
- [ ] **6.5.1** Test on desktop (1920x1080, 1366x768)
- [ ] **6.5.2** Test on tablet (768x1024)
- [ ] **6.5.3** Test on mobile (375x667, 414x896)
- [ ] **6.5.4** Fix layout issues:
  - Scrollable tables
  - Stacked forms on mobile
  - Hidden/collapsed navigation
- [ ] **6.5.5** Verify touch interactions on mobile

---

### Phase 7: Testing & documentation (Week 14-15)

#### Task 7.1: Functional testing checklist
- [ ] **7.1.1** Test all authentication flows:
  - Login (all roles)
  - Logout
  - Password validation
  - Unauthorized access attempts
- [ ] **7.1.2** Test Admin workflows:
  - Create academic year
  - Create class with homeroom teacher
  - Register student (verify user account creation)
  - Create subject assignments
  - Manage users
- [ ] **7.1.3** Test Teacher workflows:
  - Create learning activity
  - Record attendance for full class
  - Input grades and participation
  - Edit existing activity
  - Generate student report
  - Generate class report
- [ ] **7.1.4** Test Student workflows:
  - View dashboard
  - Check attendance history
  - Check grade history
  - Apply filters
  - Generate personal report
- [ ] **7.1.5** Test calculations:
  - Verify attendance percentage
  - Verify grade averages
  - Verify participation averages
  - Test with edge cases (missing data)
- [ ] **7.1.6** Test validations:
  - Duplicate NIS
  - Duplicate email
  - Invalid date ranges
  - Invalid grade values
  - Empty required fields

#### Task 7.2: Database performance testing
- [ ] **7.2.1** ✅ **SKIP** - Laravel Debugbar already installed in boilerplate
- [ ] **7.2.2** Enable Debugbar in `.env`: `DEBUGBAR_ENABLED=true`
- [ ] **7.2.3** Test query counts on each page:
  - Admin dashboard: Target < 10 queries
  - Teacher activity list: Target < 15 queries
  - Student grade history: Target < 10 queries
- [ ] **7.2.4** Identify and fix N+1 query problems:
  - Add `with()` to resource queries
  - Optimize relationship loading
- [ ] **7.2.5** Verify index usage with `EXPLAIN`:
  - Test query: Activities filtered by date
  - Test query: Students filtered by class
  - Test query: Details filtered by student
- [ ] **7.2.6** Measure page load times:
  - All pages should load < 3 seconds
  - Report generation < 5 seconds
- [ ] **7.2.7** Test with larger dataset:
  - Create 100 students, 50 activities
  - Verify performance remains acceptable

#### Task 7.3: Bug fixing
- [ ] **7.3.1** Create bug tracking document (spreadsheet or markdown)
- [ ] **7.3.2** Categorize bugs by severity: Critical, High, Medium, Low
- [ ] **7.3.3** Fix critical bugs first (system crashes, data loss)
- [ ] **7.3.4** Fix high-priority bugs (broken features, calculation errors)
- [ ] **7.3.5** Fix medium-priority bugs (UI issues, minor errors)
- [ ] **7.3.6** Document known low-priority issues (for future improvement)

#### Task 7.4: User documentation
- [ ] **7.4.1** Create `USER_MANUAL.md` with sections:
  - **1. Introduction:** System overview
  - **2. Getting Started:** Login instructions
  - **3. Admin Guide:**
    - How to create academic years
    - How to register students
    - How to assign teachers
    - How to manage users
  - **4. Teacher Guide:**
    - How to create learning activities
    - How to record attendance
    - How to input grades
    - How to generate reports
  - **5. Student Guide:**
    - How to view attendance
    - How to check grades
    - How to generate personal report
  - **6. FAQ:** Common questions and troubleshooting
- [ ] **7.4.2** Add screenshots for each major feature
- [ ] **7.4.3** Create quick reference cards (1-page PDFs) for each role

#### Task 7.5: Technical documentation
- [ ] **7.5.1** Create `INSTALLATION.md`:
  - Requirements (PHP, MySQL, Composer)
  - Installation steps
  - Configuration (database, environment)
  - Initial seeding
- [ ] **7.5.2** Create `DATABASE.md`:
  - ERD diagram (use dbdiagram.io or similar)
  - Table descriptions
  - Relationship explanations
  - Index documentation
- [ ] **7.5.3** Update `README.md`:
  - Project description
  - Features list
  - Technology stack
  - Installation guide link
  - Screenshots
  - Credits
- [ ] **7.5.4** Document code:
  - Add PHPDoc comments to complex methods
  - Add inline comments for non-obvious logic
  - Document custom helper functions

#### Task 7.6: Demo data preparation
- [ ] **7.6.1** Create comprehensive seeder: `DemoDataSeeder`
  - 2 academic years (one active)
  - 6 classes (2 per grade level)
  - 6 teachers
  - 60 students (10 per class)
  - 15 subjects (spread across classes)
  - 50 learning activities
  - 500+ detail records (attendance, grades, participation)
- [ ] **7.6.2** Ensure data is realistic:
  - Varied attendance patterns
  - Grade distribution (bell curve)
  - Complete and incomplete activities
- [ ] **7.6.3** Test all features with demo data
- [ ] **7.6.4** Prepare fresh database script for presentation

#### Task 7.7: Presentation preparation
- [ ] **7.7.1** Create PowerPoint/slides:
  - **Slide 1:** Title slide (SIPPEL, your name, date)
  - **Slide 2:** Problem statement
  - **Slide 3:** Solution overview
  - **Slide 4:** Technology stack
  - **Slide 5:** Database design (ERD)
  - **Slide 6:** Key features (with screenshots)
  - **Slide 7:** Demo outline
  - **Slide 8:** Challenges and solutions
  - **Slide 9:** Conclusion and future work
- [ ] **7.7.2** Prepare live demo script:
  - Admin: Create new class
  - Teacher: Record activity for that class
  - Student: View the recorded activity
  - Generate and show report
- [ ] **7.7.3** Practice presentation (target: 15-20 minutes)
- [ ] **7.7.4** Prepare answers for common questions

---

### Phase 8: Final submission (Week 16)

#### Task 8.1: Code review and cleanup
- [ ] **8.1.1** Remove unused files and code
- [ ] **8.1.2** Remove debug statements (dd(), dump(), console.log)
- [ ] **8.1.3** Clean up commented code
- [ ] **8.1.4** ✅ **AVAILABLE** - Run Laravel Pint for code formatting: `./vendor/bin/pint` or `composer pint`
- [ ] **8.1.5** ✅ **AVAILABLE** - Run Larastan for static analysis: `./vendor/bin/phpstan analyse` or `composer phpstan`
- [ ] **8.1.6** ✅ **AVAILABLE** - Run Rector for code quality: `./vendor/bin/rector process` or `composer rector`
- [ ] **8.1.7** ✅ **AVAILABLE** - Or run all quality checks: `composer review` (pint + rector + phpstan + pest)
- [ ] **8.1.8** Check for security issues:
  - No hardcoded credentials
  - No exposed API keys
  - Proper validation on all inputs
- [ ] **8.1.9** Verify `.env.example` is up-to-date
- [ ] **8.1.10** Update `composer.json` and `package.json` if needed

#### Task 8.2: Final testing
- [ ] **8.2.1** Fresh install test:
  - Clone project to new directory
  - Run installation steps from `INSTALLATION.md`
  - Verify everything works
- [ ] **8.2.2** Run demo data seeder: `php artisan db:seed --class=DemoDataSeeder`
- [ ] **8.2.3** Complete end-to-end workflow test:
  - Admin creates all master data
  - Teacher records activities for 2 weeks
  - Students view their data
  - Generate all report types
- [ ] **8.2.4** Cross-browser testing (Chrome, Firefox, Edge)
- [ ] **8.2.5** Performance check: All pages load < 3 seconds

#### Task 8.3: Documentation finalization
- [ ] **8.3.1** Review and proofread all documentation
- [ ] **8.3.2** Check all links and references
- [ ] **8.3.3** Verify screenshots are current
- [ ] **8.3.4** Add table of contents to long documents
- [ ] **8.3.5** Export documentation to PDF if required
- [ ] **8.3.6** Prepare documentation package (ZIP with all docs)

#### Task 8.4: Project packaging
- [ ] **8.4.1** Create submission folder structure:
  ```
  SIPPEL_FinalProject/
  ├── source_code/          (Full Laravel project)
  ├── documentation/
  │   ├── PRD_SIMPLIFIED.md
  │   ├── USER_MANUAL.md
  │   ├── INSTALLATION.md
  │   ├── DATABASE.md
  │   └── README.md
  ├── database/
  │   ├── sippel_empty.sql   (Schema only)
  │   └── sippel_demo.sql    (With demo data)
  ├── screenshots/          (System screenshots)
  └── presentation/
      └── SIPPEL_Presentation.pptx
  ```
- [ ] **8.4.2** Export clean database:
  - Schema only: `mysqldump -u root -p --no-data sippel_db > sippel_empty.sql`
  - With demo data: `mysqldump -u root -p sippel_db > sippel_demo.sql`
- [ ] **8.4.3** Capture screenshots of all major features
- [ ] **8.4.4** Create project README for submission folder
- [ ] **8.4.5** Compress submission folder to ZIP

#### Task 8.5: Deployment (Optional)
- [ ] **8.5.1** Choose hosting platform (e.g., shared hosting, VPS)
- [ ] **8.5.2** Upload files via FTP or Git
- [ ] **8.5.3** Configure production database
- [ ] **8.5.4** Update `.env` for production:
  - Set `APP_ENV=production`
  - Set `APP_DEBUG=false`
  - Configure production database credentials
- [ ] **8.5.5** Run migrations and seeders on production
- [ ] **8.5.6** Test deployed application
- [ ] **8.5.7** Configure domain (if available)
- [ ] **8.5.8** Document deployment process

#### Task 8.6: Final submission
- [ ] **8.6.1** Submit project according to institution guidelines
- [ ] **8.6.2** Submit source code (ZIP or GitHub repository link)
- [ ] **8.6.3** Submit documentation package
- [ ] **8.6.4** Submit presentation slides
- [ ] **8.6.5** Provide deployed URL (if applicable)
- [ ] **8.6.6** Schedule presentation/defense date

#### Task 8.7: Presentation day
- [ ] **8.7.1** Bring backup copy of project (USB drive)
- [ ] **8.7.2** Bring printed documentation (if required)
- [ ] **8.7.3** Test equipment before presentation
- [ ] **8.7.4** Have demo database ready to load
- [ ] **8.7.5** Deliver presentation confidently
- [ ] **8.7.6** Answer questions thoroughly
- [ ] **8.7.7** Thank reviewers and audience

---

## Summary of phase breakdown

**Total tasks:** 155+ detailed subtasks across 8 phases (reduced from 160+ due to boilerplate)

**Key principles applied:**
1. ✅ **Not overengineered** - Focus on core functionality, no unnecessary complexity
2. ✅ **Clean code** - Proper naming, documentation, validation
3. ✅ **Maintainable** - Clear structure, consistent patterns, documented decisions
4. ✅ **Extensible** - Modular design, relationships properly defined, easy to add features
5. ✅ **Boilerplate-aware** - Leverages existing setup, avoids redundant work

**Estimated effort distribution (adjusted for boilerplate):**
- Phase 1 (Foundation): **15 hours** (reduced from 25 - boilerplate saves 10 hours)
- Phase 2 (Master Data): 30 hours
- Phase 3 (Core Functionality): 40 hours
- Phase 4 (Student Interface): 20 hours
- Phase 5 (Reporting): 25 hours
- Phase 6 (Dashboards): 20 hours
- Phase 7 (Testing): **25 hours** (reduced from 30 - quality tools already set up)
- Phase 8 (Submission): 15 hours
- **Total: ~190 hours** (vs. ~205 hours from scratch = **15 hours saved!** ⚡)
- **Timeline: ~12 hours/week over 16 weeks** (very achievable!)

**Boilerplate advantages:**
- ✅ FilamentPHP already configured with best practices
- ✅ Authentication and session management ready
- ✅ Quality tools (Pint, Larastan, Rector) pre-installed
- ✅ Testing framework (Pest) with plugins ready
- ✅ Development workflow (`composer review`) established
- ✅ Admin panel structure in place
- ✅ No need to configure basic Laravel setup

**Critical path:**
Phase 1 → Phase 2 → Phase 3 → Phase 5 (dependencies must be completed in order)

**Parallel work opportunities:**
- Phase 4 and 5 can partially overlap
- Phase 6 widgets can be developed alongside Phase 4-5
- Documentation in Phase 7 can start during Phase 6

**Quick start checklist:**
1. ✅ Clone boilerplate (done)
2. ✅ Run `composer update` (done)
3. 🔲 Update `.env` for MySQL
4. 🔲 Install Spatie Permission
5. 🔲 Install DomPDF
6. 🔲 Create migrations
7. 🔲 Create models
8. 🔲 Set up roles & seeders
9. 🔲 Create Teacher & Student panels
10. 🔲 Start building resources!

---

## 10. Database performance best practices

### 10.1 Query optimization guidelines

**Must implement:**

1. **Use Eager Loading** to prevent N+1 query problems:
   ```php
   // Good - Eager loading
   $activities = AktivitasPembelajaran::with(['kelas', 'mataPelajaran', 'guru'])->get();
   
   // Bad - N+1 problem
   $activities = AktivitasPembelajaran::all();
   foreach ($activities as $activity) {
       echo $activity->kelas->nama; // Triggers separate query each time
   }
   ```

2. **Use select() to fetch only needed columns:**
   ```php
   // Good - Only needed columns
   User::select('id', 'nama', 'email')->where('role', 'teacher')->get();
   
   // Avoid - Fetching all columns unnecessarily
   User::all();
   ```

3. **Utilize indexes in WHERE clauses:**
   ```php
   // Good - Uses index on tanggal
   AktivitasPembelajaran::where('tanggal', '>=', $startDate)
       ->where('tanggal', '<=', $endDate)
       ->get();
   
   // Good - Uses index on kelas_id
   Siswa::where('kelas_id', $kelasId)->get();
   ```

4. **Use pagination for large datasets:**
   ```php
   // Good - Paginated results
   DetailAktivitas::where('siswa_id', $siswaId)
       ->paginate(20);
   ```

### 10.2 Index verification checklist

Before submitting the project, verify indexes using MySQL commands:

```sql
-- Check all indexes on a table
SHOW INDEX FROM users;
SHOW INDEX FROM kelas;
SHOW INDEX FROM aktivitas_pembelajaran;
SHOW INDEX FROM detail_aktivitas;

-- Verify index usage in queries
EXPLAIN SELECT * FROM aktivitas_pembelajaran 
WHERE tanggal >= '2025-01-01' AND kelas_id = 1;

-- Should show 'Using index' or 'Using where; Using index'
```

### 10.3 Performance testing

**Required tests:**

1. **Loading activities for a class** should complete in < 1 second
2. **Calculating student attendance percentage** should complete in < 2 seconds
3. **Generating a class report** should complete in < 5 seconds
4. **Dashboard statistics** should load in < 2 seconds

**How to test:**
- Use Laravel Debugbar to monitor query count and execution time
- Aim for < 10 queries per page load (use eager loading)
- No individual query should take > 100ms on test data

### 10.4 Common pitfalls to avoid

| Pitfall | Impact | Solution |
|---------|--------|----------|
| Missing foreign key indexes | Slow joins | Ensure all FK columns are indexed |
| N+1 query problem | Hundreds of queries | Use eager loading with `with()` |
| No pagination | Memory issues | Use `paginate()` for tables |
| SELECT * queries | Unnecessary data transfer | Use `select()` to specify columns |
| Missing date indexes | Slow date range queries | Index `tanggal` column |
| Unindexed WHERE clauses | Full table scans | Index frequently filtered columns |

---

## 11. Success criteria

### 10.1 Functional completeness

The project is considered complete when:
- ✅ All 33 functional requirements (FR-001 to FR-033) are implemented
- ✅ All 21 user stories (US-001 to US-021) work as specified
- ✅ Three user roles function with proper permissions
- ✅ Teachers can record activities and generate reports
- ✅ Students can view their progress
- ✅ Admin can manage all master data

### 10.2 Technical requirements

- ✅ Application runs without critical errors
- ✅ Database structure matches specification with all required indexes
- ✅ All foreign keys have proper indexes
- ✅ All forms have proper validation
- ✅ Security measures implemented (password hashing, CSRF protection)
- ✅ Interface is responsive on different screen sizes
- ✅ All text is in Indonesian language
- ✅ Queries use eager loading to prevent N+1 problems

### 10.3 Documentation

- ✅ Database schema documentation
- ✅ User manual (how to use the system)
- ✅ Installation guide
- ✅ Source code comments for complex logic

### 10.4 Demonstration

For final presentation, system should demonstrate:
- ✅ Complete user flow: Admin creates data → Teacher records activity → Student views progress
- ✅ Report generation and PDF export
- ✅ Dashboard statistics and calculations
- ✅ Responsive design on different devices

---

## 12. Optional enhancements (Bonus features)

If time permits, these features can be added for additional credit:

### 11.1 Nice to have
- [ ] Bulk student import via CSV/Excel
- [ ] Excel export (in addition to PDF)
- [ ] Advanced filtering on data tables
- [ ] Edit history/audit trail
- [ ] Profile photo upload for users
- [ ] Print-friendly report layouts

### 11.2 Advanced
- [ ] Email notifications (e.g., low attendance alert)
- [ ] Chart visualizations on dashboards
- [ ] Automated backup functionality
- [ ] Password reset via email
- [ ] Multi-language support (English + Indonesian)

---

## 13. Constraints and assumptions

### 12.1 Constraints
- **Time limit:** 16 weeks (approximately 4 months)
- **Team size:** Solo project or small team (1-3 students)
- **Budget:** Free and open-source tools only
- **Environment:** Development on local machine, deployment optional

### 12.2 Assumptions
- Developer has basic knowledge of Laravel and PHP
- Developer has access to local development environment
- MySQL database is available locally
- Internet access for package installation and documentation

---

## 14. Risk assessment

| Risk | Impact | Mitigation |
|------|--------|------------|
| Scope creep (adding too many features) | High | Stick to Must Have requirements, track optional features separately |
| FilamentPHP learning curve | Medium | Start with official documentation, use tutorials, allocate extra time for learning |
| Database design issues | High | Review schema thoroughly before implementation, use migrations for version control |
| Time management | Medium | Follow implementation phases strictly, use project management tool |
| Data validation complexity | Medium | Use Laravel's built-in validation, test thoroughly with edge cases |

---

## Appendix A: Glossary

| Term | Definition |
|------|------------|
| Admin | School administrator/operator with full system access |
| Alpa | Absent without permission |
| Ganjil | Odd semester (typically Aug-Dec) |
| Genap | Even semester (typically Jan-Jun) |
| Guru | Teacher |
| Hadir | Present (attendance status) |
| Izin | Absent with permission |
| Kelas | Class (e.g., 7A, 8B) |
| Mata Pelajaran | Subject/course |
| NIS | Student Identification Number |
| Sakit | Sick leave (attendance status) |
| Siswa | Student |
| SIPPEL | Sistem Informasi Pemantauan Pembelajaran Kelas |
| SMP | Junior High School (grades 7-9) |
| Wali Kelas | Homeroom teacher |

---

## Appendix B: Reference links

- Laravel Documentation: https://laravel.com/docs
- FilamentPHP Documentation: https://filamentphp.com/docs
- Spatie Laravel Permission: https://spatie.be/docs/laravel-permission
- MySQL Documentation: https://dev.mysql.com/doc/
- Tailwind CSS: https://tailwindcss.com/docs

---

**Document prepared for:** Academic Final Project  
**Recommended for:** Final year undergraduate students (Computer Science/Information Systems)  
**Estimated effort:** 200-250 hours of development work  
**Complexity level:** Intermediate  

---

*This simplified PRD focuses on core functionality that is achievable within a typical final project timeline while still demonstrating significant technical skills and understanding of full-stack web development.*
