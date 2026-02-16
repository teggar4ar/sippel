# Phase 5: Reporting (Week 10-11)

**Objective:** Implement PDF report generation for students and classes, using the `laporan` table for cached statistics.

**Estimated Time:** 25 hours

**UI Framework:**
- **Admin interface**: FilamentPHP (desktop) for report management and generation
- **Teacher interface**: Flux UI (mobile) with simple report download buttons
- **Student interface**: Flux UI (mobile) with report viewing/download

**Database Context:**
- The `laporan` table stores pre-calculated/aggregated statistics per student, per subject, per academic year
- This table acts as a **cache** to improve report generation performance
- Reports can be generated on-demand from `detail_aktivitas` or retrieved from cached `laporan` records

---

## Task 5.1: Report calculation and caching

- [x] **5.1.1** Create command: `php artisan make:command CalculateReports`
  - Purpose: Calculate and cache report statistics in `laporan` table
  - For each student, for each subject, for each academic year:
    - Calculate `rata_kehadiran` from `detail_aktivitas.kehadiran`
    - Calculate `rata_nilai` from `detail_aktivitas.nilai`
    - Calculate `rata_partisipasi` from `detail_aktivitas.partisipasi`
    - Create or update `laporan` record

- [x] **5.1.2** Schedule command to run daily: Add to `routes/console.php`

- [x] **5.1.3** Add manual trigger button in Admin panel: Custom action "Perbarui Laporan"

- [x] **5.1.4** Test calculation accuracy with sample data

---

## Task 5.2: Report layout design

- [x] **5.2.1** Create Blade view: `resources/views/reports/student-report.blade.php`
  - Header: School logo, name, address
  - Student info: NIS, Name, Class
  - Academic year info: From laporan.tahunAjaran
  - Sections: Per-subject statistics from `laporan` table (attendance %, average grade, average participation)
  - Footer: Generated date, signature placeholders

- [x] **5.2.2** Create Blade view: `resources/views/reports/class-report.blade.php`
  - Header: School info, Class name, Subject
  - Table: Student list with data from `laporan` table (attendance %, average grade, average participation)
  - Summary: Class average calculated from all students' laporan records
  - Footer: Teacher signature, date

- [x] **5.2.3** Add CSS styling for print-friendly layout

- [x] **5.2.4** Test views with sample data

---

## Task 5.3: Student report generation (Teacher & Admin)

- [x] **5.3.1** Create custom page: `app/Filament/Pages/StudentReport.php`
  - Purpose: Generate student report from cached `laporan` data
  - Form: Select student, select academic year (default: active)

- [x] **5.3.2** Create controller method: `generateStudentReport()`
  - Query `laporan` records for selected student and academic year
  - Retrieve all subjects with their cached statistics
  - If laporan data doesn't exist, calculate on-demand from `detail_aktivitas`
  - Return view with data

- [x] **5.3.3** Add DomPDF integration:
  - Note: DomPDF already installed in Phase 1 ✅
  - Configure PDF options (paper size: A4, orientation: portrait)
  - Generate PDF from Blade view

- [x] **5.3.4** Add download action: Return PDF as download

- [x] **5.3.5** Test report generation with cached laporan data

---

## Task 5.4: Class report generation (Teacher & Admin)

- [x] **5.4.1** Create custom page: `app/Filament/Pages/ClassReport.php`
  - Purpose: Generate class report from cached `laporan` data
  - Form: Select class, select subject, select academic year (default: active)

- [x] **5.4.2** Create controller method: `generateClassReport()`
  - Query all students in selected class
  - For each student, retrieve `laporan` record for selected subject and academic year
  - Calculate class averages from individual laporan records
  - Return view with data

- [x] **5.4.3** Add sorting options: Sort by name, attendance, or grade

- [x] **5.4.4** Add PDF export using DomPDF

- [x] **5.4.5** Test class report with full class data

---

## Task 5.5: Report access in Student panel (Flux UI)

- [x] **5.5.1** Create Livewire component for student reports:
  ```bash
  php artisan make:livewire Student/LaporanSaya
  ```

- [x] **5.5.2** Build mobile-optimized report interface with Flux UI:
  ```blade
  <div class="space-y-6">
      <flux:heading size="xl">Laporan Saya</flux:heading>
      
      <!-- Filter form -->
      <flux:card class="space-y-3">
          <flux:select wire:model.live="tahunAjaranId" label="Tahun Ajaran">
              @foreach($tahunAjaran as $ta)
                  <option value="{{ $ta->id }}">{{ $ta->nama_tahun }} - {{ $ta->semester }}</option>
              @endforeach
          </flux:select>
          
          <flux:select wire:model.live="mataPelajaranId" label="Mata Pelajaran">
              <option value="">Semua Mata Pelajaran</option>
              @foreach($mataPelajaran as $mapel)
                  <option value="{{ $mapel->id }}">{{ $mapel->nama_mapel }}</option>
              @endforeach
          </flux:select>
      </flux:card>
      
      <!-- Report preview from laporan table -->
      <flux:card>
          <flux:heading size="sm" class="mb-4">Ringkasan Laporan</flux:heading>
          
          @if($laporanData->isNotEmpty())
              <div class="space-y-3">
                  @foreach($laporanData as $laporan)
                      <div class="border-b pb-3 last:border-0">
                          <flux:heading size="sm">{{ $laporan->mataPelajaran->nama_mapel }}</flux:heading>
                          <div class="grid grid-cols-3 gap-2 mt-2 text-sm">
                              <div>
                                  <flux:text class="text-xs text-zinc-500">Kehadiran</flux:text>
                                  <flux:text class="font-bold">{{ round($laporan->rata_kehadiran, 1) }}%</flux:text>
                              </div>
                              <div>
                                  <flux:text class="text-xs text-zinc-500">Nilai</flux:text>
                                  <flux:text class="font-bold">{{ round($laporan->rata_nilai, 1) }}</flux:text>
                              </div>
                              <div>
                                  <flux:text class="text-xs text-zinc-500">Partisipasi</flux:text>
                                  <flux:text class="font-bold">{{ round($laporan->rata_partisipasi, 1) }}/5</flux:text>
                              </div>
                          </div>
                      </div>
                  @endforeach
              </div>
          @else
              <flux:text class="text-center py-8">Belum ada data laporan</flux:text>
          @endif
      </flux:card>
      
      <!-- Download button -->
      @if($laporanData->isNotEmpty())
          <flux:button 
              wire:click="downloadPdf" 
              variant="primary" 
              class="w-full"
              icon="arrow-down-tray">
              Download Laporan PDF
          </flux:button>
      @endif
  </div>
  ```

- [x] **5.5.3** Implement download method in Livewire:
  ```php
  public function downloadPdf()
  {
      $siswa = auth()->user()->siswa;
      $laporanData = Laporan::where('siswa_id', $siswa->id)
          ->where('tahun_ajaran_id', $this->tahunAjaranId)
          ->when($this->mataPelajaranId, fn($q) => 
              $q->where('mata_pelajaran_id', $this->mataPelajaranId))
          ->with(['mataPelajaran', 'tahunAjaran'])
          ->get();
      
      $pdf = \PDF::loadView('reports.student-report', [
          'siswa' => $siswa,
          'laporanData' => $laporanData,
      ]);
      
      return response()->streamDownload(function () use ($pdf) {
          echo $pdf->output();
      }, 'laporan-' . $siswa->nis . '.pdf');
  }
  ```

- [x] **5.5.4** Test student self-report generation on mobile

---

## Task 5.6: Report accuracy testing

- [x] **5.6.1** Create test data: 3 students, 10 activities each per subject

- [x] **5.6.2** Run CalculateReports command to populate `laporan` table

- [x] **5.6.3** Manually calculate expected values

- [x] **5.6.4** Generate reports and verify calculations match

- [x] **5.6.5** Test edge cases:
  - Student with no activities (laporan record shouldn't exist)
  - Student with all absences (rata_kehadiran should be 0)
  - Student with missing grades (rata_nilai should exclude nulls)

- [x] **5.6.6** Fix any calculation discrepancies

---

## ✅ Phase 5 Completion Checklist

- [x] CalculateReports command created and scheduled
- [x] Laporan table populated with cached statistics
- [x] Student report Blade template created
- [x] Class report Blade template created
- [x] Report styling is print-friendly
- [x] DomPDF configured and working
- [x] Student report generation functional (uses laporan table)
- [x] Class report generation functional (uses laporan table)
- [x] Student can generate personal reports
- [x] Teacher can generate student reports
- [x] Teacher can generate class reports
- [x] All calculations are accurate
- [x] PDF download working properly
- [x] Reports generated in < 2 seconds (thanks to cached data)

---

## 🎯 Success Criteria

Phase 5 is complete when:
1. ✅ CalculateReports command calculates and caches statistics in `laporan` table
2. ✅ Laporan table is updated daily via scheduled command
3. ✅ Teacher can generate student progress reports from cached data
4. ✅ Teacher can generate class performance reports from cached data
5. ✅ Student can generate personal progress reports from cached data
6. ✅ Reports show accurate attendance percentages
7. ✅ Reports show accurate grade averages
8. ✅ Reports can be exported to PDF
9. ✅ PDF layout is clean and print-friendly
10. ✅ Report generation completes in < 2 seconds (thanks to caching)

---

## 📝 Notes

- **Laporan table = Cache:** The `laporan` table stores pre-calculated statistics to improve performance
- Reports are generated from cached `laporan` data, not calculated on-demand from `detail_aktivitas`
- CalculateReports command should run daily to keep cache fresh
- If laporan data doesn't exist, fallback to real-time calculation from `detail_aktivitas`
- Use DomPDF (already installed in Phase 1)
- Optimize queries for report generation (eager loading)
- Test with large datasets (30+ students)
- Ensure PDF layout doesn't break with long data
- Add pagination for multi-page reports if needed
- Include proper headers and footers in PDF

---

**Previous Phase:** [← Phase 4: Student Interface](./PHASE_4_STUDENT_INTERFACE.md)  
**Next Phase:** [Phase 6: Dashboards & Polish →](./PHASE_6_DASHBOARDS_POLISH.md)
