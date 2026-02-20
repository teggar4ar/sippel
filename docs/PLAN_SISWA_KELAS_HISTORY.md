# Plan: Historical Class Tracking (`siswa_kelas_history`)

## Problem Summary

When admin runs **Ganti Semester** or **Kenaikan Kelas**, the system does `Siswa::update(['kelas_id' => $newKelasId])`, overwriting the student's class reference. Since `siswa.kelas_id` is the **only** link between a student and their class, all historical class data is lost. This means:

- **Student reports** (`LaporanSaya`) can't find the correct subjects for previous years
- **Teacher reports** (`Laporan`) security checks compare `$siswa->kelas_id` against current wali kelas classes only
- **Student dashboard** `performancePerMapel` uses current `$siswa->kelas_id` to find subjects
- **CalculateReports command** uses `$siswa->kelas_id` to match subjects, which breaks for past years


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
