# 📝 Implementation Checklist

## Phase 1: Core Functionality
- [x] Add `getContext()` method to `TahunAjaran` model
- [x] Add `setContext()` method to `TahunAjaran` model
- [x] Create shared `Components\TahunAjaranSelector` Livewire component (with `$variant` prop)
- [x] Create view for selector component (`livewire/components/tahun-ajaran-selector.blade.php`)
- [x] Add unit tests for `getContext()` and `setContext()` methods (`tests/Unit/Models/TahunAjaranContextTest.php`)

## Phase 2: UI Integration — Teacher
- [x] Update teacher layout to include selector in header (desktop sidebar + mobile header)
- [x] Test selector functionality on teacher panel
- [x] Ensure proper styling with `variant='slate'`
- [ ] Add loading states (deferred)

## Phase 3: UI Integration — Student
- [x] Update student layout to include selector in header (desktop sidebar + mobile header)
- [x] Test selector functionality on student panel
- [x] Ensure proper styling with `variant='teal'`
- [ ] Add loading states (deferred)

## Phase 4: Query Updates — Teacher Panel
Update files di teacher panel:
- [x] `Teacher\Dashboard.php` — Replace `where('status', true)` with `getContext()` in `activeTahunAjaran()`
- [x] `Teacher\AktivitasPembelajaran\CreateAktivitas.php` — Replace 3× `getActive()` with `getContext()`
- [x] `Teacher\Laporan.php` — Replace `mount()` default with `getContext()`
- [x] `Teacher\AktivitasPembelajaran\ListAktivitas.php` — Add tahun ajaran context filter to query (currently unscoped)
- [x] `Teacher\AktivitasPembelajaran\EditAktivitas.php` — No changes needed
- [x] `Teacher\AktivitasPembelajaran\ViewAktivitas.php` — No changes needed

## Phase 5: Query Updates — Student Panel
Update files di student panel:
- [x] `Student\Dashboard.php` — Add context filter to `performancePerMapel`, `totalAktivitas`, `recentAktivitas`, `attendanceStreak`, `motivationalMessage`
- [x] `Student\RiwayatKehadiran.php` — Add context filter to stats query, riwayat query, and mataPelajaran dropdown
- [x] `Student\RiwayatNilai.php` — Add context filter to `summaryPerMapel`, riwayat query, and mataPelajaran dropdown
- [x] `Student\LaporanSaya.php` — Update `mount()` default to use `getContext()` (existing per-page dropdown stays)
- [x] `Student\Profil.php` — No changes needed

## Phase 6: Testing & Documentation
- [x] Manual testing untuk semua fitur teacher
- [x] Manual testing untuk semua fitur student
- [x] Test edge cases (empty context, deleted tahun ajaran)
- [x] Test student with no `kelas` in selected context (e.g., student transferred classes between years)
- [ ] Test context sync between header selector and `LaporanSaya.php` per-page selector
- [ ] Update technical documentation
- [ ] Update user guide
