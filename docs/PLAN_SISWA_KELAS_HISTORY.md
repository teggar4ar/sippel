# Plan: Historical Class Tracking (`siswa_kelas_history`)

## Problem Summary

When admin runs **Ganti Semester** or **Kenaikan Kelas**, the system does `Siswa::update(['kelas_id' => $newKelasId])`, overwriting the student's class reference. Since `siswa.kelas_id` is the **only** link between a student and their class, all historical class data is lost. This means:

- **Student reports** (`LaporanSaya`) can't find the correct subjects for previous years
- **Teacher reports** (`Laporan`) security checks compare `$siswa->kelas_id` against current wali kelas classes only
- **Student dashboard** `performancePerMapel` uses current `$siswa->kelas_id` to find subjects
- **CalculateReports command** uses `$siswa->kelas_id` to match subjects, which breaks for past years

## Implementation Phases

### Phase 1 — Database Layer (no breaking changes)

| Step | Action | File |
|------|--------|------|
| 1.1 | Create migration for `siswa_kelas_history` table | `database/migrations/xxxx_create_siswa_kelas_history_table.php` |
| 1.2 | Create `SiswaKelasHistory` model | `app/Models/SiswaKelasHistory.php` |
| 1.3 | Add relationships to `Siswa` model | `app/Models/Siswa.php` |
| 1.4 | Add relationship to `Kelas` model | `app/Models/Kelas.php` |
| 1.5 | Add helper method `Siswa::getKelasForTahunAjaran($tahunAjaranId)` | `app/Models/Siswa.php` |

**Table schema:**

```
siswa_kelas_history
├── id (PK)
├── siswa_id (FK → siswa)
├── kelas_id (FK → kelas)
├── tahun_ajaran_id (FK → tahun_ajaran)
├── timestamps
└── UNIQUE(siswa_id, tahun_ajaran_id)  ← one class per student per year
```

### Phase 2 — Admin Transition Pages (record history during transitions)

| Step | Action | File |
|------|--------|------|
| 2.1 | **GantiSemesterPage**: Before updating `kelas_id`, INSERT current enrollment into `siswa_kelas_history`. Then also INSERT the new enrollment into history. | `app/Filament/Pages/GantiSemesterPage.php` |
| 2.2 | **KenaikanKelasPage**: Same — record old enrollment into history, then record new enrollment after class change. | `app/Filament/Pages/KenaikanKelasPage.php` |

**Key change**: Both pages currently do:

```php
$siswa->update(['kelas_id' => $newKelasId]);  // overwrites history
```

Will become:

```php
// 1. Record current enrollment in history (if not already recorded)
SiswaKelasHistory::firstOrCreate([...old enrollment...]);
// 2. Update current class reference (still needed for "current" class)
$siswa->update(['kelas_id' => $newKelasId]);
// 3. Record new enrollment in history
SiswaKelasHistory::firstOrCreate([...new enrollment...]);
```

### Phase 3 — Backfill Existing Data (safety net)

| Step | Action | File |
|------|--------|------|
| 3.1 | Create seeder/command to backfill history from existing data | `database/seeders/BackfillSiswaKelasHistorySeeder.php` |

Logic: For each active student, INSERT their current `kelas_id` + that class's `tahun_ajaran_id` into history. Also scan `detail_aktivitas → aktivitas_pembelajaran → kelas` to find historical class enrollments.

### Phase 4 — Update Report Queries (use history table)

| Step | Action | File | Current Bug |
|------|--------|------|-------------|
| 4.1 | **Student LaporanSaya**: `mataPelajaranList()` uses `$siswa->kelas_id` | `app/Livewire/Student/LaporanSaya.php` | Shows current class subjects, not selected year's |
| 4.2 | **Student Dashboard**: `performancePerMapel()` uses `$siswa->kelas_id` | `app/Livewire/Student/Dashboard.php` | Shows current class subjects performance only |
| 4.3 | **Teacher Laporan**: `kelasWali()` is unfiltered; security checks use `$siswa->kelas_id` | `app/Livewire/Teacher/Laporan.php` | Shows all classes; security may reject valid old-year students |
| 4.4 | **CalculateReports**: Uses `$siswa->kelas_id` to match subjects | `app/Console/Commands/CalculateReports.php` | Can't calculate past year reports after migration |

Replace pattern: where code currently does `$siswa->kelas_id`, use:

```php
$siswa->getKelasForTahunAjaran($tahunAjaranId)?->id ?? $siswa->kelas_id
```

### Phase 5 — Admin UI Consistency

| Step | Action | File | Issue |
|------|--------|------|-------|
| 5.1 | **SiswaForm**: `kelas_id` select only shows active year's classes | `app/Filament/Resources/Siswas/Schemas/SiswaForm.php` | Fine for enrollment, but should auto-create history record |
| 5.2 | **CreateSiswa/EditSiswa**: After saving, ensure history record is created | `app/Filament/Resources/Siswas/Pages/CreateSiswa.php`, `EditSiswa.php` | New students need history entry |

### Phase 6 — Testing & Validation

| Step | Action |
|------|--------|
| 6.1 | Test: Ganti Semester creates history records for both old & new enrollment |
| 6.2 | Test: Kenaikan Kelas creates history records correctly (naik, tinggal, lulus) |
| 6.3 | Test: Student can view reports from previous year after semester change |
| 6.4 | Test: Teacher can view/download class reports from previous year |
| 6.5 | Test: CalculateReports works for past academic years |
| 6.6 | Test: New student created via admin gets history record |

## Dependency Order

```
Phase 1 (DB + Model)
    ↓
Phase 2 (Admin transitions) ← depends on model/table
    ↓
Phase 3 (Backfill) ← depends on model
    ↓
Phase 4 (Query updates) ← depends on model + backfilled data
    ↓
Phase 5 (Admin UI) ← depends on model
    ↓
Phase 6 (Testing)
```

## Risk Assessment

| Risk | Mitigation |
|------|------------|
| `siswa.kelas_id` is still the "current" class — backward compatible | ✅ No existing code breaks; we only ADD history |
| Backfill may miss old enrollments | Backfill scans both `siswa.kelas_id` AND `detail_aktivitas` chains |
| Race condition during transition | All writes wrapped in `DB::transaction` (already the case) |
| `$siswa->kelas_id` still updated | Intentional — keeps "current class" working for non-history-aware code |

## Files Affected (Complete List)

**New files (3):**

1. `database/migrations/xxxx_create_siswa_kelas_history_table.php`
2. `app/Models/SiswaKelasHistory.php`
3. `database/seeders/BackfillSiswaKelasHistorySeeder.php`

**Modified files (8):**

1. `app/Models/Siswa.php` — add relationships + `getKelasForTahunAjaran()`
2. `app/Models/Kelas.php` — add `siswaHistory()` relationship
3. `app/Filament/Pages/GantiSemesterPage.php` — record history on transition
4. `app/Filament/Pages/KenaikanKelasPage.php` — record history on transition
5. `app/Livewire/Student/LaporanSaya.php` — fix subject list for past years
6. `app/Livewire/Student/Dashboard.php` — fix `performancePerMapel` for past years
7. `app/Livewire/Teacher/Laporan.php` — fix `kelasWali` filter + security checks
8. `app/Console/Commands/CalculateReports.php` — use history for past year lookups
