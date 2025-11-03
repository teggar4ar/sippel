# Phase 3: Core Functionality - Learning Activities (Week 5-7)

**Objective:** Implement the core feature - teachers recording daily learning activities with attendance, grades, and participation using Flux UI for mobile-responsive interface.

**Estimated Time:** 48 hours (includes Flux UI + auth redirect: ~8 hours additional)

**UI Framework:** Flux UI (Livewire component library) for mobile-first teacher interface

**Authentication Architecture:** Single login at `/app/login` with role-based redirect:
- Admin → FilamentPHP at `/app` (desktop UI)
- Teacher → Flux UI at `/teacher` (mobile UI)
- Student → Flux UI at `/student` (mobile UI)

---

## Task 3.0: Pre-migration audit and preparation

- [ ] **3.0.1** Document existing FilamentPHP artifacts:
  - List all Resources in `app/Filament/Resources/`
  - List all Pages in `app/Filament/Pages/`
  - Identify which are admin-only vs teacher/student-specific
  - **Current state:** TahunAjaran, Kelas, MataPelajaran, Siswa, Users resources (all admin-only)

- [SKIP] **3.0.2** Backup current implementation:
  - Commit all Phase 1 & 2 work to Git
  - Tag as `pre-flux-migration`
  - Create database backup: `mysqldump -u root sippel_db > backup_pre_migration.sql`

- [SKIP] **3.0.3** Create rollback plan:
  - Document steps to revert if migration fails
  - Keep note of critical breaking points
  - Test database restore process

---

## Task 3.1: Configure role-based authentication redirect

**Purpose:** After successful login at `/app/login`, redirect users based on their role to appropriate UI.

- [ ] **3.1.1** Create custom login redirect middleware:
  ```bash
  php artisan make:middleware RedirectBasedOnRole
  ```

- [ ] **3.1.2** Implement redirect logic in middleware:
  ```php
  <?php
  
  namespace App\Http\Middleware;
  
  use Closure;
  use Illuminate\Http\Request;
  
  class RedirectBasedOnRole
  {
      public function handle(Request $request, Closure $next)
      {
          if (auth()->check()) {
              $user = auth()->user();
              
              // If user is on /app routes and is teacher/student, redirect to their interface
              if ($request->is('app') || $request->is('app/*')) {
                  if ($user->hasRole('teacher') && !$request->is('app/login', 'app/logout')) {
                      return redirect('/teacher');
                  }
                  
                  if ($user->hasRole('student') && !$request->is('app/login', 'app/logout')) {
                      return redirect('/student');
                  }
              }
              
              // If teacher tries to access /student or vice versa
              if ($request->is('teacher') || $request->is('teacher/*')) {
                  if (!$user->hasRole('teacher')) {
                      abort(403, 'Unauthorized');
                  }
              }
              
              if ($request->is('student') || $request->is('student/*')) {
                  if (!$user->hasRole('student')) {
                      abort(403, 'Unauthorized');
                  }
              }
          }
          
          return $next($request);
      }
  }
  ```

- [ ] **3.1.3** Register middleware in `bootstrap/app.php`:
  ```php
  ->withMiddleware(function (Middleware $middleware) {
      $middleware->web(append: [
          \App\Http\Middleware\RedirectBasedOnRole::class,
      ]);
  })
  ```

- [ ] **3.1.4** Update FilamentPHP login to redirect after authentication:
  - Create custom Login page: `app/Filament/Pages/Auth/Login.php`
  ```php
  <?php
  
  namespace App\Filament\Pages\Auth;
  
  use Filament\Pages\Auth\Login as BaseLogin;
  use Illuminate\Contracts\Support\Htmlable;
  
  class Login extends BaseLogin
  {
      public function getHeading(): string | Htmlable
      {
          return 'Login ke SIPPEL';
      }
      
      protected function getRedirectUrl(): string
      {
          $user = auth()->user();
          
          if ($user->hasRole('admin')) {
              return '/app';
          }
          
          if ($user->hasRole('teacher')) {
              return '/teacher';
          }
          
          if ($user->hasRole('student')) {
              return '/student';
          }
          
          return '/app'; // fallback
      }
  }
  ```

- [ ] **3.1.5** Update AdminPanelProvider to use custom login:
  ```php
  ->login(\App\Filament\Pages\Auth\Login::class)
  ```

- [ ] **3.1.6** Test authentication flow:
  - Login as admin → should see FilamentPHP at `/app`
  - Login as teacher → should redirect to `/teacher`
  - Login as student → should redirect to `/student`
  - Try accessing `/app` as teacher → should redirect to `/teacher`
  - Try accessing `/teacher` as student → should get 403 error

- [ ] **3.1.7** Update `User::canAccessPanel()` to restrict to admin-only:
  ```php
  // app/Models/User.php
  public function canAccessPanel(Panel $panel): bool
  {
      // After migration, only admins can access FilamentPHP panel
      return $this->hasRole('admin');
  }
  ```

- [ ] **3.1.8** Define teacher routes in `routes/web.php`:
  ```php
  // Teacher Routes (Livewire + FluxUI)
  Route::middleware(['auth', 'role:teacher'])->prefix('teacher')->name('teacher.')->group(function () {
      Route::get('/', \App\Livewire\Teacher\Dashboard::class)->name('dashboard');
      Route::get('/aktivitas', \App\Livewire\Teacher\AktivitasPembelajaran\ListAktivitas::class)
          ->name('aktivitas.list');
      Route::get('/aktivitas/create', \App\Livewire\Teacher\AktivitasPembelajaran\CreateAktivitas::class)
          ->name('aktivitas.create');
      Route::get('/aktivitas/{id}/edit', \App\Livewire\Teacher\AktivitasPembelajaran\EditAktivitas::class)
          ->name('aktivitas.edit');
      Route::get('/aktivitas/{id}', \App\Livewire\Teacher\AktivitasPembelajaran\ViewAktivitas::class)
          ->name('aktivitas.view');
      Route::get('/laporan', \App\Livewire\Teacher\Laporan::class)->name('laporan');
  });
  ```

- [ ] **3.1.9** Test routes are accessible with correct middleware:
  - Access `/teacher` as teacher → should work
  - Access `/teacher` as admin → should get 403
  - Access `/teacher` as student → should get 403
  - Access `/app` as teacher → should redirect to `/teacher`

---

## Task 3.2: Install and configure Flux UI

**Note:** Using Flux UI Free Tier (sufficient for this project)

### Dependency Considerations:

**Version Compatibility:**
- FilamentPHP 4.x uses Livewire 3.x ✅
- Flux UI Free requires Livewire 3.x ✅
- Both compatible — no version conflicts expected

**Shared Dependencies:**
- TailwindCSS (both use it) ✅
- Alpine.js (both include it) ✅
- Potential CSS class naming collisions (test thoroughly)

---

- [ ] **3.2.1a** Install Flux UI Free package:
  ```bash
  composer require livewire/flux
  ```

- [ ] **3.2.1b** Verify installation in `composer.json`:
  - Check `composer.json` shows `"livewire/flux"` dependency
  - Run `composer show --tree | grep flux` to verify no conflicts

- [ ] **3.2.1c** Check if Flux has installation command:
  ```bash
  php artisan list | grep flux
  # If flux:install exists, run it
  php artisan flux:install
  ```

- [ ] **3.2.1d** Verify Flux components are available:
  - Check `vendor/livewire/flux/resources/views/components/` directory exists
  - Verify blade components are registered

- [ ] **3.2.1e** Test Flux installation with simple component:
  - Create test route: `Route::get('/test-flux', fn() => view('test-flux'));`
  - Create `resources/views/test-flux.blade.php` with `<flux:button>Test</flux:button>`
  - Visit `/test-flux` and verify button renders

- [ ] **3.2.1f** Rebuild frontend assets:
  ```bash
  npm run build
  # Or for development: npm run dev
  ```

- [ ] **3.2.1g** Verify Flux styles are loaded:
  - Open browser DevTools → Network tab
  - Check for Flux CSS files loading
  - Inspect button element for Flux classes

- [ ] **3.2.2** Clear application caches after installation:
  ```bash
  php artisan config:clear
  php artisan route:clear
  php artisan view:clear
  php artisan optimize:clear
  ```

- [ ] **3.2.3** Create base layout structure:
  ```bash
  # Create teacher layout
  mkdir -p resources/views/layouts
  touch resources/views/layouts/teacher.blade.php
  ```

- [ ] **3.2.4** Configure Flux appearance in teacher layout:
  ```blade
  <!-- resources/views/layouts/teacher.blade.php -->
  <!DOCTYPE html>
  <html lang="id">
  <head>
      <meta charset="UTF-8">
      <meta name="viewport" content="width=device-width, initial-scale=1.0">
      <title>SIPPEL - Teacher Portal</title>
      
      @fluxStyles
      @vite('resources/css/app.css')
  </head>
  <body class="min-h-screen bg-white dark:bg-zinc-800">
      {{ $slot }}
      
      @fluxScripts
      @vite('resources/js/app.js')
  </body>
  </html>
  ```

- [ ] **3.2.5** Create teacher navigation layout with mobile collapsible sidebar:
  ```blade
  <!-- Inside teacher.blade.php body -->
  <flux:header class="sticky top-0 z-50">
      <flux:sidebar.toggle icon="bars-2" />
      <flux:heading size="sm">SIPPEL - Guru</flux:heading>
      <flux:spacer />
      <flux:dropdown position="bottom" align="end">
          <flux:profile avatar="{{ auth()->user()->avatar_url ?? 'https://ui-avatars.com/api/?name=' . urlencode(auth()->user()->nama) }}" />
          <flux:menu>
              <flux:menu.item href="{{ route('teacher.dashboard') }}">Dashboard</flux:menu.item>
              <flux:menu.separator />
              <flux:menu.item icon="arrow-right-start-on-rectangle" 
                  href="{{ route('filament.app.auth.logout') }}"
                  onclick="event.preventDefault(); document.getElementById('logout-form').submit();">
                  Logout
              </flux:menu.item>
          </flux:menu>
      </flux:dropdown>
  </flux:header>
  
  <flux:sidebar collapsible="mobile">
      <flux:sidebar.nav>
          <flux:sidebar.item icon="home" href="{{ route('teacher.dashboard') }}">Dashboard</flux:sidebar.item>
          <flux:sidebar.item icon="academic-cap" href="{{ route('teacher.aktivitas.list') }}">Aktivitas</flux:sidebar.item>
          <flux:sidebar.item icon="document-text" href="{{ route('teacher.laporan') }}">Laporan</flux:sidebar.item>
      </flux:sidebar.nav>
  </flux:sidebar>
  
  <flux:main class="px-4 py-6 max-w-7xl mx-auto">
      {{ $slot }}
  </flux:main>
  
  <form id="logout-form" action="{{ route('filament.app.auth.logout') }}" method="POST" class="hidden">
      @csrf
  </form>
  ```

- [ ] **3.2.6** Test Flux UI installation and responsive layout:
  - Test on desktop browser (Chrome, Firefox)
  - Test on mobile devices (iPhone, Android)
  - Verify sidebar collapses on mobile
  - Check touch targets are large enough (44px minimum)
  - Test navigation toggle on mobile

---

## Task 3.3: Learning activity Livewire component (Teacher interface)

**Note:** Using custom Livewire components with Flux UI instead of FilamentPHP resource for mobile-optimized experience.

**Directory Structure:**
```
app/Livewire/
├── Teacher/
│   ├── Dashboard.php
│   └── AktivitasPembelajaran/
│       ├── CreateAktivitas.php
│       ├── ListAktivitas.php
│       ├── EditAktivitas.php
│       └── ViewAktivitas.php

resources/views/livewire/
├── teacher/
│   ├── dashboard.blade.php
│   └── aktivitas-pembelajaran/
│       ├── create-aktivitas.blade.php
│       ├── list-aktivitas.blade.php
│       ├── edit-aktivitas.blade.php
│       └── view-aktivitas.blade.php
```

- [ ] **3.3.1** Generate Livewire components:
  ```bash
  php artisan make:livewire Teacher/AktivitasPembelajaran/CreateAktivitas
  php artisan make:livewire Teacher/AktivitasPembelajaran/ListAktivitas
  php artisan make:livewire Teacher/AktivitasPembelajaran/EditAktivitas
  php artisan make:livewire Teacher/AktivitasPembelajaran/ViewAktivitas
  ```

- [ ] **3.3.1b** Verify component namespaces:
  - Components should be: `App\Livewire\Teacher\AktivitasPembelajaran\CreateAktivitas`
  - Views should be: `livewire.teacher.aktivitas-pembelajaran.create-aktivitas`

- [ ] **3.2.2** Create mobile-optimized form layout with Flux UI:
  ```blade
  <flux:card class="space-y-6">
      <flux:heading size="lg">Buat Aktivitas Pembelajaran</flux:heading>
      
      <flux:input wire:model="tanggal" type="date" label="Tanggal" />
      
      <flux:select wire:model="mata_pelajaran_id" label="Mata Pelajaran" placeholder="Pilih mata pelajaran">
          @foreach($mataPelajaran as $mapel)
              <option value="{{ $mapel->id }}">{{ $mapel->nama_mapel }} - {{ $mapel->kelas->nama_lengkap }}</option>
          @endforeach
      </flux:select>
      
      <flux:input wire:model="topik" label="Topik" placeholder="Topik pembelajaran" />
      
      <flux:textarea wire:model="catatan" label="Catatan" rows="3" />
      
      <flux:button type="button" wire:click="nextStep" variant="primary" class="w-full">
          Lanjut ke Absensi
      </flux:button>
  </flux:card>
  ```

- [ ] **3.2.3** Add data filtering in component:
  - Filter `mata_pelajaran` by `guru_id = auth()->id()`
  - Auto-load `kelas_id` from selected subject
  - Set default `tanggal` to today

- [ ] **3.2.4** Add form validation:
  ```php
  protected $rules = [
      'tanggal' => 'required|date',
      'mata_pelajaran_id' => 'required|exists:mata_pelajaran,id',
      'topik' => 'required|max:200',
      'catatan' => 'nullable|max:500',
  ];
  ```

- [ ] **3.2.5** Test activity creation form on mobile devices

---

## Task 3.4: Attendance recording with Flux UI cards

**Mobile-optimized student attendance cards using Flux UI**

- [ ] **3.4.1** Create card-based attendance layout:
  ```blade
  <div class="space-y-4">
      <flux:heading size="lg">Absensi Siswa - {{ $kelas->nama_lengkap }}</flux:heading>
      
      @foreach($siswa as $s)
          <flux:card size="sm">
              <div class="flex justify-between items-start mb-3">
                  <flux:heading size="sm">{{ $s->user->nama }}</flux:heading>
                  <flux:badge>{{ $s->nis }}</flux:badge>
              </div>
              
              <!-- Large touch-friendly radio buttons for mobile -->
              <flux:radio.group 
                  wire:model="detailAktivitas.{{ $s->id }}.kehadiran" 
                  variant="cards" 
                  class="max-sm:flex-col mb-3">
                  <flux:radio value="Hadir" label="Hadir" checked />
                  <flux:radio value="Izin" label="Izin" />
                  <flux:radio value="Sakit" label="Sakit" />
                  <flux:radio value="Alpa" label="Alpa" />
              </flux:radio.group>
              
              <div class="grid grid-cols-2 gap-3 mb-3">
                  <flux:input 
                      wire:model="detailAktivitas.{{ $s->id }}.nilai" 
                      type="number" 
                      label="Nilai (0-100)" 
                      min="0" 
                      max="100" />
                  
                  <flux:select 
                      wire:model="detailAktivitas.{{ $s->id }}.partisipasi" 
                      label="Partisipasi">
                      <option value="">-</option>
                      <option value="1">1 ⭐</option>
                      <option value="2">2 ⭐⭐</option>
                      <option value="3">3 ⭐⭐⭐</option>
                      <option value="4">4 ⭐⭐⭐⭐</option>
                      <option value="5">5 ⭐⭐⭐⭐⭐</option>
                  </flux:select>
              </div>
              
              <flux:textarea 
                  wire:model="detailAktivitas.{{ $s->id }}.catatan" 
                  label="Catatan" 
                  rows="2" 
                  placeholder="Catatan untuk siswa (opsional)" />
          </flux:card>
      @endforeach
      
      <div class="sticky bottom-0 bg-white dark:bg-zinc-800 p-4 -mx-4 border-t">
          <flux:button wire:click="saveAktivitas" variant="primary" class="w-full">
              Simpan Aktivitas
          </flux:button>
      </div>
  </div>
  ```

- [ ] **3.3.2** Implement auto-population logic in Livewire component:
  - On `mata_pelajaran_id` selected, load all students from class
  - Pre-fill `detailAktivitas` array with default `kehadiran = 'Hadir'`
  - Set `siswa_id` for each student

- [ ] **3.3.3** Add save method with database transaction:
  ```php
  public function saveAktivitas()
  {
      $this->validate();
      
      DB::transaction(function () {
          $aktivitas = AktivitasPembelajaran::create([
              'tanggal' => $this->tanggal,
              'topik' => $this->topik,
              'catatan' => $this->catatan,
              'mata_pelajaran_id' => $this->mata_pelajaran_id,
              'kelas_id' => $this->kelas_id,
              'guru_id' => auth()->id(),
          ]);
          
          foreach ($this->detailAktivitas as $siswaId => $detail) {
              DetailAktivitas::create([
                  'aktivitas_pembelajaran_id' => $aktivitas->id,
                  'siswa_id' => $siswaId,
                  'kehadiran' => $detail['kehadiran'],
                  'nilai' => $detail['nilai'] ?? null,
                  'partisipasi' => $detail['partisipasi'] ?? null,
                  'catatan' => $detail['catatan'] ?? null,
              ]);
          }
      });
      
      session()->flash('success', 'Aktivitas berhasil disimpan!');
      return redirect()->route('teacher.aktivitas.list');
  }
  ```

- [ ] **3.3.4** Add validation rules:
  ```php
  protected function rules()
  {
      return [
          'detailAktivitas.*.kehadiran' => 'required|in:Hadir,Izin,Sakit,Alpa',
          'detailAktivitas.*.nilai' => 'nullable|numeric|min:0|max:100',
          'detailAktivitas.*.partisipasi' => 'nullable|integer|min:1|max:5',
          'detailAktivitas.*.catatan' => 'nullable|string|max:500',
      ];
  }
  ```

- [ ] **3.3.5** Test attendance recording on actual mobile devices (touch targets, scrolling)

---

## Task 3.4: Activity list view with Flux UI

- [ ] **3.4.1** Create mobile-optimized activity list:
  ```blade
  <flux:card class="space-y-4">
      <div class="flex justify-between items-center">
          <flux:heading size="lg">Aktivitas Saya</flux:heading>
          <flux:button href="{{ route('teacher.aktivitas.create') }}" variant="primary" size="sm">
              + Buat Baru
          </flux:button>
      </div>
      
      <!-- Filter section -->
      <div class="grid grid-cols-2 gap-3">
          <flux:select wire:model.live="filterMapel" label="Mata Pelajaran">
              <option value="">Semua</option>
              @foreach($mataPelajaran as $mapel)
                  <option value="{{ $mapel->id }}">{{ $mapel->nama_mapel }}</option>
              @endforeach
          </flux:select>
          
          <flux:input wire:model.live="filterTanggal" type="date" label="Tanggal" />
      </div>
      
      <!-- Activity cards for mobile -->
      <div class="space-y-3">
          @forelse($aktivitas as $a)
              <flux:card size="sm" class="hover:bg-zinc-50 dark:hover:bg-zinc-700">
                  <div class="flex justify-between items-start mb-2">
                      <flux:badge>{{ $a->tanggal->format('d M Y') }}</flux:badge>
                      <flux:badge variant="subtle">{{ $a->kelas->nama_lengkap }}</flux:badge>
                  </div>
                  
                  <flux:heading size="sm">{{ $a->topik }}</flux:heading>
                  <flux:text class="mt-1 text-sm">{{ $a->mataPelajaran->nama_mapel }}</flux:text>
                  
                  <div class="flex gap-2 mt-3">
                      <flux:button 
                          href="{{ route('teacher.aktivitas.edit', $a->id) }}" 
                          variant="ghost" 
                          size="sm">
                          Edit
                      </flux:button>
                      <flux:button 
                          href="{{ route('teacher.aktivitas.view', $a->id) }}" 
                          variant="subtle" 
                          size="sm">
                          Lihat Detail
                      </flux:button>
                  </div>
              </flux:card>
          @empty
              <flux:text class="text-center py-8">Belum ada aktivitas</flux:text>
          @endforelse
      </div>
      
      <!-- Pagination -->
      <div class="mt-4">
          {{ $aktivitas->links() }}
      </div>
  </flux:card>
  ```

- [ ] **3.4.2** Implement filtering and sorting in Livewire:
  ```php
  public function getAktivitasProperty()
  {
      return AktivitasPembelajaran::query()
          ->where('guru_id', auth()->id())
          ->when($this->filterMapel, fn($q) => $q->where('mata_pelajaran_id', $this->filterMapel))
          ->when($this->filterTanggal, fn($q) => $q->whereDate('tanggal', $this->filterTanggal))
          ->with(['mataPelajaran', 'kelas', 'detailAktivitas'])
          ->latest('tanggal')
          ->paginate(10);
  }
  ```

- [ ] **3.4.3** Test list view and filters on mobile

---

## Task 3.5: Edit functionality for activities

- [ ] **3.5.1** Create edit Livewire component similar to create:
  - Load existing `AktivitasPembelajaran` record
  - Pre-fill form with existing data
  - Load existing `DetailAktivitas` records into array

- [ ] **3.5.2** Implement update logic:
  ```php
  public function updateAktivitas()
  {
      $this->validate();
      
      DB::transaction(function () {
          $this->aktivitas->update([
              'tanggal' => $this->tanggal,
              'topik' => $this->topik,
              'catatan' => $this->catatan,
          ]);
          
          foreach ($this->detailAktivitas as $siswaId => $detail) {
              DetailAktivitas::updateOrCreate(
                  [
                      'aktivitas_pembelajaran_id' => $this->aktivitas->id,
                      'siswa_id' => $siswaId,
                  ],
                  [
                      'kehadiran' => $detail['kehadiran'],
                      'nilai' => $detail['nilai'] ?? null,
                      'partisipasi' => $detail['partisipasi'] ?? null,
                      'catatan' => $detail['catatan'] ?? null,
                  ]
              );
          }
      });
      
      session()->flash('success', 'Aktivitas berhasil diperbarui!');
      return redirect()->route('teacher.aktivitas.list');
  }
  ```

- [ ] **3.5.3** Add view-only page with Flux UI cards (read-only mode)

- [ ] **3.5.4** Test edit and update functionality

---

## Task 3.6: Automatic calculations (Model accessors/scopes)

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

---

## Task 3.5: Activity list and management

- [ ] **3.5.1** Add search functionality: Search by topic, date

- [ ] **3.5.2** Add bulk actions: Delete multiple activities

- [ ] **3.5.3** Add custom action: Duplicate activity (copy to new date)

- [ ] **3.5.4** Add summary widget above table:
  - Total activities this month
  - Average class attendance
  - Most active subject

- [ ] **3.5.5** Test all teacher workflows end-to-end

- [ ] **3.6.3** Create `Siswa` model method: `getAveragePartisipasiAttribute()`
  - Calculate: AVG(partisipasi) across all activities
  - Filter out null values

- [ ] **3.6.4** Add eager loading examples:
  ```php
  // In controller/Livewire component
  $siswa = Siswa::with(['detailAktivitas.aktivitasPembelajaran.mataPelajaran'])
      ->where('kelas_id', $kelasId)
      ->get();
  ```

- [ ] **3.6.5** Test performance with Laravel Debugbar (target: < 15 queries)

---

## Task 3.7: Teacher dashboard with Flux UI

- [ ] **3.7.1** Create dashboard Livewire component:
  ```bash
  php artisan make:livewire Teacher/Dashboard
  ```

- [ ] **3.7.2** Build mobile-optimized dashboard:
  ```blade
  <div class="space-y-6">
      <flux:heading size="xl">Selamat datang, {{ auth()->user()->nama }}</flux:heading>
      
      <!-- Stats cards -->
      <div class="grid grid-cols-2 gap-4">
          <flux:card size="sm">
              <flux:heading size="sm">Aktivitas Minggu Ini</flux:heading>
              <flux:text class="text-2xl font-bold mt-2">{{ $aktivitasMingguIni }}</flux:text>
          </flux:card>
          
          <flux:card size="sm">
              <flux:heading size="sm">Rata-rata Kehadiran</flux:heading>
              <flux:text class="text-2xl font-bold mt-2">{{ $rataKehadiran }}%</flux:text>
          </flux:card>
      </div>
      
      <!-- Quick actions -->
      <flux:card class="space-y-3">
          <flux:heading size="sm">Aksi Cepat</flux:heading>
          <flux:button href="{{ route('teacher.aktivitas.create') }}" variant="primary" class="w-full">
              + Buat Aktivitas Baru
          </flux:button>
          <flux:button href="{{ route('teacher.aktivitas.list') }}" variant="subtle" class="w-full">
              Lihat Semua Aktivitas
          </flux:button>
      </flux:card>
      
      <!-- Recent activities -->
      <flux:card class="space-y-4">
          <flux:heading size="sm">Aktivitas Terbaru</flux:heading>
          @foreach($recentAktivitas as $a)
              <div class="border-b pb-3 last:border-0">
                  <flux:text class="font-medium">{{ $a->topik }}</flux:text>
                  <flux:text class="text-sm text-zinc-500">
                      {{ $a->mataPelajaran->nama_mapel }} - {{ $a->tanggal->format('d M Y') }}
                  </flux:text>
              </div>
          @endforeach
      </flux:card>
  </div>
  ```

- [ ] **3.7.3** Test dashboard on mobile devices

---

## ✅ Phase 3 Completion Checklist

### Pre-Migration
- [ ] Database and code backed up with git tag
- [ ] Existing FilamentPHP resources documented
- [ ] Rollback plan created

### Authentication & Routes
- [ ] RedirectBasedOnRole middleware created and registered
- [ ] Custom Login page with role-based redirect implemented
- [ ] AdminPanelProvider updated with custom login
- [ ] User::canAccessPanel() updated to admin-only
- [ ] Teacher routes defined in routes/web.php
- [ ] All routes tested with correct middleware

### Flux UI Installation
- [ ] Flux UI Free package installed
- [ ] Installation verified (no conflicts)
- [ ] Flux components available and tested
- [ ] Assets rebuilt (npm run build)
- [ ] Application caches cleared
- [ ] Flux styles loading in browser

### Layout & Navigation
- [ ] Teacher base layout created (resources/views/layouts/teacher.blade.php)
- [ ] Flux header with mobile toggle implemented
- [ ] Collapsible sidebar for mobile created
- [ ] Navigation items added (Dashboard, Aktivitas, Laporan)
- [ ] User profile dropdown with logout working

### Livewire Components
- [ ] Livewire component directory structure created
- [ ] Create activity component with mobile-optimized form
- [ ] Attendance recording with Flux card-based layout (large touch targets)
- [ ] Activity list view with filters and mobile cards
- [ ] Edit functionality for existing activities
- [ ] View-only page for activity details
- [ ] Teacher dashboard with stats and quick actions

### Data & Performance
- [ ] Automatic calculations (attendance %, grades, participation) implemented
- [ ] Database transactions ensure data integrity
- [ ] Query performance optimized with eager loading (< 15 queries per page)
- [ ] Validation rules implemented and tested

### Testing
- [ ] Mobile responsiveness tested on actual devices (iOS, Android)
- [ ] Touch targets verified (44px minimum)
- [ ] Authentication flow tested for all roles
- [ ] Cross-role access restrictions verified
- [ ] Migration-specific tests passed (teacher can't access /app)

---

## 🎯 Success Criteria

Phase 3 is complete when:
1. ✅ Flux UI properly installed and configured
2. ✅ Teacher can create daily learning activities via mobile-friendly form
3. ✅ Teacher can select subject and class
4. ✅ Student list auto-populates with card-based layout
5. ✅ Teacher can record attendance with large touch-friendly radio buttons
6. ✅ Teacher can input grades (0-100) and participation (1-5)
7. ✅ Teacher can add individual feedback/notes per student
8. ✅ Teacher can edit existing activities
9. ✅ Activity list displays properly on mobile with filters
10. ✅ Automatic calculations work correctly
11. ✅ All validations prevent invalid data
12. ✅ Performance is acceptable (< 15 queries per page)
13. ✅ Interface is usable on actual mobile devices (iPhone, Android)

---

## 📝 Notes

**UI Framework Change:**
- **From:** FilamentPHP resource (desktop-focused)
- **To:** Custom Livewire components with Flux UI (mobile-first)
- **Reason:** Teachers need mobile-friendly interface for daily operations

**Key Flux UI Features Used:**
- `flux:card` with `size="sm"` for compact mobile layouts
- `flux:radio.group variant="cards"` for large touch targets (44px+)
- `flux:button` with `class="w-full"` for full-width mobile buttons
- `max-sm:flex-col` utility for vertical stacking on mobile
- Sticky bottom bar for save button (always accessible)

**Performance Optimizations:**
- Use `wire:model.live` for real-time filtering
- Implement `wire:key` in loops to prevent re-rendering issues
- Eager load relationships with `with()`
- Use database transactions for atomic operations
- Test with 30+ students to verify performance

**Mobile Testing:**
- Test on actual devices (not just browser responsive mode)
- Verify touch target sizes (minimum 44px)
- Check keyboard behavior on input fields
- Verify scrolling and sticky elements
- Test on both iOS and Android if possible

---

**Previous Phase:** [← Phase 2: Master Data](./PHASE_2_MASTER_DATA.md)  
**Next Phase:** [Phase 4: Student Interface →](./PHASE_4_STUDENT_INTERFACE.md)
