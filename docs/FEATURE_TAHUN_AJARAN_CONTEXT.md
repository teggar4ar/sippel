# Feature: Tahun Ajaran Context Switcher

## 📋 Overview

Implementasi **global context switcher** untuk tahun ajaran yang memungkinkan **guru dan siswa** bekerja dengan data dari tahun ajaran manapun tanpa harus mengubah status tahun ajaran aktif di sistem.

## 🎯 Problem Statement

Saat ini sistem hanya menampilkan data berdasarkan tahun ajaran yang **status = true** (aktif). Ini menyebabkan:

- ❌ Guru tidak bisa review data tahun lalu
- ❌ Tidak bisa mempersiapkan data untuk semester depan
- ❌ Tidak fleksibel untuk bekerja dengan multiple tahun ajaran
- ❌ Admin harus sering mengubah status tahun ajaran aktif
- ❌ Siswa tidak bisa melihat riwayat kehadiran & nilai dari tahun ajaran sebelumnya
- ❌ Dashboard siswa hanya menampilkan data dari kelas saat ini, tanpa konteks tahun ajaran
- ❌ Halaman Kehadiran & Nilai siswa tidak memiliki filter tahun ajaran sama sekali

## � Current State Analysis

Hasil analisis penggunaan `TahunAjaran` di codebase saat ini:

### Teacher Panel

| File | Pattern | Notes |
|------|---------|-------|
| `Teacher\Dashboard.php` | `where('status', true)->first()` (1×, via `activeTahunAjaran` computed) | Filters `mySubjects`, `dashboardStats`, `partisipasiPerKelas` |
| `Teacher\AktivitasPembelajaran\CreateAktivitas.php` | `TahunAjaran::getActive()` (3×) | Filters `tingkatKelasList`, `grupKelasList`, `mataPelajaran` |
| `Teacher\AktivitasPembelajaran\EditAktivitas.php` | ❌ None | Loads activity by ID, no year filter |
| `Teacher\AktivitasPembelajaran\ListAktivitas.php` | ❌ None | Lists by `guru_id` + search/date filters, **no year scope** |
| `Teacher\AktivitasPembelajaran\ViewAktivitas.php` | ❌ None | Loads single activity by ID |
| `Teacher\Laporan.php` | `where('status', true)` in `mount()` + own dropdown (`$tahunAjaranId`) | Already has per-page year selector; default needs updating |

### Student Panel

| File | Pattern | Notes |
|------|---------|-------|
| `Student\Dashboard.php` | ❌ None | All data from `$siswa->detailAktivitas` — **no year scope, mixes all years** |
| `Student\RiwayatKehadiran.php` | ❌ None | Filters by mapel/status/date — **no year scope, mixes all years** |
| `Student\RiwayatNilai.php` | ❌ None | Filters by mapel/date — **no year scope, mixes all years** |
| `Student\LaporanSaya.php` | `where('status', true)` in `mount()` + own dropdown (`$tahunAjaranId`) | Already has per-page year selector; default needs updating |
| `Student\Profil.php` | ❌ None | Static user/siswa info, no year relevance |

### Inconsistencies Found

1. **Mixed patterns**: `CreateAktivitas` uses model's `getActive()` method, while `Dashboard` and `Laporan` duplicate logic with `where('status', true)->first()`
2. **Student panel gap**: 3 of 5 student pages have **zero tahun ajaran filtering** — all historical data is displayed without year context
3. **ListAktivitas gap**: Teacher's activity list has no year scope, showing activities across all years
4. **LaporanSaya redundancy**: Already has its own year selector, but defaults to active year — should default to global context instead

## �💡 Solution

Buat **session-based context switcher** di header yang memungkinkan user (guru & siswa) memilih tahun ajaran untuk "context" mereka, mirip dengan:
- Fiscal year selector di aplikasi accounting
- Workspace switcher di aplikasi project management
- Environment switcher di development tools

## 🏗️ Technical Design

### 1. Session Management

**Store tahun ajaran ID di session:**

```php
// Set context
session(['tahun_ajaran_context' => $tahunAjaranId]);

// Get context
$contextId = session('tahun_ajaran_context');
```

### 2. Model Enhancement

**Tambahkan method di `TahunAjaran` model:**

```php
// app/Models/TahunAjaran.php

/**
 * Get tahun ajaran context untuk user saat ini
 * Priority:
 * 1. Session context (jika user sudah memilih)
 * 2. Fallback ke tahun ajaran aktif (status = true)
 */
public static function getContext(): ?self
{
    $contextId = session('tahun_ajaran_context');
    
    if ($contextId) {
        $tahunAjaran = self::find($contextId);
        if ($tahunAjaran) {
            return $tahunAjaran;
        }
    }
    
    // Fallback ke tahun ajaran aktif
    return self::getActive();
}

/**
 * Set tahun ajaran context untuk user saat ini
 */
public static function setContext(?int $tahunAjaranId): void
{
    if ($tahunAjaranId === null) {
        session()->forget('tahun_ajaran_context');
    } else {
        session(['tahun_ajaran_context' => $tahunAjaranId]);
    }
}
```

### 3. Shared Livewire Component untuk Selector

Karena selector digunakan oleh **kedua panel** (teacher & student), component ditempatkan di namespace shared.

**Create component:**

```bash
php artisan make:livewire Components/TahunAjaranSelector
```

**Component code:**

```php
// app/Livewire/Components/TahunAjaranSelector.php

namespace App\Livewire\Components;

use App\Models\TahunAjaran;
use Livewire\Attributes\Computed;
use Livewire\Component;

class TahunAjaranSelector extends Component
{
    public ?int $selectedTahunAjaranId = null;

    /**
     * Theme variant: 'slate' for teacher, 'teal' for student.
     * Passed from the layout to match panel styling.
     */
    public string $variant = 'slate';

    public function mount(): void
    {
        $context = TahunAjaran::getContext();
        $this->selectedTahunAjaranId = $context?->id;
    }

    #[Computed]
    public function tahunAjaranList()
    {
        return TahunAjaran::orderByDesc('nama_tahun')
            ->orderByDesc('semester')
            ->get();
    }

    public function updatedSelectedTahunAjaranId($value): void
    {
        TahunAjaran::setContext($value);
        
        // Emit event untuk refresh semua component yang depend on context
        $this->dispatch('tahun-ajaran-context-changed');
        
        // Refresh page untuk update semua data
        $this->redirect(request()->url(), navigate: true);
    }

    public function render()
    {
        return view('livewire.components.tahun-ajaran-selector');
    }
}
```

**View:**

```blade
{{-- resources/views/livewire/components/tahun-ajaran-selector.blade.php --}}

<div class="flex items-center gap-2">
    <flux:icon name="calendar" class="w-4 h-4 {{ $variant === 'teal' ? 'text-teal-500 dark:text-teal-400' : 'text-slate-500 dark:text-slate-400' }}" />
    <flux:select 
        wire:model.live="selectedTahunAjaranId"
        class="text-sm rounded-lg focus:ring-0
            {{ $variant === 'teal'
                ? 'border-teal-300 dark:border-teal-600 dark:bg-slate-800 focus:border-teal-500'
                : 'border-slate-300 dark:border-slate-600 dark:bg-slate-800 focus:border-blue-500'
            }}"
    >
        @foreach($this->tahunAjaranList as $ta)
            <option value="{{ $ta->id }}">
                {{ $ta->nama_tahun }} - {{ $ta->semester }}
                @if($ta->status) ⭐ @endif
            </option>
        @endforeach
    </flux:select>
</div>
```

### 4. Update Layouts

#### 4a. Teacher Layout

**Tambahkan selector di header teacher layout:**

```blade
{{-- resources/views/layouts/teacher.blade.php --}}

<header class="sticky top-0 z-40 bg-white dark:bg-slate-900 border-b border-slate-200 dark:border-slate-700">
    <div class="flex items-center justify-between px-4 py-3">
        {{-- Logo & Navigation --}}
        <div class="flex items-center gap-4">
            <h1 class="text-lg font-bold">SIPPEL Guru</h1>
        </div>

        {{-- Tahun Ajaran Context Switcher --}}
        <div class="flex items-center gap-4">
            @livewire('components.tahun-ajaran-selector', ['variant' => 'slate'])
            
            {{-- User menu, notifications, etc --}}
            <div>...</div>
        </div>
    </div>
</header>
```

#### 4b. Student Layout

**Tambahkan selector di header student layout:**

```blade
{{-- resources/views/layouts/student.blade.php --}}

<header class="sticky top-0 z-40 bg-white dark:bg-slate-900 border-b border-teal-200 dark:border-teal-700">
    <div class="flex items-center justify-between px-4 py-3">
        {{-- Logo & Navigation --}}
        <div class="flex items-center gap-4">
            <h1 class="text-lg font-bold">SIPPEL Siswa</h1>
        </div>

        {{-- Tahun Ajaran Context Switcher --}}
        <div class="flex items-center gap-4">
            @livewire('components.tahun-ajaran-selector', ['variant' => 'teal'])
            
            {{-- User menu, notifications, etc --}}
            <div>...</div>
        </div>
    </div>
</header>
```

### 5. Update Queries

**Replace semua `TahunAjaran::getActive()` dan `TahunAjaran::where('status', true)` dengan `TahunAjaran::getContext()`**

#### 5a. Teacher Panel Query Updates

##### `Teacher\Dashboard.php`

Uses `TahunAjaran::where('status', true)->first()` in 1 computed (`activeTahunAjaran`), referenced by 3 others (`mySubjects`, `dashboardStats`, `partisipasiPerKelas`).

```php
// BEFORE:
#[Computed]
public function activeTahunAjaran(): ?TahunAjaran
{
    return TahunAjaran::where('status', true)->first();
}

// AFTER:
#[Computed]
public function activeTahunAjaran(): ?TahunAjaran
{
    return TahunAjaran::getContext();
}
```

##### `Teacher\AktivitasPembelajaran\CreateAktivitas.php`

Uses `TahunAjaran::getActive()` in 3 computeds (`tingkatKelasList`, `grupKelasList`, `mataPelajaran`).

```php
// BEFORE:
$activeTahunAjaran = TahunAjaran::getActive();

// AFTER:
$contextTahunAjaran = TahunAjaran::getContext();
```

##### `Teacher\Laporan.php`

Uses `TahunAjaran::where('status', true)->first()` in `mount()` to set default.
Already has its own tahun ajaran selector dropdown — **no query changes needed**, but `mount()` default should use context.

```php
// BEFORE (mount):
$activeTahunAjaran = TahunAjaran::where('status', true)->first();

// AFTER (mount):
$contextTahunAjaran = TahunAjaran::getContext();
```

##### `Teacher\AktivitasPembelajaran\EditAktivitas.php`

No TahunAjaran references — **no changes needed**.

##### `Teacher\AktivitasPembelajaran\ListAktivitas.php`

No TahunAjaran references — **consider adding context filter** to scope activities to the selected year.

```php
// SUGGESTION: Add tahun ajaran context filter to query
->whereHas('kelas', fn ($q) => $q->where('tahun_ajaran_id', TahunAjaran::getContext()?->id))
```

##### `Teacher\AktivitasPembelajaran\ViewAktivitas.php`

No TahunAjaran references — **no changes needed** (loads single activity by ID).

#### 5b. Student Panel Query Updates

> **Key difference from teacher**: Student components currently have **no tahun ajaran filtering at all** (except `LaporanSaya`). They query `DetailAktivitas` and `MataPelajaran` based on `$siswa->kelas_id` or `$siswa->id` without scoping to any academic year. This means all historical data across all tahun ajaran is mixed together.
>
> With the context switcher, student components need to add tahun ajaran filtering via the `kelas.tahun_ajaran_id` relationship chain:
> `detail_aktivitas → aktivitas_pembelajaran → kelas → tahun_ajaran_id`

##### `Student\Dashboard.php`

Currently: No TahunAjaran reference. Loads `$siswa->detailAktivitas` without year filter.
Impact: `performancePerMapel`, `motivationalMessage`, `attendanceStreak`, `totalAktivitas`, and `recentAktivitas` all show data across all years.

```php
// AFTER: Scope performancePerMapel to context
#[Computed]
public function performancePerMapel(): Collection
{
    $siswa = $this->siswa();
    if (! $siswa instanceof Siswa || ! $siswa->kelas_id) {
        return collect();
    }

    $contextTahunAjaran = TahunAjaran::getContext();
    if (! $contextTahunAjaran) {
        return collect();
    }

    // Get subjects from classes in the context tahun ajaran
    $subjects = MataPelajaran::whereHas(
        'kelas', fn ($q) => $q->where('tahun_ajaran_id', $contextTahunAjaran->id)
    )->whereHas(
        'kelas.siswa', fn ($q) => $q->where('siswa.id', $siswa->id)
    )->get();

    return $subjects->map(function (MataPelajaran $mapel) use ($siswa): array {
        $avgNilai = $siswa->getAverageGrade($mapel->id);
        return [
            'nama_mapel' => $mapel->nama_mapel,
            'avg_nilai' => $avgNilai ?? 0,
        ];
    })->filter(fn (array $data): bool => $data['avg_nilai'] > 0)
        ->sortByDesc('avg_nilai')
        ->take(5)
        ->values();
}

// AFTER: Scope totalAktivitas and recentAktivitas in render()
public function render(): View
{
    $siswa = $this->siswa();
    $contextTahunAjaran = TahunAjaran::getContext();

    $totalAktivitas = 0;
    $recentAktivitas = collect();

    if ($siswa instanceof Siswa && $contextTahunAjaran) {
        $totalAktivitas = $siswa->detailAktivitas()
            ->whereHas('aktivitasPembelajaran.kelas',
                fn ($q) => $q->where('tahun_ajaran_id', $contextTahunAjaran->id)
            )->count();

        $recentAktivitas = DetailAktivitas::query()
            ->where('siswa_id', $siswa->id)
            ->whereHas('aktivitasPembelajaran.kelas',
                fn ($q) => $q->where('tahun_ajaran_id', $contextTahunAjaran->id)
            )
            // ...existing ordering & limit
            ->limit(5)
            ->get();
    }
    // ...
}
```

##### `Student\RiwayatKehadiran.php`

Currently: No TahunAjaran reference. Queries `DetailAktivitas` with filters for mapel, status, date range — but no year scope.
Impact: Shows attendance from **all years** mixed together.

```php
// AFTER: Add context filter to stats query and riwayat query
$contextTahunAjaran = TahunAjaran::getContext();

$query = DetailAktivitas::query()
    ->where('siswa_id', $siswa->id)
    ->whereHas('aktivitasPembelajaran', fn ($q) => $q->whereNull('deleted_at'))
    ->when($contextTahunAjaran, fn ($q) => $q->whereHas(
        'aktivitasPembelajaran.kelas',
        fn ($sq) => $sq->where('tahun_ajaran_id', $contextTahunAjaran->id)
    ));

// Same filter applied to the mataPelajaran dropdown:
$mataPelajaran = MataPelajaran::query()
    ->whereHas('aktivitasPembelajaran.detailAktivitas', fn ($q) => $q->where('siswa_id', $siswa->id))
    ->when($contextTahunAjaran, fn ($q) => $q->whereHas(
        'kelas', fn ($sq) => $sq->where('tahun_ajaran_id', $contextTahunAjaran->id)
    ))
    ->orderBy('nama_mapel')
    ->get();
```

##### `Student\RiwayatNilai.php`

Currently: No TahunAjaran reference. Same pattern as RiwayatKehadiran.
Impact: Shows grades from **all years** mixed together.

```php
// AFTER: Same context filter pattern as RiwayatKehadiran
$contextTahunAjaran = TahunAjaran::getContext();

// Apply to both summaryPerMapel and the riwayat query:
->when($contextTahunAjaran, fn ($q) => $q->whereHas(
    'aktivitasPembelajaran.kelas',
    fn ($sq) => $sq->where('tahun_ajaran_id', $contextTahunAjaran->id)
))
```

##### `Student\LaporanSaya.php`

Currently: Already has its own tahun ajaran selector (`$tahunAjaranId` property with dropdown). Defaults to `TahunAjaran::where('status', true)->first()` in `mount()`.
Impact: **Only `mount()` default needs updating** to use global context. The existing per-page dropdown can remain for report-specific override.

```php
// BEFORE (mount):
$activeTahunAjaran = TahunAjaran::where('status', true)->first();

// AFTER (mount):
$contextTahunAjaran = TahunAjaran::getContext();
if ($contextTahunAjaran) {
    $this->tahunAjaranId = $contextTahunAjaran->id;
}
```

##### `Student\Profil.php`

No TahunAjaran references — **no changes needed** (displays static user/siswa info).

## 🎨 UI/UX Considerations

### Teacher Panel — Desktop
```
┌─────────────────────────────────────────────────────────┐
│  SIPPEL Guru    [🗓 2027/2028 Ganjil ⭐ ▼]    🔔  👤   │
└─────────────────────────────────────────────────────────┘
```

### Teacher Panel — Mobile
```
┌──────────────────────────────┐
│  ☰  SIPPEL    🔔  👤        │
│  [🗓 2027/2028 Ganjil ⭐ ▼] │
└──────────────────────────────┘
```

### Student Panel — Desktop
```
┌─────────────────────────────────────────────────────────┐
│  SIPPEL Siswa   [🗓 2027/2028 Ganjil ⭐ ▼]    👤       │
└─────────────────────────────────────────────────────────┘
```

### Student Panel — Mobile
```
┌──────────────────────────────┐
│  ☰  SIPPEL         👤       │
│  [🗓 2027/2028 Ganjil ⭐ ▼] │
└──────────────────────────────┘
```

### Dropdown Options (shared)
```
┌────────────────────────────┐
│ ⭐ 2027/2028 Ganjil       │ ← Active
│   2026/2027 Genap         │
│   2026/2027 Ganjil        │
│   2025/2026 Genap         │
│   2025/2026 Ganjil        │
└────────────────────────────┘
```

## 🚀 Benefits

### For Teachers
1. ✅ Dapat review data tahun lalu untuk referensi
2. ✅ Dapat mempersiapkan data untuk semester/tahun depan
3. ✅ Lebih fleksibel dalam bekerja dengan data
4. ✅ Tidak perlu menunggu admin mengubah tahun ajaran aktif

### For Students
1. ✅ Dapat melihat riwayat kehadiran dari tahun ajaran sebelumnya
2. ✅ Dapat membandingkan nilai antar semester/tahun
3. ✅ Dashboard menampilkan data yang relevan dengan konteks yang dipilih
4. ✅ Akses laporan historis tanpa tergantung status tahun ajaran aktif
5. ✅ Halaman Kehadiran & Nilai kini terfilter per tahun ajaran (sebelumnya campur semua)

### For Admins
1. ✅ Tidak perlu sering mengubah status tahun ajaran
2. ✅ Sistem lebih stabil karena tahun aktif jarang berubah
3. ✅ Lebih mudah untuk testing dan data migration

### For System
1. ✅ Better separation of concerns
2. ✅ More flexible data access patterns
3. ✅ Supports multi-tenancy like behavior per user
4. ✅ Shared component reduces code duplication between panels

## ⚠️ Important Notes

### Session Lifecycle
- Context disimpan di session
- Akan reset ketika user logout
- Default fallback ke tahun ajaran aktif

### Data Integrity
- Context hanya mempengaruhi **read operations**
- **Write operations** (create aktivitas) selalu menggunakan context
- Validasi tetap diperlukan untuk ensure data consistency

### Permission
- Guru hanya bisa akses tahun ajaran yang mereka pernah mengajar
- Siswa bisa akses tahun ajaran dimana mereka pernah terdaftar di suatu kelas
- Pertimbangkan untuk add permission check di `setContext()`

### Student-Specific Considerations
- Siswa mungkin pindah kelas antar tahun ajaran → query harus join via `kelas.tahun_ajaran_id`, bukan `siswa.kelas_id` saat ini
- Jika siswa tidak memiliki `DetailAktivitas` di tahun ajaran yang dipilih, tampilkan pesan "Tidak ada data" (bukan error)
- `LaporanSaya.php` sudah memiliki dropdown tahun ajaran sendiri — tetap pertahankan sebagai override lokal, tapi default ke global context

## 🔄 Migration Strategy

### Step 1: Add Feature (Non-Breaking)
- Add new methods without removing old ones
- Keep `getActive()` for backward compatibility
- Test thoroughly in development

### Step 2: Gradual Migration
- Update one component at a time
- Test after each update
- Monitor for issues

### Step 3: Full Rollout
- Update all components (teacher + student)
- Deprecate direct usage of `getActive()` and `where('status', true)` in teacher & student context
- Update documentation

## 📚 Related Features

### Future Enhancements
1. **Preset Filters**: Quick switch to "Current", "Last Year", "Next Year"
2. **Favorites**: Pin frequently accessed tahun ajaran
3. **Comparison Mode**: Compare data across multiple tahun ajaran
4. **Analytics**: Show statistics per tahun ajaran

### Integration Points
- Dashboard widgets should respect context
- Reports should allow context override
- PDF exports should include context info

## 🧪 Testing Scenarios

### Test Cases — Teacher
1. ✅ Teacher selects different tahun ajaran → dashboard, aktivitas, laporan data updates
2. ✅ Teacher logout → context reset to active
3. ✅ Tahun ajaran deleted → fallback to active
4. ✅ No active tahun ajaran → show error message
5. ✅ Create aktivitas → uses current context
6. ✅ Switch context → page refreshes correctly

### Test Cases — Student
1. ✅ Student selects different tahun ajaran → dashboard, kehadiran, nilai data updates
2. ✅ Student logout → context reset to active
3. ✅ Student has no data in selected tahun ajaran → shows empty state message
4. ✅ LaporanSaya per-page dropdown defaults to global context on page load
5. ✅ Student changes global context → LaporanSaya defaults update on next visit
6. ✅ MataPelajaran dropdown in Kehadiran/Nilai filters correctly for selected tahun ajaran
7. ✅ performancePerMapel shows subjects from context tahun ajaran only
8. ✅ attendanceStreak calculates only within context tahun ajaran

### Edge Cases
- User has no mata pelajaran in selected context
- Selected tahun ajaran becomes deleted
- Multiple tabs with different contexts
- Session expired
- Student transferred to different class between years (different `kelas_id`)
- Student has data in some years but not others
- Context sync between global selector and LaporanSaya local selector

## 📖 Documentation Updates

### User Guide
- Add section "Memilih Tahun Ajaran"
- Add screenshots of selector
- Explain when to use context switching

### Developer Guide
- Document `getContext()` vs `getActive()`
- Best practices for using context in queries
- How to handle context in new features

---

**Status**: 📋 Planned (Not Implemented)

**Priority**: 🔥 High

**Estimated Effort**: 6-8 hours (expanded from 4-6 to include student panel)

**Dependencies**: None

**Last Updated**: 2026-02-18
