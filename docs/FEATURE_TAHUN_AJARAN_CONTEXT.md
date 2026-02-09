# Feature: Tahun Ajaran Context Switcher

## 📋 Overview

Implementasi **global context switcher** untuk tahun ajaran yang memungkinkan guru bekerja dengan data dari tahun ajaran manapun tanpa harus mengubah status tahun ajaran aktif di sistem.

## 🎯 Problem Statement

Saat ini sistem hanya menampilkan data berdasarkan tahun ajaran yang **status = true** (aktif). Ini menyebabkan:

- ❌ Guru tidak bisa review data tahun lalu
- ❌ Tidak bisa mempersiapkan data untuk semester depan
- ❌ Tidak fleksibel untuk bekerja dengan multiple tahun ajaran
- ❌ Admin harus sering mengubah status tahun ajaran aktif

## 💡 Solution

Buat **session-based context switcher** di header yang memungkinkan user memilih tahun ajaran untuk "context" mereka, mirip dengan:
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

### 3. Livewire Component untuk Selector

**Create component:**

```bash
php artisan make:livewire Teacher/Components/TahunAjaranSelector
```

**Component code:**

```php
// app/Livewire/Teacher/Components/TahunAjaranSelector.php

namespace App\Livewire\Teacher\Components;

use App\Models\TahunAjaran;
use Livewire\Attributes\Computed;
use Livewire\Component;

class TahunAjaranSelector extends Component
{
    public ?int $selectedTahunAjaranId = null;

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
        return view('livewire.teacher.components.tahun-ajaran-selector');
    }
}
```

**View:**

```blade
{{-- resources/views/livewire/teacher/components/tahun-ajaran-selector.blade.php --}}

<div class="flex items-center gap-2">
    <flux:icon name="calendar" class="w-4 h-4 text-slate-500 dark:text-slate-400" />
    <flux:select 
        wire:model.live="selectedTahunAjaranId"
        class="text-sm border-slate-300 dark:border-slate-600 dark:bg-slate-800 rounded-lg focus:border-blue-500 focus:ring-0"
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

### 4. Update Layout

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
            @livewire('teacher.components.tahun-ajaran-selector')
            
            {{-- User menu, notifications, etc --}}
            <div>...</div>
        </div>
    </div>
</header>
```

### 5. Update Queries

**Replace semua `TahunAjaran::getActive()` dengan `TahunAjaran::getContext()`**

Contoh file yang perlu diupdate:

#### `CreateAktivitas.php`

```php
// BEFORE:
#[Computed]
public function tingkatKelasList()
{
    $activeTahunAjaran = TahunAjaran::getActive();
    // ...
}

// AFTER:
#[Computed]
public function tingkatKelasList()
{
    $contextTahunAjaran = TahunAjaran::getContext();
    // ...
}
```

#### `Dashboard.php`

```php
// BEFORE:
public function getRecentActivities()
{
    return AktivitasPembelajaran::whereHas('kelas', function($q) {
            $q->where('tahun_ajaran_id', TahunAjaran::getActive()?->id);
        })
        ->get();
}

// AFTER:
public function getRecentActivities()
{
    return AktivitasPembelajaran::whereHas('kelas', function($q) {
            $q->where('tahun_ajaran_id', TahunAjaran::getContext()?->id);
        })
        ->get();
}
```

## 📝 Implementation Checklist

### Phase 1: Core Functionality
- [ ] Add `getContext()` method to `TahunAjaran` model
- [ ] Add `setContext()` method to `TahunAjaran` model
- [ ] Create `TahunAjaranSelector` Livewire component
- [ ] Create view for selector component
- [ ] Add unit tests for context methods

### Phase 2: UI Integration
- [ ] Update teacher layout to include selector in header
- [ ] Test selector functionality
- [ ] Ensure proper styling (mobile responsive)
- [ ] Add loading states

### Phase 3: Query Updates
Update files di teacher panel:
- [ ] `CreateAktivitas.php`
- [ ] `EditAktivitas.php`
- [ ] `ListAktivitas.php`
- [ ] `Dashboard.php`
- [ ] `Laporan.php`

### Phase 4: Student Panel (Optional)
- [ ] Consider if students need context switcher
- [ ] Update student queries if needed

### Phase 5: Testing & Documentation
- [ ] Manual testing untuk semua fitur teacher
- [ ] Test edge cases (empty context, deleted tahun ajaran)
- [ ] Update technical documentation
- [ ] Update user guide

## 🎨 UI/UX Considerations

### Desktop
```
┌─────────────────────────────────────────────────────────┐
│  SIPPEL Guru    [🗓 2027/2028 Ganjil ⭐ ▼]    🔔  👤   │
└─────────────────────────────────────────────────────────┘
```

### Mobile
```
┌──────────────────────────────┐
│  ☰  SIPPEL    🔔  👤        │
│  [🗓 2027/2028 Ganjil ⭐ ▼] │
└──────────────────────────────┘
```

### Dropdown Options
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

### For Admins
1. ✅ Tidak perlu sering mengubah status tahun ajaran
2. ✅ Sistem lebih stabil karena tahun aktif jarang berubah
3. ✅ Lebih mudah untuk testing dan data migration

### For System
1. ✅ Better separation of concerns
2. ✅ More flexible data access patterns
3. ✅ Supports multi-tenancy like behavior per user

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
- Pertimbangkan untuk add permission check di `setContext()`

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
- Update all components
- Deprecate direct usage of `getActive()` in teacher context
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

### Test Cases
1. ✅ User selects different tahun ajaran → data updates
2. ✅ User logout → context reset to active
3. ✅ Tahun ajaran deleted → fallback to active
4. ✅ No active tahun ajaran → show error message
5. ✅ Create aktivitas → uses current context
6. ✅ Switch context → page refreshes correctly

### Edge Cases
- User has no mata pelajaran in selected context
- Selected tahun ajaran becomes deleted
- Multiple tabs with different contexts
- Session expired

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

**Estimated Effort**: 4-6 hours

**Dependencies**: None

**Last Updated**: 2026-01-31
