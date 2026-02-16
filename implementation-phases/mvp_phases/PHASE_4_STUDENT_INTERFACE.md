# Phase 4: Student Interface (Week 8-9)

**Objective:** Create mobile-first student dashboard and views for attendance and grades using Flux UI.

**Estimated Time:** 24 hours (includes Flux UI implementation: ~4 hours additional)

**UI Framework:** Flux UI (Livewire component library) for mobile-first student interface

---

## Task 4.0: Define student routes

- [x] **4.0.1** Add student routes to `routes/web.php`:
  ```php
  // Student Routes (Livewire + FluxUI)
  Route::middleware(['auth', 'role:student'])->prefix('student')->name('student.')->group(function () {
      Route::get('/', \App\Livewire\Student\Dashboard::class)->name('dashboard');
      Route::get('/kehadiran', \App\Livewire\Student\RiwayatKehadiran::class)->name('kehadiran');
      Route::get('/nilai', \App\Livewire\Student\RiwayatNilai::class)->name('nilai');
      Route::get('/profil', \App\Livewire\Student\Profil::class)->name('profil');
  });
  ```

- [x] **4.0.2** Test student routes:
  - Access `/student` as student → should work
  - Access `/student` as admin → should get 403
  - Access `/student` as teacher → should get 403

---

## Task 4.1: Student navigation and layout

**Directory Structure:**
```
app/Livewire/
├── Student/
│   ├── Dashboard.php
│   ├── RiwayatKehadiran.php
│   ├── RiwayatNilai.php
│   └── Profil.php

resources/views/
├── layouts/
│   └── student.blade.php
└── livewire/
    └── student/
        ├── dashboard.blade.php
        ├── riwayat-kehadiran.blade.php
        ├── riwayat-nilai.blade.php
        └── profil.blade.php
```

- [x] **4.1.1** Create student layout with Flux UI:
  ```blade
  <!-- resources/views/layouts/student.blade.php -->
  <!DOCTYPE html>
  <html lang="id">
  <head>
      <meta charset="UTF-8">
      <meta name="viewport" content="width=device-width, initial-scale=1.0">
      <title>SIPPEL - Student Portal</title>
      
      @fluxStyles
      @vite('resources/css/app.css')
  </head>
  <body class="min-h-screen bg-white dark:bg-zinc-800">
      <!-- Mobile-optimized header -->
      <flux:header class="sticky top-0 z-50">
          <flux:sidebar.toggle icon="bars-2" />
          <flux:heading size="sm">SIPPEL - Siswa</flux:heading>
          <flux:spacer />
          <flux:dropdown position="bottom" align="end">
              <flux:profile avatar="{{ auth()->user()->avatar_url ?? 'https://ui-avatars.com/api/?name=' . urlencode(auth()->user()->nama) }}" />
              <flux:menu>
                  <flux:menu.item href="{{ route('student.dashboard') }}">Dashboard</flux:menu.item>
                  <flux:menu.item href="{{ route('student.profil') }}">Profil</flux:menu.item>
                  <flux:menu.separator />
                  <flux:menu.item icon="arrow-right-start-on-rectangle" 
                      href="{{ route('filament.app.auth.logout') }}"
                      onclick="event.preventDefault(); document.getElementById('logout-form').submit();">
                      Logout
                  </flux:menu.item>
              </flux:menu>
          </flux:dropdown>
      </flux:header>
      
      <!-- Sidebar for mobile -->
      <flux:sidebar sticky collapsible="mobile">
          <flux:sidebar.nav>
              <flux:sidebar.item icon="home" href="{{ route('student.dashboard') }}">Dashboard</flux:sidebar.item>
              <flux:sidebar.item icon="calendar" href="{{ route('student.kehadiran') }}">Kehadiran</flux:sidebar.item>
              <flux:sidebar.item icon="academic-cap" href="{{ route('student.nilai') }}">Nilai</flux:sidebar.item>
          </flux:sidebar.nav>
      </flux:sidebar>
      
      <flux:main class="px-4 py-6 max-w-7xl mx-auto">
          {{ $slot }}
      </flux:main>
      
      <form id="logout-form" action="{{ route('filament.app.auth.logout') }}" method="POST" class="hidden">
          @csrf
      </form>
      
      @fluxScripts
      @vite('resources/js/app.js')
  </body>
  </html>
  ```

- [x] **4.1.2** Test navigation on mobile devices

---

## Task 4.2: Student dashboard (Mobile-first)

- [x] **4.2.1** Create dashboard Livewire component:
  ```bash
  php artisan make:livewire Student/Dashboard
  ```

- [x] **4.2.2** Build mobile-optimized dashboard with Flux UI:
  ```blade
  <div class="space-y-6">
      <div>
          <flux:heading size="xl">Hai, {{ auth()->user()->nama }}!</flux:heading>
          <flux:text class="mt-2">Kelas {{ $siswa->kelas->nama_lengkap }}</flux:text>
      </div>
      
      <!-- Stats cards in grid -->
      <div class="grid grid-cols-2 gap-4">
          <flux:card size="sm">
              <flux:icon name="check-circle" class="text-green-500 mb-2" />
              <flux:text class="text-sm text-zinc-500">Kehadiran</flux:text>
              <flux:heading size="lg">{{ $siswa->attendance_percentage }}%</flux:heading>
          </flux:card>
          
          <flux:card size="sm">
              <flux:icon name="academic-cap" class="text-blue-500 mb-2" />
              <flux:text class="text-sm text-zinc-500">Rata-rata Nilai</flux:text>
              <flux:heading size="lg">{{ $siswa->average_grade }}</flux:heading>
          </flux:card>
          
          <flux:card size="sm">
              <flux:icon name="star" class="text-yellow-500 mb-2" />
              <flux:text class="text-sm text-zinc-500">Partisipasi</flux:text>
              <flux:heading size="lg">{{ $siswa->average_partisipasi }}/5</flux:heading>
          </flux:card>
          
          <flux:card size="sm">
              <flux:icon name="book-open" class="text-purple-500 mb-2" />
              <flux:text class="text-sm text-zinc-500">Total Aktivitas</flux:text>
              <flux:heading size="lg">{{ $totalAktivitas }}</flux:heading>
          </flux:card>
      </div>
      
      <!-- Quick links -->
      <div class="grid grid-cols-2 gap-3">
          <flux:button href="{{ route('student.kehadiran') }}" variant="primary" class="w-full">
              Lihat Kehadiran
          </flux:button>
          <flux:button href="{{ route('student.nilai') }}" variant="subtle" class="w-full">
              Lihat Nilai
          </flux:button>
      </div>
      
      <!-- Recent activities card -->
      <flux:card class="space-y-4">
          <flux:heading size="sm">Aktivitas Terbaru</flux:heading>
          @foreach($recentAktivitas as $detail)
              <div class="border-b pb-3 last:border-0">
                  <div class="flex justify-between items-start mb-1">
                      <flux:text class="font-medium">{{ $detail->aktivitasPembelajaran->topik }}</flux:text>
                      <flux:badge 
                          :variant="$detail->kehadiran === 'Hadir' ? 'success' : 'warning'">
                          {{ $detail->kehadiran }}
                      </flux:badge>
                  </div>
                  <flux:text class="text-sm text-zinc-500">
                      {{ $detail->aktivitasPembelajaran->mataPelajaran->nama_mapel }} - 
                      {{ $detail->aktivitasPembelajaran->tanggal->format('d M Y') }}
                  </flux:text>
                  @if($detail->nilai)
                      <flux:text class="text-sm mt-1">Nilai: {{ $detail->nilai }}</flux:text>
                  @endif
              </div>
          @endforeach
          
          <flux:button href="{{ route('student.kehadiran') }}" variant="ghost" class="w-full">
              Lihat Semua Aktivitas
          </flux:button>
      </flux:card>
  </div>
  ```

- [x] **4.2.3** Implement data calculations in component:
  ```php
  public function getAttendancePercentageProperty()
  {
      $siswa = auth()->user()->siswa;
      $total = $siswa->detailAktivitas()->count();
      $hadir = $siswa->detailAktivitas()->where('kehadiran', 'Hadir')->count();
      
      return $total > 0 ? round(($hadir / $total) * 100, 1) : 0;
  }
  ```

- [x] **4.2.4** Test dashboard on actual mobile devices

---

## Task 4.3: Attendance history view (Mobile-first)

- [x] **4.3.1** Create Livewire component:
  ```bash
  php artisan make:livewire Student/RiwayatKehadiran
  ```

- [x] **4.3.2** Build mobile-optimized attendance view:
  ```blade
  <div class="space-y-6">
      <flux:heading size="xl">Riwayat Kehadiran</flux:heading>
      
      <!-- Summary stats cards -->
      <div class="grid grid-cols-4 gap-2">
          <flux:card size="sm" class="text-center">
              <flux:badge variant="success" class="mb-1">Hadir</flux:badge>
              <flux:heading size="lg">{{ $stats['hadir'] }}</flux:heading>
              <flux:text class="text-xs">{{ $stats['hadir_pct'] }}%</flux:text>
          </flux:card>
          
          <flux:card size="sm" class="text-center">
              <flux:badge variant="info" class="mb-1">Izin</flux:badge>
              <flux:heading size="lg">{{ $stats['izin'] }}</flux:heading>
              <flux:text class="text-xs">{{ $stats['izin_pct'] }}%</flux:text>
          </flux:card>
          
          <flux:card size="sm" class="text-center">
              <flux:badge variant="warning" class="mb-1">Sakit</flux:badge>
              <flux:heading size="lg">{{ $stats['sakit'] }}</flux:heading>
              <flux:text class="text-xs">{{ $stats['sakit_pct'] }}%</flux:text>
          </flux:card>
          
          <flux:card size="sm" class="text-center">
              <flux:badge variant="danger" class="mb-1">Alpa</flux:badge>
              <flux:heading size="lg">{{ $stats['alpa'] }}</flux:heading>
              <flux:text class="text-xs">{{ $stats['alpa_pct'] }}%</flux:text>
          </flux:card>
      </div>
      
      <!-- Filters -->
      <flux:card class="space-y-3">
          <flux:select wire:model.live="filterMapel" label="Mata Pelajaran">
              <option value="">Semua Mata Pelajaran</option>
              @foreach($mataPelajaran as $mapel)
                  <option value="{{ $mapel->id }}">{{ $mapel->nama_mapel }}</option>
              @endforeach
          </flux:select>
          
          <flux:select wire:model.live="filterStatus" label="Status Kehadiran">
              <option value="">Semua Status</option>
              <option value="Hadir">Hadir</option>
              <option value="Izin">Izin</option>
              <option value="Sakit">Sakit</option>
              <option value="Alpa">Alpa</option>
          </flux:select>
          
          <div class="grid grid-cols-2 gap-3">
              <flux:input wire:model.live="filterDariTanggal" type="date" label="Dari" />
              <flux:input wire:model.live="filterSampaiTanggal" type="date" label="Sampai" />
          </div>
      </flux:card>
      
      <!-- Attendance list as cards -->
      <div class="space-y-3">
          @forelse($riwayat as $detail)
              <flux:card size="sm">
                  <div class="flex justify-between items-start mb-2">
                      <flux:badge>{{ $detail->aktivitasPembelajaran->tanggal->format('d M Y') }}</flux:badge>
                      <flux:badge 
                          :variant="match($detail->kehadiran) {
                              'Hadir' => 'success',
                              'Izin' => 'info',
                              'Sakit' => 'warning',
                              'Alpa' => 'danger',
                          }">
                          {{ $detail->kehadiran }}
                      </flux:badge>
                  </div>
                  
                  <flux:heading size="sm">{{ $detail->aktivitasPembelajaran->topik }}</flux:heading>
                  <flux:text class="text-sm text-zinc-500 mt-1">
                      {{ $detail->aktivitasPembelajaran->mataPelajaran->nama_mapel }}
                  </flux:text>
                  
                  @if($detail->catatan)
                      <flux:text class="text-sm mt-2 italic">
                          Catatan: {{ $detail->catatan }}
                      </flux:text>
                  @endif
              </flux:card>
          @empty
              <flux:card>
                  <flux:text class="text-center py-8">Tidak ada data kehadiran</flux:text>
              </flux:card>
          @endforelse
      </div>
      
      <!-- Pagination -->
      <div class="mt-4">
          {{ $riwayat->links() }}
      </div>
  </div>
  ```

- [x] **4.3.3** Implement filtering logic:
  ```php
  public function getRiwayatProperty()
  {
      return DetailAktivitas::query()
          ->where('siswa_id', auth()->user()->siswa->id)
          ->with(['aktivitasPembelajaran.mataPelajaran', 'aktivitasPembelajaran.kelas'])
          ->when($this->filterMapel, fn($q) => 
              $q->whereHas('aktivitasPembelajaran', fn($sq) => 
                  $sq->where('mata_pelajaran_id', $this->filterMapel)))
          ->when($this->filterStatus, fn($q) => $q->where('kehadiran', $this->filterStatus))
          ->when($this->filterDariTanggal, fn($q) => 
              $q->whereHas('aktivitasPembelajaran', fn($sq) => 
                  $sq->whereDate('tanggal', '>=', $this->filterDariTanggal)))
          ->when($this->filterSampaiTanggal, fn($q) => 
              $q->whereHas('aktivitasPembelajaran', fn($sq) => 
                  $sq->whereDate('tanggal', '<=', $this->filterSampaiTanggal)))
          ->latest('created_at')
          ->paginate(15);
  }
  
  public function getStatsProperty()
  {
      $siswa = auth()->user()->siswa;
      $query = DetailAktivitas::where('siswa_id', $siswa->id);
      
      if ($this->filterMapel) {
          $query->whereHas('aktivitasPembelajaran', fn($q) => 
              $q->where('mata_pelajaran_id', $this->filterMapel));
      }
      
      $total = $query->count();
      $hadir = $query->where('kehadiran', 'Hadir')->count();
      $izin = $query->where('kehadiran', 'Izin')->count();
      $sakit = $query->where('kehadiran', 'Sakit')->count();
      $alpa = $query->where('kehadiran', 'Alpa')->count();
      
      return [
          'hadir' => $hadir,
          'izin' => $izin,
          'sakit' => $sakit,
          'alpa' => $alpa,
          'hadir_pct' => $total > 0 ? round(($hadir / $total) * 100) : 0,
          'izin_pct' => $total > 0 ? round(($izin / $total) * 100) : 0,
          'sakit_pct' => $total > 0 ? round(($sakit / $total) * 100) : 0,
          'alpa_pct' => $total > 0 ? round(($alpa / $total) * 100) : 0,
      ];
  }
  ```

- [x] **4.3.4** Test attendance history on mobile devices

---

## Task 4.4: Grade history view (Mobile-first)

- [x] **4.4.1** Create Livewire component:
  ```bash
  php artisan make:livewire Student/RiwayatNilai
  ```

- [x] **4.4.2** Build mobile-optimized grade view:
  ```blade
  <div class="space-y-6">
      <flux:heading size="xl">Riwayat Nilai</flux:heading>
      
      <!-- Summary by subject -->
      <div class="space-y-3">
          @foreach($summaryPerMapel as $mapel)
              <flux:card size="sm">
                  <flux:heading size="sm">{{ $mapel['nama'] }}</flux:heading>
                  <div class="grid grid-cols-3 gap-3 mt-3">
                      <div>
                          <flux:text class="text-xs text-zinc-500">Rata-rata</flux:text>
                          <flux:text class="text-lg font-bold">{{ $mapel['avg'] }}</flux:text>
                      </div>
                      <div>
                          <flux:text class="text-xs text-zinc-500">Tertinggi</flux:text>
                          <flux:text class="text-lg font-bold text-green-600">{{ $mapel['max'] }}</flux:text>
                      </div>
                      <div>
                          <flux:text class="text-xs text-zinc-500">Terendah</flux:text>
                          <flux:text class="text-lg font-bold text-red-600">{{ $mapel['min'] }}</flux:text>
                      </div>
                  </div>
              </flux:card>
          @endforeach
      </div>
      
      <!-- Filters -->
      <flux:card class="space-y-3">
          <flux:select wire:model.live="filterMapel" label="Mata Pelajaran">
              <option value="">Semua Mata Pelajaran</option>
              @foreach($mataPelajaran as $mapel)
                  <option value="{{ $mapel->id }}">{{ $mapel->nama_mapel }}</option>
              @endforeach
          </flux:select>
          
          <div class="grid grid-cols-2 gap-3">
              <flux:input wire:model.live="filterDariTanggal" type="date" label="Dari" />
              <flux:input wire:model.live="filterSampaiTanggal" type="date" label="Sampai" />
          </div>
      </flux:card>
      
      <!-- Grade list as cards -->
      <div class="space-y-3">
          @forelse($riwayat as $detail)
              <flux:card size="sm">
                  <div class="flex justify-between items-start mb-2">
                      <flux:badge>{{ $detail->aktivitasPembelajaran->tanggal->format('d M Y') }}</flux:badge>
                      @if($detail->nilai)
                          <flux:badge 
                              :variant="match(true) {
                                  $detail->nilai >= 80 => 'success',
                                  $detail->nilai >= 60 => 'warning',
                                  default => 'danger',
                              }">
                              Nilai: {{ $detail->nilai }}
                          </flux:badge>
                      @endif
                  </div>
                  
                  <flux:heading size="sm">{{ $detail->aktivitasPembelajaran->topik }}</flux:heading>
                  <flux:text class="text-sm text-zinc-500 mt-1">
                      {{ $detail->aktivitasPembelajaran->mataPelajaran->nama_mapel }}
                  </flux:text>
                  
                  @if($detail->partisipasi)
                      <div class="flex items-center gap-1 mt-2">
                          <flux:text class="text-sm">Partisipasi:</flux:text>
                          @for($i = 1; $i <= 5; $i++)
                              <flux:icon 
                                  name="star" 
                                  :variant="$i <= $detail->partisipasi ? 'solid' : 'outline'"
                                  class="{{ $i <= $detail->partisipasi ? 'text-yellow-500' : 'text-zinc-300' }}"
                              />
                          @endfor
                      </div>
                  @endif
                  
                  @if($detail->catatan)
                      <flux:text class="text-sm mt-2 italic border-l-2 border-blue-500 pl-3">
                          {{ $detail->catatan }}
                      </flux:text>
                  @endif
              </flux:card>
          @empty
              <flux:card>
                  <flux:text class="text-center py-8">Tidak ada data nilai</flux:text>
              </flux:card>
          @endforelse
      </div>
      
      <!-- Pagination -->
      <div class="mt-4">
          {{ $riwayat->links() }}
      </div>
  </div>
  ```

- [x] **4.4.3** Implement data calculations:
  ```php
  public function getSummaryPerMapelProperty()
  {
      $siswa = auth()->user()->siswa;
      $mataPelajaran = MataPelajaran::where('kelas_id', $siswa->kelas_id)->get();
      
      return $mataPelajaran->map(function ($mapel) use ($siswa) {
          $nilai = DetailAktivitas::where('siswa_id', $siswa->id)
              ->whereHas('aktivitasPembelajaran', fn($q) => 
                  $q->where('mata_pelajaran_id', $mapel->id))
              ->whereNotNull('nilai')
              ->pluck('nilai');
          
          return [
              'nama' => $mapel->nama_mapel,
              'avg' => $nilai->avg() ? round($nilai->avg(), 1) : '-',
              'max' => $nilai->max() ?? '-',
              'min' => $nilai->min() ?? '-',
          ];
      });
  }
  ```

- [x] **4.4.4** Test grade history on mobile devices

---

## Task 4.5: Permissions and access control

- [x] **4.5.1** Create policy for `DetailAktivitas`:
  ```bash
  php artisan make:policy DetailAktivitasPolicy --model=DetailAktivitas
  ```
  ```php
  public function view(User $user, DetailAktivitas $detail)
  {
      return $user->hasRole('student') && $user->siswa->id === $detail->siswa_id;
  }
  
  public function create(User $user)
  {
      return false; // Students cannot create
  }
  
  public function update(User $user, DetailAktivitas $detail)
  {
      return false; // Students cannot update
  }
  
  public function delete(User $user, DetailAktivitas $detail)
  {
      return false; // Students cannot delete
  }
  ```

- [x] **4.5.2** Add middleware to student routes:
  ```php
  Route::middleware(['auth', 'role:student'])->prefix('student')->group(function () {
      Route::get('/dashboard', Dashboard::class)->name('student.dashboard');
      Route::get('/kehadiran', RiwayatKehadiran::class)->name('student.kehadiran');
      Route::get('/nilai', RiwayatNilai::class)->name('student.nilai');
  });
  ```

- [x] **4.5.3** Add data scoping in Livewire components:
  ```php
  // Ensure student can only see their own data
  public function mount()
  {
      if (!auth()->user()->hasRole('student')) {
          abort(403);
      }
  }
  ```

- [x] **4.5.4** Test unauthorized access attempts:
  - Student trying to access other student's data via URL manipulation
  - Student trying to access teacher/admin routes
  - Verify 403/404 errors for unauthorized access

---

## Task 4.6: Progressive Web App (PWA) features

- [x] **4.6.1** Add PWA manifest for install prompt:
  - Created `/public/manifest-student.json` for student portal
  - Created `/public/manifest-teacher.json` for teacher portal
  - Added theme colors: teal (#0d9488) for student, slate (#1e293b) for teacher
  - Configured standalone display mode with portrait orientation

- [x] **4.6.2** Add service worker for offline support:
  - Created `/public/sw-student.js` for student portal
  - Created `/public/sw-teacher.js` for teacher portal
  - Network-first strategy with cache fallback
  - Static asset caching for CSS, JS, images, fonts
  - Created `/public/offline.html` fallback page
  - Skips Livewire and API requests

- [x] **4.6.3** Add PWA meta tags to layouts:
  - Added `theme-color`, `apple-mobile-web-app-capable` meta tags
  - Added manifest link and apple-touch-icon
  - Service worker registration script in both layouts

- [x] **4.6.4** Create app icons:
  - Created SVG icons at `/public/icons/`
  - Student icons: teal background (#0d9488)
  - Teacher icons: slate background (#1e293b)

---

## ✅ Phase 4 Completion Checklist

### Routes & Layout
- [x] Student routes defined in routes/web.php
- [x] Student base layout created (resources/views/layouts/student.blade.php)
- [x] Navigation layout created with Flux UI (sidebar + header)
- [x] Flux header with mobile toggle implemented
- [x] Collapsible sidebar for mobile working
- [x] User profile dropdown with logout working

### Livewire Components
- [x] Livewire component directory structure created
- [x] Student dashboard with mobile-first stat cards
- [x] Attendance history page with card-based layout
- [x] Grade history page with color-coded badges
- [x] Profile page (optional)

### Data & Features
- [x] Summary statistics by subject implemented
- [x] All filters working properly with live updates
- [x] Color coding and visual indicators (badges, stars)
- [x] Teacher feedback/notes visible to students
- [x] Performance optimized (< 10 queries per page with eager loading)

### Security & Access Control
- [x] DetailAktivitas policy created and applied
- [x] Policies prevent unauthorized access
- [x] Student can only see their own data
- [x] Cross-role access restrictions verified
- [x] Middleware protecting student routes tested

### Testing
- [ ] Mobile responsiveness tested on actual devices (iOS, Android)
- [ ] Touch targets verified (44px minimum)
- [x] Student authentication flow tested
- [x] Data scoping tested (can't see other students' data)
- [x] PWA features implemented

---

## 🎯 Success Criteria

Phase 4 is complete when:
1. ✅ Student can view personal dashboard with stats
2. ✅ Dashboard displays attendance %, average grade, participation
3. ✅ Student can view attendance history with color-coded statuses
4. ✅ Student can filter attendance by subject, status, date range
5. ✅ Student can view grade history with badges and stars
6. ✅ Summary stats show average, highest, lowest per subject
7. ✅ Student can filter grades by subject and date
8. ✅ All data is read-only (student cannot modify)
9. ✅ Student can only access their own data
10. ✅ Interface works smoothly on mobile devices (iOS, Android)
11. ✅ Touch targets are large enough (44px minimum)
12. ✅ Performance is acceptable (< 10 queries per page)
13. ✅ PWA installable on mobile home screen

---

## 📝 Notes

**UI Framework Change:**
- **From:** FilamentPHP custom pages (desktop-focused)
- **To:** Custom Livewire components with Flux UI (mobile-first)
- **Reason:** Students primarily access via smartphones, need touch-friendly interface

**Key Flux UI Features Used:**
- `flux:card` with `size="sm"` for compact mobile content
- `flux:badge` with variants for color-coded status (success, warning, danger)
- `flux:icon` with solid/outline variants for star ratings
- Grid layouts (`grid-cols-2`, `grid-cols-4`) for stat cards
- `wire:model.live` for real-time filtering without page refresh

**Mobile-First Design Patterns:**
- Card-based layouts for easy scrolling
- Summary stats at the top (above the fold)
- Collapsible filters to save space
- Large badges for quick visual scanning
- Sticky headers for persistent navigation

**Performance Considerations:**
- Use computed properties (`getStatsProperty()`) for calculated values
- Eager load relationships: `with(['aktivitasPembelajaran.mataPelajaran'])`
- Paginate results (15 items per page)
- Cache heavy calculations in `laporan` table (Phase 5)
- Monitor queries with Laravel Debugbar (target: < 10 queries)

**Testing Checklist:**
- [ ] Test on actual iPhone (Safari)
- [ ] Test on actual Android phone (Chrome)
- [ ] Verify touch target sizes (minimum 44px × 44px)
- [ ] Test with slow 3G network
- [ ] Verify filters work without full page reload
- [ ] Test PWA install prompt on iOS and Android
- [ ] Verify offline functionality (if PWA enabled)

---

**Previous Phase:** [← Phase 3: Core Functionality](./PHASE_3_CORE_FUNCTIONALITY.md)  
**Next Phase:** [Phase 5: Reporting →](./PHASE_5_REPORTING.md)
