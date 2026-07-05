# 🔍 SIPPEL Codebase Audit — N+1, Inefisiensi, dan Rekomendasi Refactoring

Hasil audit menyeluruh terhadap seluruh codebase: Models, Livewire Components, Filament Resources/Pages/Widgets, Services, Observers, Policies, Exports, dan Blade Views.

---

## Ringkasan Temuan

| Prioritas | Kategori | Jumlah Temuan |
|-----------|----------|:---:|
| 🔴 Kritis | N+1 Query & Loop Query | 12 |
| 🟠 Tinggi | Redundant/Duplicate Query | 8 |
| 🟡 Sedang | Over-fetching & Missing Cache | 7 |
| 🟢 Rendah | Code Smell & Blade Optimization | 6 |
| **Total** | | **33** |

---

## 🔴 KRITIS — N+1 Query & Loop Query

### 1. `CreateAktivitas::createDetailRecords()` — N INSERT per siswa (DONE``)

[CreateAktivitas.php](file:///d:/laragon/www/sippel/app/Livewire/Teacher/AktivitasPembelajaran/CreateAktivitas.php)

Setiap siswa di kelas = 1 INSERT query. Kelas 30 siswa = **30 INSERT queries**.

```diff
- // SEKARANG: N INSERT queries
- foreach ($this->detailAktivitas as $detail) {
-     DetailAktivitas::create([
-         'aktivitas_pembelajaran_id' => $aktivitas->id,
-         'siswa_id' => $detail['siswa_id'],
-         'kehadiran' => $detail['kehadiran'],
-         'nilai' => $detail['nilai'],
-         'partisipasi' => $detail['partisipasi'],
-         'catatan' => $detail['catatan'],
-     ]);
- }

+ // REKOMENDASI: Bulk insert (1 query)
+ $records = collect($this->detailAktivitas)->map(fn ($detail) => [
+     'aktivitas_pembelajaran_id' => $aktivitas->id,
+     'siswa_id' => $detail['siswa_id'],
+     'kehadiran' => $detail['kehadiran'],
+     'nilai' => $detail['nilai'],
+     'partisipasi' => $detail['partisipasi'],
+     'catatan' => $detail['catatan'],
+     'created_at' => now(),
+     'updated_at' => now(),
+ ])->all();
+ DetailAktivitas::insert($records);
+
+ // Trigger observer/recalculate secara batch setelah insert
+ $insertedDetails = DetailAktivitas::where('aktivitas_pembelajaran_id', $aktivitas->id)->get();
+ foreach ($insertedDetails as $detail) {
+     app(LaporanCalculatorService::class)->recalculateForDetail($detail);
+ }
```

> [!WARNING]
> Bulk `insert()` melewatkan Eloquent events/observers. Jika `DetailAktivitasObserver` perlu dijalankan, gunakan `upsert()` atau trigger recalculation manual setelah insert. Perlu diputuskan: apakah tetap pakai observer atau pindah ke manual recalculation?

---

### 2. `EditAktivitas::updateDetailRecords()` — N × 2 query (SELECT + UPDATE) (DONE)

[EditAktivitas.php](file:///d:/laragon/www/sippel/app/Livewire/Teacher/AktivitasPembelajaran/EditAktivitas.php)

`updateOrCreate()` di dalam loop = 1 SELECT + 1 INSERT/UPDATE per siswa.

```diff
- foreach ($this->detailAktivitas as $detail) {
-     DetailAktivitas::updateOrCreate(
-         ['aktivitas_pembelajaran_id' => $this->aktivitas->id, 'siswa_id' => $detail['siswa_id']],
-         ['kehadiran' => ..., 'nilai' => ..., 'partisipasi' => ..., 'catatan' => ...]
-     );
- }

+ // REKOMENDASI: Gunakan upsert() (1 query)
+ $records = collect($this->detailAktivitas)->map(fn ($d) => [
+     'aktivitas_pembelajaran_id' => $this->aktivitas->id,
+     'siswa_id' => $d['siswa_id'],
+     'kehadiran' => $d['kehadiran'],
+     'nilai' => $d['nilai'],
+     'partisipasi' => $d['partisipasi'],
+     'catatan' => $d['catatan'],
+     'updated_at' => now(),
+     'created_at' => now(),
+ ])->all();
+ DetailAktivitas::upsert($records,
+     ['aktivitas_pembelajaran_id', 'siswa_id'],
+     ['kehadiran', 'nilai', 'partisipasi', 'catatan', 'updated_at']
+ );
```

---

### 3. `ActivityChartWidget` — 7 COUNT queries dalam loop (DONE)

[Activity`ChartWidget.php](file:///d:/laragon/www/sippel/app/Filament/Widgets/ActivityChartWidget.php)

```diff
- // SEKARANG: 7 query (1 per hari)
- for ($i = 6; $i >= 0; $i--) {
-     $date = now()->subDays($i);
-     $count = AktivitasPembelajaran::whereDate('tanggal', $date)->count();
- }

+ // REKOMENDASI: 1 query dengan GROUP BY
+ $startDate = now()->subDays(6)->startOfDay();
+ $counts = AktivitasPembelajaran::where('tanggal', '>=', $startDate)
+     ->selectRaw('DATE(tanggal) as date, COUNT(*) as count')
+     ->groupBy('date')
+     ->pluck('count', 'date');
+
+ for ($i = 6; $i >= 0; $i--) {
+     $date = now()->subDays($i)->format('Y-m-d');
+     $data[] = $counts->get($date, 0);
+ }
```

---

### 4. `Student\Dashboard::stats()` — 4 COUNT queries, harus 1 (DONE)

[Dashboard.php (Student)](file:///d:/laragon/www/sippel/app/Livewire/Student/Dashboard.php)

```diff
- // SEKARANG: 4 query (clone + count per status)
- return [
-     'hadir' => (clone $query)->where('kehadiran', KehadiranStatus::Hadir)->count(),
-     'izin'  => (clone $query)->where('kehadiran', KehadiranStatus::Izin)->count(),
-     'sakit' => (clone $query)->where('kehadiran', KehadiranStatus::Sakit)->count(),
-     'alpa'  => (clone $query)->where('kehadiran', KehadiranStatus::Alpa)->count(),
- ];

+ // REKOMENDASI: 1 query dengan conditional aggregation
+ $result = (clone $query)->selectRaw("
+     SUM(CASE WHEN kehadiran = ? THEN 1 ELSE 0 END) as hadir,
+     SUM(CASE WHEN kehadiran = ? THEN 1 ELSE 0 END) as izin,
+     SUM(CASE WHEN kehadiran = ? THEN 1 ELSE 0 END) as sakit,
+     SUM(CASE WHEN kehadiran = ? THEN 1 ELSE 0 END) as alpa
+ ", [
+     KehadiranStatus::Hadir->value,
+     KehadiranStatus::Izin->value,
+     KehadiranStatus::Sakit->value,
+     KehadiranStatus::Alpa->value,
+ ])->first();
+
+ return [
+     'hadir' => (int) $result->hadir,
+     'izin'  => (int) $result->izin,
+     'sakit' => (int) $result->sakit,
+     'alpa'  => (int) $result->alpa,
+ ];
```

---

### 5. `GantiSemesterPage::migrateStudentsToNewSemester()` — N query per siswa per kelas (DONE)

[GantiSemesterPage.php](file:///d:/laragon/www/sippel/app/Filament/Pages/GantiSemesterPage.php)

Per-kelas loop → per-siswa loop → `SiswaKelasHistory::firstOrCreate()` (×2) + `$siswa->update()`. Untuk 6 kelas × 30 siswa = **~360 queries**.

```diff
+ // REKOMENDASI: Bulk operations per kelas
+ foreach ($classMapping as $oldKelasId => $newKelasId) {
+     $siswaIds = Siswa::where('kelas_id', $oldKelasId)->pluck('id');
+
+     // 1. Bulk update siswa kelas_id
+     Siswa::whereIn('id', $siswaIds)->update(['kelas_id' => $newKelasId]);
+
+     // 2. Bulk insert history records
+     $historyRecords = $siswaIds->map(fn ($id) => [
+         'siswa_id' => $id,
+         'kelas_id' => $newKelasId,
+         'tahun_ajaran_id' => $newTahunAjaranId,
+         'created_at' => now(),
+         'updated_at' => now(),
+     ])->all();
+     SiswaKelasHistory::insertOrIgnore($historyRecords);
+ }
```

---

### 6. `KenaikanKelasPage::processStudentDecisions()` — find() per siswa (DONE)

[KenaikanKelasPage.php](file:///d:/laragon/www/sippel/app/Filament/Pages/KenaikanKelasPage.php)

```diff
- // SEKARANG: 1 query per student
- foreach ($decisions as $siswaId => $decision) {
-     $siswa = Siswa::with('kelas')->find($siswaId);
-     $this->applyStudentDecision($siswa, $decision, ...);
- }

+ // REKOMENDASI: Batch load semua siswa sekaligus
+ $siswaIds = array_keys($decisions);
+ $allSiswa = Siswa::with('kelas')->whereIn('id', $siswaIds)->get()->keyBy('id');
+
+ foreach ($decisions as $siswaId => $decision) {
+     $siswa = $allSiswa->get($siswaId);
+     if (!$siswa) continue;
+     $this->applyStudentDecision($siswa, $decision, ...);
+ }
```

---

### 7. `DetailAktivitasPolicy::getTeacherKelasIds()` — 2 query tanpa cache (DONE)

[DetailAktivitasPolicy.php](file:///d:/laragon/www/sippel/app/Policies/DetailAktivitasPolicy.php)

Setiap policy check (view, create, update, delete) = 2 DB queries. Bisa dipanggil berkali-kali dalam 1 request.

```diff
+ // REKOMENDASI: Cache per-request
  private function getTeacherKelasIds(User $user): array
  {
+     return Cache::store('array')->remember(
+         'teacher_kelas_ids_' . $user->id,
+         fn () => array_unique(array_merge(
              $user->kelasAsWali()->pluck('id')->all(),
              $user->mataPelajaranAsGuru()->pluck('kelas_id')->all()
+         ))
+     );
  }
```

---

### 8. `KelasForm` — 3 query identik untuk grup kelas (DONE)

[KelasForm.php](file:///d:/laragon/www/sippel/app/Filament/Resources/Kelas/Schemas/KelasForm.php)

`grupKelasOptions()`, `grupKelasHelperText()`, dan `grupKelasValidationRule()` masing-masing menjalankan query yang sama untuk mengecek grup yang sudah dipakai.

```diff
+ // REKOMENDASI: 1 shared method
+ private static function getTakenGroups(int $tingkat, int $tahunAjaranId, ?int $excludeId): Collection
+ {
+     return Cache::store('array')->remember(
+         "taken_groups_{$tingkat}_{$tahunAjaranId}_{$excludeId}",
+         fn () => Kelas::where('tingkat_kelas', $tingkat)
+             ->where('tahun_ajaran_id', $tahunAjaranId)
+             ->when($excludeId, fn ($q) => $q->where('id', '!=', $excludeId))
+             ->pluck('grup_kelas')
+     );
+ }
```

---

### 9. Wizard `User::role('teacher')->pluck()` dipanggil per field (DONE)

[GantiSemesterPage.php](file:///d:/laragon/www/sippel/app/Filament/Pages/GantiSemesterPage.php) &
[KenaikanKelasPage.php](file:///d:/laragon/www/sippel/app/Filament/Pages/KenaikanKelasPage.php)

Setiap field wali kelas di wizard menjalankan `User::role('teacher')->pluck('name', 'id')`. Jika ada 6 kelas = **6 query identik**.

```diff
+ // REKOMENDASI: Cache di awal wizard step
+ $teachers = Cache::store('array')->remember('teacher_list', fn () =>
+     User::role('teacher')->orderBy('name')->pluck('name', 'id')
+ );
```

---

### 10. `Siswa::getAttendanceStreak()` — fetch ALL lalu iterate PHP (DONE)

[Siswa.php](file:///d:/laragon/www/sippel/app/Models/Siswa.php)

Mengambil SEMUA record dari DB lalu loop satu per satu di PHP. Untuk siswa aktif bertahun-tahun, bisa ribuan record.

```diff
+ // REKOMENDASI: Limit fetch, stop di first non-hadir
+ public function getAttendanceStreak(?int $tahunAjaranId = null): int
+ {
+     $query = $this->detailAktivitas()
+         ->join('aktivitas_pembelajaran', ...)
+         ->orderByDesc('aktivitas_pembelajaran.tanggal')
+         ->select('detail_aktivitas.kehadiran');
+
+     if ($tahunAjaranId) {
+         $query->whereHas('aktivitasPembelajaran.kelas', 
+             fn ($q) => $q->where('tahun_ajaran_id', $tahunAjaranId));
+     }
+
+     $streak = 0;
+     foreach ($query->lazy(100) as $detail) {
+         if ($detail->kehadiran !== KehadiranStatus::Hadir) break;
+         $streak++;
+     }
+     return $streak;
+ }
```

---

### 11. `Siswa::getAttendanceBreakdown()` — selalu hit DB meski relasi loaded (DONE)

[Siswa.php](file:///d:/laragon/www/sippel/app/Models/Siswa.php)

Metode ini selalu menjalankan query baru, mengabaikan `detailAktivitas` yang sudah eager-loaded.

```diff
+ // REKOMENDASI: Gunakan pola needsQuery() seperti metode lainnya
+ public function getAttendanceBreakdown(?int $mataPelajaranId = null): array
+ {
+     if (!$this->needsQuery($mataPelajaranId, null, null, null)) {
+         $details = $this->detailAktivitas;
+         // ... hitung dari collection ...
+     }
+     // ... fallback ke DB query ...
+ }
```

---

### 12. `DetailAktivitas::scopeWithTimelineJoin()` — whereHas + JOIN redundan (DONE)

[DetailAktivitas.php](file:///d:/laragon/www/sippel/app/Models/DetailAktivitas.php)

Scope ini menjalankan `whereHas('aktivitasPembelajaran')` (subquery) DAN `join('aktivitas_pembelajaran')` pada tabel yang sama — double processing.

```diff
- // SEKARANG: whereHas (subquery) + join (untuk ordering)
- $query->whereHas('aktivitasPembelajaran', function ($q) use ($kelasId, $tahunAjaranId) {
-     if ($kelasId !== null) { $q->where('kelas_id', $kelasId); }
-     $q->whereHas('kelas', fn ($kq) => $kq->where('tahun_ajaran_id', $tahunAjaranId));
- })
- ->join('aktivitas_pembelajaran', ...);

+ // REKOMENDASI: Hanya gunakan JOIN (1 operasi)
+ $query->join('aktivitas_pembelajaran', 'detail_aktivitas.aktivitas_pembelajaran_id', '=', 'aktivitas_pembelajaran.id')
+     ->join('kelas', 'aktivitas_pembelajaran.kelas_id', '=', 'kelas.id')
+     ->where('kelas.tahun_ajaran_id', $tahunAjaranId)
+     ->when($kelasId !== null, fn ($q) => $q->where('aktivitas_pembelajaran.kelas_id', $kelasId))
+     ->whereNull('aktivitas_pembelajaran.deleted_at')
+     ->with(['aktivitasPembelajaran.mataPelajaran'])
+     ->orderByDesc('aktivitas_pembelajaran.tanggal')
+     ->orderByDesc('detail_aktivitas.id')
+     ->select('detail_aktivitas.*');
```

---

## 🟠 TINGGI — Redundant/Duplicate Queries

### 13. `TahunAjaran::getContext()` — dipanggil 6+ kali per render tanpa memo (DONE)

[TahunAjaran.php](file:///d:/laragon/www/sippel/app/Models/TahunAjaran.php)

Setiap `#[Computed]` property di Student/Teacher Dashboard memanggil `getContext()` yang bisa execute 1-2 query.

```diff
+ // REKOMENDASI: Memoize per-request
+ public static function getContext(): ?self
+ {
+     return Cache::store('array')->remember('tahun_ajaran_context', function () {
+         $contextId = session('tahun_ajaran_context');
+         if ($contextId) {
+             $tahunAjaran = self::find($contextId);
+             if ($tahunAjaran) return $tahunAjaran;
+         }
+         return self::getActive();
+     });
+ }
```

---

### 14. `Teacher\Dashboard::dashboardStats()` — query MataPelajaran duplikat (DONE)

[Dashboard.php (Teacher)](file:///d:/laragon/www/sippel/app/Livewire/Teacher/Dashboard.php)

`MataPelajaran::where('guru_id', ...)` dijalankan 2x: sekali untuk `count()`, sekali untuk `pluck('kelas_id')`.

```diff
+ // REKOMENDASI: Ambil sekali, pakai ulang
+ $mapels = MataPelajaran::where('guru_id', Auth::id())
+     ->whereHas('kelas', fn ($q) => $q->where('tahun_ajaran_id', $tahunAjaran->id))
+     ->get(['id', 'kelas_id']);
+ $mapelDiampu = $mapels->count();
+ $kelasIds = $mapels->pluck('kelas_id')->unique()->values()->all();
```

---

### 15. `Teacher\Dashboard::partisipasiPerKelas()` — duplikat mySubjects() (DONE)

[Dashboard.php (Teacher)](file:///d:/laragon/www/sippel/app/Livewire/Teacher/Dashboard.php)

Method ini menjalankan query hampir identik dengan `mySubjects()` computed property.

```diff
+ // REKOMENDASI: Gunakan $this->mySubjects yang sudah di-cache oleh Livewire
+ $mapelList = $this->mySubjects;
```

---

### 16. `ClassReport` — duplikat query antara preview dan download

[ClassReport.php](file:///d:/laragon/www/sippel/app/Filament/Pages/ClassReport.php)

`generatePreview()` dan `downloadPdf()` keduanya menjalankan query load kelas, mataPelajaran, tahunAjaran, lalu `getLaporanData()` — copy-paste.

```diff
+ // REKOMENDASI: Extract shared method
+ private function resolveReportContext(): array
+ {
+     $kelas = Kelas::with('waliKelas')->findOrFail($this->kelasId);
+     $mataPelajaran = $this->mataPelajaranId
+         ? MataPelajaran::with('guru')->find($this->mataPelajaranId) : null;
+     $tahunAjaran = TahunAjaran::find($this->tahunAjaranId);
+     $laporanData = $this->getLaporanData();
+     return compact('kelas', 'mataPelajaran', 'tahunAjaran', 'laporanData');
+ }
```

---

### 17. `HasLaporanDownloads` — duplikat access check

[HasLaporanDownloads.php](file:///d:/laragon/www/sippel/app/Livewire/Teacher/Concerns/HasLaporanDownloads.php)

`validateStudentPreviewAccess()` dan `resolveStudentPdfData()` keduanya menjalankan kelasHistory check yang identik.

---

### 18. `EditAktivitas::loadDetailAktivitas()` — re-query Siswa yang sudah loaded

[EditAktivitas.php](file:///d:/laragon/www/sippel/app/Livewire/Teacher/AktivitasPembelajaran/EditAktivitas.php)

`mount()` sudah eager-load `detailAktivitas.siswa.user`, tapi `loadDetailAktivitas()` menjalankan `Siswa::where('kelas_id')` lagi.

```diff
+ // REKOMENDASI: Gunakan data yang sudah di-load
+ $loadedSiswaIds = $this->aktivitas->detailAktivitas->pluck('siswa_id');
+ // Hanya query untuk siswa BARU yang belum ada di detailAktivitas
```

---

### 19. `SiswaKelasHistory::clearTeacherDashboardCache()` — DB query di model event

[SiswaKelasHistory.php](file:///d:/laragon/www/sippel/app/Models/SiswaKelasHistory.php)

Setiap create/update/delete pada `SiswaKelasHistory` menjalankan `MataPelajaran::query()->pluck('guru_id')` untuk cache invalidation.

```diff
+ // REKOMENDASI: Defer ke queue jika tidak time-critical
+ static::created(function (self $history): void {
+     dispatch(fn () => $history->clearTeacherDashboardCache(...));
+ });
```

---

### 20. `LaporanCalculatorService::calculateStatistics()` — 4× iterasi collection

[LaporanCalculatorService.php](file:///d:/laragon/www/sippel/app/Services/LaporanCalculatorService.php)

```diff
- $hadirCount = $detailAktivitas->filter(fn ($d) => $d->kehadiran === KehadiranStatus::Hadir)->count();
- $izinCount  = $detailAktivitas->filter(fn ($d) => $d->kehadiran === KehadiranStatus::Izin)->count();
- $sakitCount = $detailAktivitas->filter(fn ($d) => $d->kehadiran === KehadiranStatus::Sakit)->count();
- $alpaCount  = $detailAktivitas->filter(fn ($d) => $d->kehadiran === KehadiranStatus::Alpa)->count();

+ // REKOMENDASI: 1 iterasi dengan countBy
+ $counts = $detailAktivitas->countBy('kehadiran');
+ $hadirCount = $counts[KehadiranStatus::Hadir->value] ?? 0;
+ $izinCount  = $counts[KehadiranStatus::Izin->value] ?? 0;
+ $sakitCount = $counts[KehadiranStatus::Sakit->value] ?? 0;
+ $alpaCount  = $counts[KehadiranStatus::Alpa->value] ?? 0;
```

---

## 🟡 SEDANG — Over-fetching & Missing Cache

### 21. `ListAktivitas::aktivitas()` — over-fetch detailAktivitas

[ListAktivitas.php](file:///d:/laragon/www/sippel/app/Livewire/Teacher/AktivitasPembelajaran/ListAktivitas.php)

`with('detailAktivitas')` memuat SEMUA record detail untuk tiap aktivitas di halaman paginasi, hanya untuk menampilkan count dan statistik.

```diff
- ->with(['mataPelajaran', 'kelas', 'detailAktivitas'])
+ ->with(['mataPelajaran', 'kelas'])
+ ->withCount('detailAktivitas')
+ ->withCount(['detailAktivitas as hadir_count' => fn ($q) =>
+     $q->where('kehadiran', KehadiranStatus::Hadir)])
+ ->withAvg('detailAktivitas', 'partisipasi')
```

> [!NOTE]
> Jika view Blade membutuhkan iterasi per-detail (bukan hanya count), maka `with('detailAktivitas')` tetap diperlukan. Namun, dari analisis `list-aktivitas.blade.php`, hanya statistik agregat yang ditampilkan.

---

### 22. `Student\Dashboard::heatmapData()` — tidak di-cache

[Dashboard.php (Student)](file:///d:/laragon/www/sippel/app/Livewire/Student/Dashboard.php)

Computed property ini menjalankan query + loop harian sepanjang semester TANPA cache, padahal data hanya berubah saat ada aktivitas baru.

```diff
+ $cacheKey = 'student_heatmap_' . $siswa->id . '_' . $contextTahunAjaran->id;
+ return Cache::remember($cacheKey, 300, function () use (...) {
+     // ... existing heatmap logic ...
+ });
```

---

### 23. `Student\Dashboard::motivationalMessage()` — hidden queries, tidak cached

[Dashboard.php (Student)](file:///d:/laragon/www/sippel/app/Livewire/Student/Dashboard.php)

Memanggil `$siswa->getAttendancePercentage()` dan `$siswa->getAverageParticipation()` — yang masing-masing bisa menjalankan query terpisah jika `detailAktivitas` belum di-load.

```diff
+ // REKOMENDASI: Cache, atau pre-load detailAktivitas di mount
+ #[Computed]
+ public function motivationalMessage(): array
+ {
+     $cacheKey = 'student_motivation_' . $this->siswa()?->id . '_' . TahunAjaran::getContext()?->id;
+     return Cache::remember($cacheKey, 300, fn () => $this->computeMotivation());
+ }
```

---

### 24. `StudentReport` form — load ALL siswa ke memory

[StudentReport.php](file:///d:/laragon/www/sippel/app/Filament/Pages/StudentReport.php)

```php
$students = Siswa::with('user')->get()->mapWithKeys(...);
```

Untuk sekolah besar, ini bisa ribuan record.

```diff
+ // REKOMENDASI: Filter berdasarkan kelas yang dipilih
+ $students = Siswa::with('user')
+     ->where('kelas_id', $this->kelasId)
+     ->get()
+     ->mapWithKeys(...);
```

---

### 25. `EditAktivitas::mataPelajaran()` — tidak ada filter tahun ajaran

[EditAktivitas.php](file:///d:/laragon/www/sippel/app/Livewire/Teacher/AktivitasPembelajaran/EditAktivitas.php)

Mengembalikan SEMUA mata pelajaran guru di semua tahun ajaran, bukan hanya tahun aktif.

```diff
+ ->whereHas('kelas', fn ($q) => $q->where('tahun_ajaran_id', TahunAjaran::getContext()?->id))
```

---

### 26. Bulk operations load ALL kelas ke memory

[SiswasTable.php](file:///d:/laragon/www/sippel/app/Filament/Resources/Siswas/Tables/SiswasTable.php) &
[MataPelajaransTable.php](file:///d:/laragon/www/sippel/app/Filament/Resources/MataPelajarans/Tables/MataPelajaransTable.php)

```diff
- Kelas::with('tahunAjaran')->get() // loads ALL kelas of ALL years

+ // REKOMENDASI: Filter tahun ajaran aktif
+ $activeTahunAjaran = TahunAjaran::getContext();
+ Kelas::with('tahunAjaran')
+     ->when($activeTahunAjaran, fn ($q) => $q->where('tahun_ajaran_id', $activeTahunAjaran->id))
+     ->get();
```

---

### 27. `ClassReportExport` — dua whereHas subquery bisa digabung

[ClassReportExport.php](file:///d:/laragon/www/sippel/app/Exports/ClassReportExport.php)

```diff
- ->whereHas('aktivitasPembelajaran', fn ($q) => $q->where('kelas_id', ...))
- ->whereHas('aktivitasPembelajaran', fn ($q) => $q->whereNull('deleted_at'))

+ ->whereHas('aktivitasPembelajaran', fn ($q) => $q
+     ->where('kelas_id', $this->kelas->id)
+     ->whereNull('deleted_at')
+ )
```

---

## 🟢 RENDAH — Code Smell & Blade Optimization

### 28. Blade — Duplikasi `@php` block desktop + mobile (3 views)

File yang terdampak:
- [list-aktivitas.blade.php](file:///d:/laragon/www/sippel/resources/views/livewire/teacher/aktivitas-pembelajaran/list-aktivitas.blade.php) (L104-118 = L178-192)
- [view-aktivitas.blade.php](file:///d:/laragon/www/sippel/resources/views/livewire/teacher/aktivitas-pembelajaran/view-aktivitas.blade.php) (L126-143 = L181-198)
- [riwayat-aktivitas.blade.php](file:///d:/laragon/www/sippel/resources/views/livewire/student/riwayat-aktivitas.blade.php) (L94-111 = L169-186)

Setiap view menghitung statistik di `@php` dua kali — sekali untuk desktop, sekali untuk mobile. Pindahkan komputasi ke awal loop (sebelum `<tr>` / mobile card split).

---

### 29. Blade — Duplikat `@livewire('tahun-ajaran-selector')` (2 instance per page)

[student.blade.php](file:///d:/laragon/www/sippel/resources/views/layouts/student.blade.php) (L45 + L116) &
[teacher.blade.php](file:///d:/laragon/www/sippel/resources/views/layouts/teacher.blade.php) (L45 + L121)

Membuat 2 Livewire component instance per page, masing-masing menjalankan query sendiri. Gunakan 1 instance dengan CSS visibility, atau cache query-nya.

---

### 30. Blade — Missing `@stack('scripts')` di student layout

[student.blade.php](file:///d:/laragon/www/sippel/resources/views/layouts/student.blade.php)

Tidak ada `@stack('vendor-scripts')` dan `@stack('scripts')` — pushed content dari komponen siswa akan hilang secara diam-diam.

---

### 31. Blade — `wire:model.live` tanpa debounce pada input tanggal

[dashboard.blade.php (Student)](file:///d:/laragon/www/sippel/resources/views/livewire/student/dashboard.blade.php) (L27, L36)

```diff
- wire:model.live="tanggalMulai"
+ wire:model.live.debounce.500ms="tanggalMulai"
```

---

### 32. `UsersTable` bulk delete — per-record exists() check

[UsersTable.php](file:///d:/laragon/www/sippel/app/Filament/Resources/Users/Tables/UsersTable.php)

```diff
- // Per record: 3 exists() queries
- $record->siswa()->exists()
- $record->kelasAsWali()->exists()
- $record->mataPelajaranAsGuru()->exists()

+ // REKOMENDASI: Pre-load relationship counts
+ // In resource's getEloquentQuery():
+ ->withCount(['siswa', 'kelasAsWali', 'mataPelajaranAsGuru'])
+ // Then check: $record->siswa_count > 0, etc.
```

---

### 33. `MataPelajaran::clearDashboardCacheForContext()` — query di setiap model event

[MataPelajaran.php](file:///d:/laragon/www/sippel/app/Models/MataPelajaran.php)

`Kelas::withTrashed()->find($kelasId)` dijalankan setiap created/updated/deleted/restored.

```diff
+ // REKOMENDASI: Gunakan kelas_id dan relasi yang sudah tersedia
+ private function clearDashboardCacheForContext(?int $guruId, ?int $kelasId): void
+ {
+     if (!$guruId || !$kelasId) return;
+     // Jika kelas sudah loaded di model event context:
+     $tahunAjaranId = $this->relationLoaded('kelas')
+         ? $this->kelas?->tahun_ajaran_id
+         : Kelas::withTrashed()->where('id', $kelasId)->value('tahun_ajaran_id');
+     if (!$tahunAjaranId) return;
+     Cache::forget('teacher_dashboard_stats_' . $guruId . '_' . $tahunAjaranId);
+ }
```

---

## Verification Plan

### Automated Tests
```bash
php artisan test --filter=Dashboard
php artisan test --filter=Aktivitas
php artisan test --filter=Laporan
```

### Manual Verification
- Enable Laravel Debugbar dan periksa query count sebelum & sesudah perbaikan pada setiap halaman
- Khusus Teacher Dashboard: target penurunan dari ~25+ queries ke <10 queries
- Khusus Student Dashboard: target penurunan dari ~15+ queries ke <8 queries
- Test bulk operations (Ganti Semester, Kenaikan Kelas) dengan data besar

---

## User Review Required

> [!IMPORTANT]
> **Prioritas eksekusi**: Apakah ingin saya memperbaiki semua 33 temuan sekaligus, atau fokus pada kategori tertentu dulu (misalnya hanya 🔴 Kritis)?

> [!IMPORTANT]
> **Bulk insert vs Observer**: Temuan #1 dan #2 (CreateAktivitas/EditAktivitas) menggunakan Eloquent `create()` yang memicu `DetailAktivitasObserver` untuk recalculate Laporan. Jika kita switch ke bulk `insert()`/`upsert()`, observer tidak akan terpicu. Opsi:
> 1. Bulk insert + manual trigger recalculation setelahnya (lebih efisien)
> 2. Tetap loop tapi batch per-chunk (kompromi)

> [!WARNING]
> **Backward compatibility**: Beberapa perubahan (terutama #12 scopeWithTimelineJoin dan #21 withCount) akan mengubah data structure yang dikirim ke Blade view. Perlu update Blade bersamaan.

## Open Questions

1. Apakah ada batasan jumlah siswa per kelas? (untuk menentukan apakah bulk insert kritis)
2. Apakah `DetailAktivitasObserver` harus selalu realtime, atau bisa di-queue?
3. Apakah ada rencana menambah fitur yang bergantung pada `detailAktivitas` relationship di `ListAktivitas`?
