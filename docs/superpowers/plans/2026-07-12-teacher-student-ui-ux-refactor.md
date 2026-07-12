# Teacher & Student UI/UX Refactor Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Refactor the visual design, layout, spacing, typography, component quality, and mobile experience of the SIPPEL Teacher (`/guru`) and Student (`/siswa`) pages to production grade — without changing any business logic, data source, query, calculation, filter behavior, or feature set.

**Architecture:** Introduce a small, shared design-token + Blade-component layer (a "design system") that both role themes consume, then re-skin each in-scope page against it. Teacher keeps its **slate/blue** identity; Student keeps its **teal/emerald** identity; both share the same spacing scale, radius, shadow, typography, card anatomy, and mobile navigation pattern so they read as one system. All Livewire PHP components, computed properties, `wire:model` bindings, event names (`update-charts`), Alpine chart wrappers' data contracts, cache keys, and query logic are **frozen** — this is a Blade/CSS/markup-only refactor.

**Tech Stack:** Laravel 12, Livewire 3, Flux UI 2, Tailwind CSS **v4** (CSS-first config via `@theme` in `resources/css/app.css` — there is **no** `tailwind.config.js`), Alpine 3, ApexCharts 5, Vite 8. PHP 8.3, Pest 4.

---

## Global Constraints

Copied verbatim from the task spec and the repo's enforced rules. Every task implicitly includes this section.

- **Preserve existing functionality; improve/refactor visual and UX only.** No business-logic, data-structure, query, calculation, or role-logic changes unless *truly* required for the UI, and none are in this plan.
- **Scope is Teacher (`/guru`) and Student (`/siswa`) pages only.** Do **not** touch the School Operator / Admin Filament panel (`/app`, `app/Filament/**`, `app/Providers/Filament/**`). Admin is considered only as design context.
- **Teacher dashboard:** preserve the **3 existing charts** and the **filters connected to them** (`kelasId`, `mapelId`, `rentangWaktu`). Do not change chart logic, data source, query, calculation, or filter behavior. UI/layout/spacing/hierarchy/responsiveness/clarity only.
- **Student dashboard — attendance summary:** preserve the **existing number of summary items** (4: Hadir, Izin, Sakit, Alpa) and their filters (`tanggalMulai`, `tanggalSelesai`, `filterCepat`). Logic/data/query/calculation/filter behavior unchanged. Card/typography/spacing/icon/hierarchy/mobile only.
- **Student dashboard — attendance heatmap:** preserve the existing heatmap and its status mapping. Do not change logic, data source, query, calculation, status mapping (`hadir | absent | incomplete | no_activity | future | blank`), or behavior. Labels/tooltip/spacing/legend/responsiveness/readability only.
- **Do not** add/remove/replace features, or change data returned by any `#[Computed]` method.
- **Frozen identifiers (must remain byte-identical):** DOM ids `#chart-tren-kehadiran`, `#chart-keaktifan-topik`, `#chart-distribusi-keaktifan`; Alpine factories `chartTrenKehadiran`, `chartKeaktifanTopik`, `chartDistribusiKeaktifan`; Livewire event `update-charts` and its `[0]`-indexed payload shape `{ tren, topik, distribusi }`; all `wire:model`/`wire:model.live` names; all `wire:click="$set(...)"` targets; `wire:ignore` on chart containers; `@js($this->...())` initial-data calls; `#[Layout('layouts.teacher')]` / `layouts.student`.
- **Code style (enforced by `composer review`):** Pint (Laravel preset), `declare(strict_types=1)`, `final` classes, global imports for any PHP touched. Run `composer review` before declaring done (order: pint → rector → phpstan → pest).
- **Tailwind v4:** design tokens are declared in `@theme` inside `resources/css/app.css`. No JS config file exists; do not create one.
- **Language:** all user-facing copy stays Indonesian (`lang="id"`). Do not translate labels.
- **No new npm dependencies.** Use existing Tailwind/Flux/Alpine/ApexCharts only.
- **Do not commit during execution unless the user explicitly asks.** Each task ends with a checkpoint: inspect `git diff`, report changed files, and wait for review/approval.
- **Verification budget per task:** every task ends by running the named Pest tests (they assert data/behavior, catching any accidental logic regression) plus `npm run build` (asserts the Blade/Tailwind compiles). Browser tests stay disabled — do not enable them.

---

## Design decisions (locked before tasks)

These are the concrete visual rules every re-skin task references. No abstract theory — these are the exact values to apply.

**Shared tokens (added to `@theme`):**
- Spacing rhythm: cards use `p-4` (mobile `p-3`), section gaps `gap-4`, page vertical rhythm `space-y-5` (was inconsistent `space-y-4`).
- Radius: cards `rounded-2xl`, inner elements/badges `rounded-lg`, pills `rounded-full`.
- Border: `border border-<role>-100 dark:border-slate-800`. Shadow: resting `shadow-sm`, no hover elevation on data cards (avoid noise).
- Card header anatomy (standardized): a `<x-ui.card>` with optional `title`, `subtitle`, `icon`, and a header `actions` slot. Header padding `px-4 py-3`, `border-b`.
- Typography scale: page title `text-xl lg:text-2xl font-bold tracking-tight`; card title `text-sm font-semibold`; card subtitle `text-xs text-<muted>`; metric value `text-2xl font-bold tabular-nums`; eyebrow/label `text-[11px] font-semibold uppercase tracking-wide`.
- Role accent CSS variables: `--color-role-accent` etc. so a single `<x-ui.card>` renders slate for teacher, teal for student via a `variant` prop.

**Mobile strategy (both roles are mobile-primary):**
- Add a **fixed bottom navigation bar** (`lg:hidden`) mirroring the sidebar's items, with the primary action (Teacher: "Buat Aktivitas") as a center FAB. This is the single biggest UX win for mobile and replaces the current "hamburger only" pattern (which stays for secondary items).
- Fix the **undefined `.safe-area-inset` class** (referenced in both layouts, never defined) so notched devices render correctly.
- Add `pb-20 lg:pb-4` to main content so the bottom nav never overlaps content.

**Per-role identity (kept distinct, same skeleton):**
- Teacher = slate surfaces + blue-600 primary + professional/dense (more data per screen).
- Student = teal/emerald surfaces + teal-600 primary + friendlier (rounder, more breathing room, keeps gamified streak).

---

## File Structure

**New files (shared design layer):**
- `resources/views/components/ui/card.blade.php` — anonymous Blade component: standardized card shell (header/title/subtitle/icon/actions slot + body). Variant-aware (`teacher`/`student`).
- `resources/views/components/ui/metric-card.blade.php` — compact stat card (label, value, unit, icon, accent color).
- `resources/views/components/ui/section-heading.blade.php` — page-level title + date/subtitle + optional action slot.
- `resources/views/components/ui/segmented.blade.php` — the reusable "button group / segmented control" markup used by both dashboards' quick-range filters (renders slots; wire bindings passed by caller).
- `resources/views/components/ui/empty-state.blade.php` — icon + title + message + optional CTA slot.
- `resources/views/components/nav/bottom-bar.blade.php` — mobile fixed bottom nav (accepts `variant` + items array).
- `resources/views/livewire/teacher/partials/dashboard-charts.blade.php` — extracted chart markup + `@pushOnce` scripts from the 676-line dashboard (keeps ids/factories/events verbatim; pure move for readability).

**Modified files (re-skin only):**
- `resources/css/app.css` — add `@theme` tokens, `.safe-area-inset`, a few component utilities.
- `resources/views/layouts/teacher.blade.php`, `resources/views/layouts/student.blade.php` — adopt shared bottom nav, safe-area fix, spacing tokens.
- `resources/views/livewire/components/tahun-ajaran-selector.blade.php` — visual polish only.
- Teacher: `dashboard.blade.php`, `laporan.blade.php`, `teacher-profile.blade.php`, `aktivitas-pembelajaran/{list,create,edit,view}-aktivitas.blade.php`.
- Student: `dashboard.blade.php`, `riwayat-aktivitas.blade.php`, `profil.blade.php`.

**Frozen files (do NOT edit):** every `app/Livewire/**/*.php`, `app/Services/**`, `app/Models/**`, `routes/web.php`, anything under `app/Filament/**`, `app/Providers/**`.

---

## Task ordering rationale

Tasks 1–2 build the shared foundation (tokens + components). Task 3 fixes the mobile nav shell both dashboards live in. Tasks 4–6 re-skin the two dashboards' protected features (highest risk, most spec constraints) — each verified against its existing tests. Tasks 7–9 re-skin the remaining in-scope pages. Task 10 is the cross-cutting responsive/QA pass. Each task is independently shippable and reversible.

---

### Task 1: Design tokens + CSS foundation

**Files:**
- Modify: `resources/css/app.css:13-16` (`@theme` block) and append new utilities at end of file.
- Test (manual/build): `npm run build`.

**Interfaces:**
- Produces: CSS custom properties and utility classes consumed by every later task: `.safe-area-inset`, `.card-surface`, tokenized radii/shadows. Role accent handled via Tailwind classes in components (not new CSS), so no JS config needed.

- [x] **Step 1: Read the current theme block**

Read `resources/css/app.css` lines 13–16 to confirm the existing `@theme` (only `--font-sans` is defined).

- [x] **Step 2: Extend `@theme` with shared tokens**

Replace the `@theme { ... }` block with:

```css
@theme {
    --font-sans: 'Instrument Sans', ui-sans-serif, system-ui, sans-serif, 'Apple Color Emoji', 'Segoe UI Emoji',
        'Segoe UI Symbol', 'Noto Color Emoji';

    /* Card/system radii — used via rounded-* utilities already, declared for consistency */
    --radius-card: 1rem;      /* rounded-2xl */
    --radius-inner: 0.5rem;   /* rounded-lg */

    /* Elevation */
    --shadow-card: 0 1px 2px 0 rgb(0 0 0 / 0.04), 0 1px 3px 0 rgb(0 0 0 / 0.06);
}
```

- [x] **Step 3: Append component utilities + fix `.safe-area-inset`**

Add at the end of `resources/css/app.css`:

```css
/* Safe-area support for notched phones (class was referenced by layouts but never defined) */
@layer utilities {
    .safe-area-inset {
        padding-top: env(safe-area-inset-top, 0px);
        padding-left: env(safe-area-inset-left, 0px);
        padding-right: env(safe-area-inset-right, 0px);
    }
    .safe-area-bottom {
        padding-bottom: env(safe-area-inset-bottom, 0px);
    }
}

/* Standard data-card surface (kept as a class so all cards share one source of truth) */
@layer components {
    .card-surface {
        @apply bg-white dark:bg-slate-900/95 rounded-2xl border shadow-sm;
    }
}
```

- [x] **Step 4: Verify the build compiles**

Run: `npm run build`
Expected: build succeeds, no "unknown utility" / CSS parse errors.

- [x] **Step 5: Checkpoint review**

Run: `git diff -- resources/css/app.css`
Expected: only token/utility CSS changes are present. Do **not** commit unless the user explicitly asks.

---

### Task 2: Shared UI Blade components

**Files:**
- Create: `resources/views/components/ui/card.blade.php`
- Create: `resources/views/components/ui/metric-card.blade.php`
- Create: `resources/views/components/ui/section-heading.blade.php`
- Create: `resources/views/components/ui/segmented.blade.php`
- Create: `resources/views/components/ui/empty-state.blade.php`
- Test (build): `npm run build`

**Interfaces:**
- Produces (consumed by all re-skin tasks):
  - `<x-ui.card :variant="'teacher'|'student'" title="" subtitle="" icon="" >...body...</x-ui.card>` with optional `<x-slot:actions>`.
  - `<x-ui.metric-card label="" value="" unit="" icon="" accent="blue|emerald|violet|amber|teal|rose" />`
  - `<x-ui.section-heading title="" subtitle="" :variant="..." >` with optional `<x-slot:action>`.
  - `<x-ui.segmented>` wrapping caller-supplied `<button>`s (pure visual container).
  - `<x-ui.empty-state icon="" title="" message="" >` with optional CTA slot.

- [ ] **Step 1: Create `card.blade.php`**

```blade
@props([
    'variant' => 'teacher',
    'title' => null,
    'subtitle' => null,
    'icon' => null,
    'flush' => false,
])
@php
    $border = $variant === 'student'
        ? 'border-teal-100 dark:border-slate-800'
        : 'border-slate-200 dark:border-slate-800';
@endphp
<div {{ $attributes->except('flush')->class(['card-surface overflow-hidden', $border]) }}>
    @if($title || isset($actions))
        <div class="flex items-center justify-between gap-3 px-4 py-3 border-b {{ $border }}">
            <div class="flex items-center gap-2 min-w-0">
                @if($icon)
                    <flux:icon name="{{ $icon }}" class="w-4 h-4 shrink-0 {{ $variant === 'student' ? 'text-teal-500' : 'text-slate-400' }}" />
                @endif
                <div class="min-w-0">
                    <h2 class="text-sm font-semibold text-slate-900 dark:text-white truncate">{{ $title }}</h2>
                    @if($subtitle)
                        <p class="text-xs text-slate-400 dark:text-slate-500 mt-0.5">{{ $subtitle }}</p>
                    @endif
                </div>
            </div>
            @isset($actions)
                <div class="shrink-0">{{ $actions }}</div>
            @endisset
        </div>
    @endif
    <div class="{{ $flush ? '' : 'p-4' }}">
        {{ $slot }}
    </div>
</div>
```

> Note: the `p-4` body padding can be disabled by passing `flush` (e.g. for the heatmap and chart bodies that manage their own padding).

- [ ] **Step 2: Create `metric-card.blade.php`**

```blade
@props([
    'label' => '',
    'shortLabel' => null,
    'value' => '0',
    'unit' => null,
    'icon' => null,
    'accent' => 'blue',
])
@php
    $accents = [
        'blue' => ['ring' => 'text-blue-500/80 dark:text-blue-400/70', 'bg' => 'bg-blue-50 dark:bg-blue-900/30', 'ic' => 'text-blue-500'],
        'emerald' => ['ring' => 'text-emerald-500/80 dark:text-emerald-400/70', 'bg' => 'bg-emerald-50 dark:bg-emerald-900/30', 'ic' => 'text-emerald-500'],
        'violet' => ['ring' => 'text-violet-500/80 dark:text-violet-400/70', 'bg' => 'bg-violet-50 dark:bg-violet-900/30', 'ic' => 'text-violet-500'],
        'amber' => ['ring' => 'text-amber-500/80 dark:text-amber-400/70', 'bg' => 'bg-amber-50 dark:bg-amber-900/30', 'ic' => 'text-amber-500'],
        'teal' => ['ring' => 'text-teal-600/80 dark:text-teal-400/70', 'bg' => 'bg-teal-50 dark:bg-teal-900/30', 'ic' => 'text-teal-500'],
        'rose' => ['ring' => 'text-rose-500/80 dark:text-rose-400/70', 'bg' => 'bg-rose-50 dark:bg-rose-900/30', 'ic' => 'text-rose-500'],
    ];
    $a = $accents[$accent] ?? $accents['blue'];
@endphp
<div {{ $attributes->class(['card-surface border-slate-200 dark:border-slate-800 px-2.5 py-2 sm:p-4 relative overflow-hidden']) }}>
    @if($icon)
        <div class="hidden sm:flex absolute top-3 right-3 w-9 h-9 {{ $a['bg'] }} rounded-lg items-center justify-center">
            <flux:icon name="{{ $icon }}" class="w-4 h-4 {{ $a['ic'] }}" />
        </div>
    @endif
    <p class="text-[9px] sm:text-xs font-semibold uppercase tracking-wide {{ $a['ring'] }} leading-tight">
        <span class="sm:hidden">{{ $shortLabel ?? $label }}</span>
        <span class="hidden sm:inline">{{ $label }}</span>
    </p>
    <p class="text-lg sm:text-2xl font-bold text-slate-900 dark:text-white mt-0.5 sm:mt-1 tabular-nums">
        {{ $value }}@if($unit)<span class="text-xs sm:text-sm font-normal text-slate-400 dark:text-slate-500"> {{ $unit }}</span>@endif
    </p>
</div>
```

- [ ] **Step 3: Create `section-heading.blade.php`**

```blade
@props(['variant' => 'teacher', 'title' => '', 'subtitle' => null])
<div class="flex items-center justify-between gap-3">
    <div class="min-w-0">
        <h1 class="text-xl lg:text-2xl font-bold tracking-tight {{ $variant === 'student' ? 'text-teal-900 dark:text-white' : 'text-slate-900 dark:text-white' }}">{{ $title }}</h1>
        @if($subtitle)
            <p class="text-sm mt-0.5 {{ $variant === 'student' ? 'text-teal-600 dark:text-teal-300' : 'text-slate-500 dark:text-slate-300' }}">{{ $subtitle }}</p>
        @endif
    </div>
    @isset($action)
        <div class="shrink-0">{{ $action }}</div>
    @endisset
</div>
```

- [ ] **Step 4: Create `segmented.blade.php`**

```blade
@props([])
<div {{ $attributes->class(['inline-flex rounded-lg overflow-hidden border border-slate-200 dark:border-slate-700 shrink-0']) }}>
    {{ $slot }}
</div>
```

- [ ] **Step 5: Create `empty-state.blade.php`**

```blade
@props(['icon' => 'inbox', 'title' => '', 'message' => null, 'variant' => 'teacher'])
<div class="flex flex-col items-center justify-center py-12 text-center px-4">
    <flux:icon name="{{ $icon }}" class="w-10 h-10 text-slate-300 dark:text-slate-600 mb-3" />
    @if($title)<p class="text-sm font-medium text-slate-600 dark:text-slate-300">{{ $title }}</p>@endif
    @if($message)<p class="text-xs text-slate-400 dark:text-slate-500 mt-1 max-w-xs">{{ $message }}</p>@endif
    @isset($cta)<div class="mt-3">{{ $cta }}</div>@endisset
</div>
```

- [ ] **Step 6: Verify build compiles**

Run: `npm run build`
Expected: success, no Blade compile errors from the new components.

- [ ] **Step 7: Checkpoint review**

Run: `git diff -- resources/views/components/ui/`
Expected: only the five shared UI components are present. Do **not** commit unless the user explicitly asks.

---

### Task 3: Mobile bottom nav + layout shell polish (both roles)

**Files:**
- Create: `resources/views/components/nav/bottom-bar.blade.php`
- Modify: `resources/views/layouts/teacher.blade.php` (add bottom nav, safe-area, content padding)
- Modify: `resources/views/layouts/student.blade.php` (same)
- Test (build + manual): `npm run build`

**Interfaces:**
- Consumes: `.safe-area-inset` / `.safe-area-bottom` (Task 1).
- Produces: `<x-nav.bottom-bar :variant="..." :items="[...]" :fab="[...]" />` — a fixed mobile bottom nav both dashboards sit above.

**Important:** Do NOT alter the sidebar's `route(...)`, `wire:navigate`, `request()->routeIs(...)`, or the `@livewire('components.tahun-ajaran-selector', ...)` calls. Navigation targets are frozen; only markup/styling around them changes.

- [ ] **Step 1: Create `bottom-bar.blade.php`**

```blade
@props(['variant' => 'teacher', 'items' => [], 'fab' => null])
@php
    $activeCls = $variant === 'student' ? 'text-teal-600 dark:text-teal-400' : 'text-blue-600 dark:text-blue-400';
    $idleCls = 'text-slate-400 dark:text-slate-500';
    $fabBg = $variant === 'student' ? 'bg-teal-600 hover:bg-teal-700' : 'bg-blue-600 hover:bg-blue-700';
    $gridCls = $fab ? 'grid-cols-5' : match (count($items)) {
        1 => 'grid-cols-1',
        2 => 'grid-cols-2',
        4 => 'grid-cols-4',
        default => 'grid-cols-3',
    };
@endphp
<nav class="lg:hidden fixed bottom-0 inset-x-0 z-30 bg-white/95 dark:bg-slate-900/95 backdrop-blur border-t border-slate-200 dark:border-slate-800 safe-area-bottom"
     aria-label="Navigasi Bawah">
    <div class="grid {{ $gridCls }} h-16">
        @foreach($items as $i => $item)
            {{-- Insert FAB in the visual center --}}
            @if($fab && $i === intdiv(count($items), 2))
                <div class="flex items-center justify-center">
                    <a href="{{ $fab['href'] }}" wire:navigate
                       class="w-12 h-12 -mt-5 rounded-full {{ $fabBg }} text-white flex items-center justify-center shadow-lg"
                       aria-label="{{ $fab['label'] }}">
                        <flux:icon name="{{ $fab['icon'] }}" class="w-6 h-6" />
                    </a>
                </div>
            @endif
            <a href="{{ $item['href'] }}" wire:navigate
               class="flex flex-col items-center justify-center gap-0.5 text-[10px] font-medium {{ $item['active'] ? $activeCls : $idleCls }}">
                <flux:icon name="{{ $item['icon'] }}" variant="outline" class="w-5 h-5" />
                <span>{{ $item['label'] }}</span>
            </a>
        @endforeach
    </div>
</nav>
```

- [ ] **Step 2: Add safe-area to the teacher mobile header**

In `resources/views/layouts/teacher.blade.php:165`, the header already has `safe-area-inset` (now defined by Task 1) — no change needed. Confirm by reading the line.

- [ ] **Step 3: Add the teacher bottom nav + content bottom padding**

In `resources/views/layouts/teacher.blade.php`, change the main content wrapper `resources/views/layouts/teacher.blade.php:186` from:

```blade
            <div class="pt-16 lg:pt-4 pb-4 px-3 lg:px-6">
```
to:
```blade
            <div class="pt-16 lg:pt-4 pb-24 lg:pb-4 px-3 lg:px-6">
```

Then immediately **before** the closing `</div>` of `.flex.min-h-screen` (i.e. right after the `</main>` at `resources/views/layouts/teacher.blade.php:205`), insert:

```blade
        {{-- Mobile bottom navigation --}}
        <x-nav.bottom-bar
            variant="teacher"
            :fab="['href' => route('teacher.aktivitas.create'), 'icon' => 'plus', 'label' => 'Buat Aktivitas']"
            :items="[
                ['href' => route('teacher.dashboard'), 'icon' => 'squares-2x2', 'label' => 'Dashboard', 'active' => request()->routeIs('teacher.dashboard')],
                ['href' => route('teacher.aktivitas.list'), 'icon' => 'clipboard-document-list', 'label' => 'Aktivitas', 'active' => request()->routeIs('teacher.aktivitas.*')],
                ['href' => route('teacher.laporan'), 'icon' => 'chart-bar', 'label' => 'Laporan', 'active' => request()->routeIs('teacher.laporan')],
                ['href' => route('teacher.profil'), 'icon' => 'user-circle', 'label' => 'Profil', 'active' => request()->routeIs('teacher.profil')],
            ]"
        />
```

> The FAB renders between "Aktivitas" and "Laporan" (center of 4 items), matching the existing mobile header's "+" action so no function is lost.

- [ ] **Step 4: Add the student bottom nav + content bottom padding**

In `resources/views/layouts/student.blade.php:173`, change:
```blade
            <div class="pt-16 lg:pt-4 pb-4 px-3 lg:px-6">
```
to:
```blade
            <div class="pt-16 lg:pt-4 pb-24 lg:pb-4 px-3 lg:px-6">
```

After the `</main>` at `resources/views/layouts/student.blade.php:192`, insert:

```blade
        {{-- Mobile bottom navigation --}}
        <x-nav.bottom-bar
            variant="student"
            :items="[
                ['href' => route('student.dashboard'), 'icon' => 'squares-2x2', 'label' => 'Dashboard', 'active' => request()->routeIs('student.dashboard')],
                ['href' => route('student.riwayat'), 'icon' => 'calendar-days', 'label' => 'Riwayat', 'active' => request()->routeIs('student.riwayat')],
                ['href' => route('student.profil'), 'icon' => 'user', 'label' => 'Profil', 'active' => request()->routeIs('student.profil')],
            ]"
        />
```

(Student has no FAB — it is read-only, so `:fab` is omitted and the grid renders 3 equal columns.)

- [ ] **Step 5: Verify build + smoke test both routes**

Run: `npm run build`
Expected: success.
Then run the auth/redirect smoke tests to confirm layouts still render:
Run: `php vendor/bin/pest --filter="Login"`
Expected: PASS (login/redirect unaffected).

- [ ] **Step 6: Checkpoint review**

Run: `git diff -- resources/views/components/nav/bottom-bar.blade.php resources/views/layouts/teacher.blade.php resources/views/layouts/student.blade.php`
Expected: only mobile navigation, safe-area, and layout padding changes are present. Do **not** commit unless the user explicitly asks.

---

### Task 4: Teacher dashboard — extract charts partial (no visual change)

**Purpose:** The dashboard blade is 676 lines mixing markup + 400 lines of chart JS. Extract the chart JS/markup into a partial **verbatim** so Task 5's re-skin is safe and reviewable. This task changes zero pixels and zero behavior.

**Files:**
- Create: `resources/views/livewire/teacher/partials/dashboard-charts.blade.php`
- Modify: `resources/views/livewire/teacher/dashboard.blade.php`
- Test: `tests/Feature/TeacherDashboardStatsTest.php`, `tests/Feature/TeacherDashboardDateFilterTest.php`

**Interfaces:**
- Consumes: the three `#[Computed]` methods `$this->chartTrenKehadiran()`, `$this->chartKeaktifanPerTopik()`, `$this->chartDistribusiKeaktifan()` — called from the partial exactly as before.
- Produces: `dashboard-charts.blade.php` containing the three chart cards (lines 107–181 region) plus both `@pushOnce` blocks (lines 279–725), moved unchanged.

- [ ] **Step 1: Run the dashboard tests first (green baseline)**

Run: `php vendor/bin/pest tests/Feature/TeacherDashboardStatsTest.php tests/Feature/TeacherDashboardDateFilterTest.php`
Expected: PASS. Record this as the baseline.

- [ ] **Step 2: Move the chart cards + scripts into the partial**

Create `resources/views/livewire/teacher/partials/dashboard-charts.blade.php` and paste, **unchanged**, the current dashboard blade content from the `{{-- Chart 1: Tren Kehadiran Siswa --}}` block (line 107) through the end of Chart 3 grid (line 181), followed by both the `@pushOnce('vendor-scripts')` block (lines 280–282) and the `@pushOnce('scripts')` block (lines 287–725). Keep every `id`, `x-data`, `@js(...)`, `@update-charts.window`, and script byte-identical.

- [ ] **Step 3: Reference the partial from the dashboard**

In `resources/views/livewire/teacher/dashboard.blade.php`, replace the moved regions (chart cards lines 107–181, and the two `@pushOnce` blocks lines 279–725) with a single include where the chart cards were:

```blade
            @include('livewire.teacher.partials.dashboard-charts')
```

Leave the filter bar (lines 68–105) and the right sidebar column (lines 184–274) in place for now.

- [ ] **Step 4: Verify build + tests still green**

Run: `npm run build`
Expected: success.
Run: `php vendor/bin/pest tests/Feature/TeacherDashboardStatsTest.php tests/Feature/TeacherDashboardDateFilterTest.php`
Expected: PASS (identical to baseline — this was a pure move).

- [ ] **Step 5: Manual chart smoke check**

Serve locally (`composer dev`), open `/guru`, confirm all 3 charts render and the Semester/Bulan/Minggu buttons + Kelas/Mapel selects still redraw charts (the `update-charts` event still fires).

- [ ] **Step 6: Checkpoint review**

Run: `git diff -- resources/views/livewire/teacher/partials/dashboard-charts.blade.php resources/views/livewire/teacher/dashboard.blade.php`
Expected: chart markup/scripts are moved into the partial with no behavior changes. Do **not** commit unless the user explicitly asks.

---

### Task 5: Teacher dashboard — visual re-skin

**Files:**
- Modify: `resources/views/livewire/teacher/dashboard.blade.php`
- Modify: `resources/views/livewire/teacher/partials/dashboard-charts.blade.php` (card shells only — NOT the chart containers/scripts)
- Test: `tests/Feature/TeacherDashboardStatsTest.php`, `tests/Feature/TeacherDashboardDateFilterTest.php`, `tests/Feature/TeacherActivityKeaktifanTest.php`, `tests/Feature/TeacherDashboardCacheTest.php`

**Interfaces:**
- Consumes: `<x-ui.section-heading>`, `<x-ui.metric-card>`, `<x-ui.card>`, `<x-ui.segmented>`, `<x-ui.empty-state>` (Task 2).
- **Frozen:** `wire:model.live="kelasId"`, `wire:model.live="mapelId"`, `wire:click="$set('rentangWaktu', '...')"`, `$this->kelasList`, `$this->mapelList`, `$this->dashboardStats[...]`, `$this->keaktifanPerKelas`, chart DOM ids/scripts.

- [ ] **Step 1: Baseline tests green**

Run: `php vendor/bin/pest tests/Feature/TeacherDashboardStatsTest.php tests/Feature/TeacherDashboardDateFilterTest.php tests/Feature/TeacherActivityKeaktifanTest.php tests/Feature/TeacherDashboardCacheTest.php`
Expected: PASS.

- [ ] **Step 2: Replace the welcome header with `section-heading`**

Replace `resources/views/livewire/teacher/dashboard.blade.php` lines 3–14 with:

```blade
    {{-- ── Welcome Header ─────────────────────────────────────────────────── --}}
    <x-ui.section-heading
        variant="teacher"
        title="Halo, {{ explode(' ', auth()->user()->name ?? 'Guru')[0] }}!"
        subtitle="{{ now()->translatedFormat('l, d F Y') }}">
        <x-slot:action>
            <a href="{{ route('teacher.aktivitas.create') }}" wire:navigate
               class="hidden lg:inline-flex items-center gap-2 px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium rounded-lg transition-colors shadow-sm">
                <flux:icon name="plus" class="w-4 h-4" />
                Buat Aktivitas
            </a>
        </x-slot:action>
    </x-ui.section-heading>
```

- [ ] **Step 3: Replace the 4 metric cards with `metric-card`**

Replace lines 16–58 (the metric grid) with:

```blade
    {{-- ── 4 Metric Cards ──────────────────────────────────────────────────── --}}
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-2 sm:gap-3">
        <x-ui.metric-card accent="blue" icon="building-library"
            label="Mata Pelajaran Diampu" shortLabel="Mapel"
            :value="$this->dashboardStats['kelas_diampu']" unit="Mapel" />
        <x-ui.metric-card accent="emerald" icon="user-group"
            label="Total Siswa" shortLabel="Siswa"
            :value="$this->dashboardStats['total_siswa']" unit="Siswa" />
        <x-ui.metric-card accent="violet" icon="clipboard-document-check"
            label="Aktivitas Minggu Ini" shortLabel="Minggu Ini"
            :value="$this->dashboardStats['aktivitas_minggu_ini']" unit="Aktivitas" />
        <x-ui.metric-card accent="amber" icon="hand-raised"
            label="Rata-rata Kehadiran" shortLabel="Kehadiran"
            :value="$this->dashboardStats['rata_kehadiran']" unit="%" />
    </div>
```

- [ ] **Step 4: Re-skin the filter bar (bindings unchanged)**

Replace the filter bar `<div class="bg-white ...">` at lines 69–105 with a `<x-ui.card variant="teacher" flush>` wrapper; **keep the two `<flux:select wire:model.live=...>` blocks and the `wire:click="$set('rentangWaktu', ...)"` button loop byte-identical**, only wrapping the range buttons in `<x-ui.segmented>`:

```blade
            {{-- Filter Bar --}}
            <x-ui.card variant="teacher" flush class="px-4 py-3">
                <div class="flex flex-col sm:flex-row sm:items-center gap-3">
                    <div class="flex items-center gap-2 flex-1 min-w-0">
                        <div class="flex-1 min-w-0">
                            <flux:select wire:model.live="kelasId" size="sm" placeholder="Semua Kelas">
                                <flux:select.option value="">Semua Kelas</flux:select.option>
                                @foreach($this->kelasList as $kelas)
                                    <flux:select.option value="{{ $kelas['id'] }}">Kelas {{ $kelas['label'] }}</flux:select.option>
                                @endforeach
                            </flux:select>
                        </div>
                        <div class="flex-1 min-w-0">
                            <flux:select wire:model.live="mapelId" size="sm" placeholder="Semua Mapel">
                                <flux:select.option value="">Semua Mapel</flux:select.option>
                                @foreach($this->mapelList as $mapel)
                                    <flux:select.option value="{{ $mapel['id'] }}">{{ $mapel['label'] }}</flux:select.option>
                                @endforeach
                            </flux:select>
                        </div>
                    </div>
                    <x-ui.segmented class="self-start sm:self-auto w-full sm:w-auto">
                        @foreach(['semester' => 'Semester ini', 'bulan' => 'Bulan ini', 'minggu' => 'Minggu ini'] as $value => $label)
                            <button wire:click="$set('rentangWaktu', '{{ $value }}')" type="button"
                                    class="flex-1 sm:flex-none px-3 py-1.5 text-xs font-medium transition-colors border-r border-slate-200 dark:border-slate-700 last:border-r-0
                                        {{ $rentangWaktu === $value ? 'bg-blue-600 text-white' : 'bg-white dark:bg-slate-800 text-slate-600 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-700' }}">
                                {{ $label }}
                            </button>
                        @endforeach
                    </x-ui.segmented>
                </div>
            </x-ui.card>
```

> The `flex-1 sm:flex-none` addition makes the range buttons full-width on mobile (a real usability gain for thumb reach) without touching the `wire:click` logic.

- [ ] **Step 5: Re-skin chart card shells in the partial (containers frozen)**

In `resources/views/livewire/teacher/partials/dashboard-charts.blade.php`, wrap each of the 3 charts in `<x-ui.card variant="teacher" flush>` for consistent header/border/radius, but leave the inner `<div class="p-2 sm:p-4" wire:ignore x-data=... x-init="init()" @update-charts.window=...>` and every `id="chart-..."` element **exactly** as-is. For the empty states inside charts, you may swap the ad-hoc empty markup for `<x-ui.empty-state variant="teacher" ...>` **only** if it preserves the `x-show="empty"` / `x-show="!empty"` toggles — keep those Alpine directives on the same wrapping elements. If preserving the toggles cleanly is not trivial, leave the chart empty states unchanged (they are already acceptable).

- [ ] **Step 6: Re-skin the right sidebar cards**

Wrap the "Mata Pelajaran Diampu" card (lines 188–249) and "Panduan Indikator Keaktifan" card (lines 252–273) in `<x-ui.card variant="teacher" title="..." flush>`, moving their existing header text into the `title` prop and keeping the `@forelse($this->keaktifanPerKelas ...)` loop body and the panduan `@foreach` untouched.

- [ ] **Step 7: Verify build + full dashboard test suite**

Run: `npm run build`
Expected: success.
Run: `php vendor/bin/pest tests/Feature/TeacherDashboardStatsTest.php tests/Feature/TeacherDashboardDateFilterTest.php tests/Feature/TeacherActivityKeaktifanTest.php tests/Feature/TeacherDashboardCacheTest.php`
Expected: PASS (unchanged).

- [ ] **Step 8: Manual verification of the 3 charts + filters**

Serve locally, open `/guru`: confirm (a) all 3 charts render, (b) changing Kelas/Mapel selects redraws charts, (c) Semester/Bulan/Minggu buttons redraw charts, (d) dark mode still themes charts, (e) mobile (<480px) charts shrink per the ApexCharts `responsive` block.

- [ ] **Step 9: Checkpoint review**

Run: `git diff -- resources/views/livewire/teacher/dashboard.blade.php resources/views/livewire/teacher/partials/dashboard-charts.blade.php`
Expected: dashboard visual/card changes only; chart ids, Alpine factories, `wire:ignore`, and filter bindings unchanged. Do **not** commit unless the user explicitly asks.

---

### Task 6: Student dashboard — visual re-skin (summary + heatmap)

**Files:**
- Modify: `resources/views/livewire/student/dashboard.blade.php`
- Test: `tests/Feature/StudentDashboardCacheTest.php`, `tests/Feature/Models/SiswaAttendanceBreakdownTest.php`, `tests/Feature/Models/SiswaAttendanceStreakTest.php`

**Interfaces:**
- Consumes: `<x-ui.section-heading>`, `<x-ui.metric-card>`, `<x-ui.card>`, `<x-ui.segmented>` (Task 2).
- **Frozen:** the 4 summary items `$stats['hadir'|'izin'|'sakit'|'alpa']`; filters `wire:model.live="tanggalMulai"`, `wire:model.live="tanggalSelesai"`, `wire:click="$set('filterCepat', '...')"`; `$this->heatmapData` structure and its `$cellColors`/`$statusLabels` status keys (`hadir|absent|incomplete|no_activity|future|blank`); `$this->attendanceStreak`, `$this->mataPelajaranList`, `$this->motivationalMessage`.

- [ ] **Step 1: Baseline tests green**

Run: `php vendor/bin/pest tests/Feature/StudentDashboardCacheTest.php tests/Feature/Models/SiswaAttendanceBreakdownTest.php tests/Feature/Models/SiswaAttendanceStreakTest.php`
Expected: PASS.

- [ ] **Step 2: Replace greeting with `section-heading`**

Replace lines 3–8 with:

```blade
    <x-ui.section-heading
        variant="student"
        title="Hai, {{ explode(' ', auth()->user()->name ?? 'Siswa')[0] }}!"
        subtitle="{{ ($this->siswa && $this->contextKelas) ? 'Kelas '.$this->contextKelas->tingkat_kelas.'-'.$this->contextKelas->grup_kelas : null }}" />
```

- [ ] **Step 3: Re-skin the filter + summary card**

Keep the motivational message block (lines 11–18) as-is (it already fits the student identity). Wrap the filter+summary block (lines 19–95) in `<x-ui.card variant="student">`. **Preserve** the two `<input type="date" wire:model.live=...>` fields and the three `wire:click="$set('filterCepat', ...)"` buttons byte-identical; wrap the quick-filter pills' container unchanged (they are `rounded-full` pills — keep that student identity).

- [ ] **Step 4: Replace the 4 summary cards with `metric-card`**

Replace the summary grid (lines 65–94) with — preserving the exact 4 items and their `$stats[...]` keys:

```blade
            {{-- Summary Cards --}}
            @php $stats = $this->stats; @endphp
            <div class="grid grid-cols-2 sm:grid-cols-4 gap-3">
                <x-ui.metric-card accent="emerald" icon="check-circle" label="Hadir" :value="$stats['hadir']" />
                <x-ui.metric-card accent="blue" icon="clock" label="Izin" :value="$stats['izin']" />
                <x-ui.metric-card accent="amber" icon="exclamation-circle" label="Sakit" :value="$stats['sakit']" />
                <x-ui.metric-card accent="rose" icon="x-circle" label="Alpa" :value="$stats['alpa']" />
            </div>
```

> Count stays 4; keys stay `hadir/izin/sakit/alpa`; the connected date/quick filters above are untouched. This satisfies the "preserve number of attendance summary items + filters" constraint.

- [ ] **Step 5: Re-skin the heatmap card (behavior frozen)**

Wrap the heatmap (lines 103–196) in `<x-ui.card variant="student" title="Peta Kehadiran" flush>`. **Do not touch** the `@php $cellColors = [...] $statusLabels = [...] @endphp` arrays, the `$heatmap['weeks']` loops, the `grid grid-flow-col grid-rows-5` structure, or the `overflow-x-auto` wrapper. Permitted improvements only:
  - Improve the tooltip: change the day cell `title="..."` to also include the day name when present (readability), keeping the same data source: `title="{{ $day['formatted_date'] ? $day['day_name'].', '.$day['formatted_date'].' — '.($statusLabels[$day['status']] ?? '') : '' }}"`.
  - Improve legend spacing/wrap on mobile: keep all 5 legend items and their colors; only adjust `gap`/`text` classes.
  - Add `scroll-smooth` and a subtle right-edge fade hint for the horizontal scroll on mobile (visual only).

- [ ] **Step 6: Re-skin the streak + panduan + subject-list cards**

Wrap the Streak widget (lines 212–282), Panduan widget (lines 285–306), and "Mata Pelajaran Saya" card (lines 315–364) in `<x-ui.card variant="student" title="..." flush>`. Keep the streak `$milestones` PHP array, `$this->attendanceStreak`, progress-bar Alpine `x-data`, and the `@foreach($this->mataPelajaranList ...)` body untouched. Keep the emojis (they are part of the student identity and the spec allows emoji where already present).

- [ ] **Step 7: Replace the two ad-hoc warning/empty blocks**

Optionally swap the "Belum ada data Mata Pelajaran" (lines 358–363) and the no-student warning (lines 368–374) for `<x-ui.empty-state variant="student" ...>` — preserving the exact copy and the `@if($this->siswa)`/`@else` control flow.

- [ ] **Step 8: Verify build + tests**

Run: `npm run build`
Expected: success.
Run: `php vendor/bin/pest tests/Feature/StudentDashboardCacheTest.php tests/Feature/Models/SiswaAttendanceBreakdownTest.php tests/Feature/Models/SiswaAttendanceStreakTest.php`
Expected: PASS.

- [ ] **Step 9: Manual verification**

Serve locally, open `/siswa`: confirm (a) 4 summary cards show correct counts, (b) date range + Semester/Bulan/Minggu filters still update the summary, (c) heatmap renders all week columns with correct colors + month labels + working tooltips, (d) horizontal scroll works on mobile width, (e) streak/subject cards render.

- [ ] **Step 10: Checkpoint review**

Run: `git diff -- resources/views/livewire/student/dashboard.blade.php`
Expected: dashboard visual/card changes only; 4 summary keys, date filters, quick filters, and heatmap status mapping unchanged. Do **not** commit unless the user explicitly asks.

---

### Task 7: Teacher — Aktivitas pages re-skin (list/create/edit/view)

**Files:**
- Modify: `resources/views/livewire/teacher/aktivitas-pembelajaran/list-aktivitas.blade.php`
- Modify: `resources/views/livewire/teacher/aktivitas-pembelajaran/create-aktivitas.blade.php`
- Modify: `resources/views/livewire/teacher/aktivitas-pembelajaran/edit-aktivitas.blade.php`
- Modify: `resources/views/livewire/teacher/aktivitas-pembelajaran/view-aktivitas.blade.php`
- Test: `tests/Feature/TeacherActivityKeaktifanTest.php` + any aktivitas Livewire tests found by `php vendor/bin/pest --filter=Aktivitas`

**Interfaces:**
- Consumes shared UI components (Task 2).
- **Frozen:** every `wire:model*`, `wire:click`, `wire:submit`, `wire:navigate`, validation error display (`@error`), route calls, and Livewire method names in these views. This is markup/skin only. The three Rector-protected components (`EditAktivitas.php`) are PHP — not edited here at all.

- [ ] **Step 1: Baseline**

Run: `php vendor/bin/pest --filter=Aktivitas`
Expected: PASS (record baseline; if zero tests match, note it and rely on manual + build).

- [ ] **Step 2: Read each of the 4 blades fully**

Read all four files end-to-end to inventory their card wrappers, page headers, form fields, table/list rows, and empty states before editing.

- [ ] **Step 3: Apply the shared system to `list-aktivitas.blade.php`**

- Replace the page header with `<x-ui.section-heading variant="teacher" title="..." subtitle="...">` (move existing "create" button into `<x-slot:action>`).
- Wrap list/table container(s) in `<x-ui.card variant="teacher" flush>`.
- Replace any bespoke empty state with `<x-ui.empty-state variant="teacher" ...>` preserving copy + any CTA `wire:navigate` link.
- Ensure list rows are mobile-friendly: on `<sm`, stack row content vertically (`flex-col sm:flex-row`) and make each row a tappable card. Do not change any filter/search `wire:model`.

- [ ] **Step 4: Apply the shared system to `create-aktivitas.blade.php` and `edit-aktivitas.blade.php`**

- Wrap the form in `<x-ui.card variant="teacher" title="...">`.
- Group fields into logical sections with consistent spacing (`space-y-4`); keep every `<flux:input>/<flux:select>/<flux:textarea wire:model...>` and `@error` block byte-identical.
- Make the submit/cancel action bar sticky-to-bottom on mobile (`sticky bottom-16 lg:static`) so long forms are usable one-handed. Keep `wire:submit`, `wire:loading`, and button `wire:click` targets unchanged.

- [ ] **Step 5: Apply the shared system to `view-aktivitas.blade.php`**

- Wrap detail sections in `<x-ui.card variant="teacher" title="...">`.
- Present per-student attendance/keaktifan rows with clear typographic hierarchy and status badges consistent with the dashboard's color mapping (Sangat Aktif=emerald, Aktif=blue, Cukup=amber, Pasif=rose). Keep all data bindings unchanged.

- [ ] **Step 6: Verify build + tests + manual CRUD smoke**

Run: `npm run build` → success.
Run: `php vendor/bin/pest --filter=Aktivitas` → PASS (matches baseline).
Manual: create → edit → view → list an aktivitas locally; confirm every field, validation message, and save works.

- [ ] **Step 7: Checkpoint review**

Run: `git diff -- resources/views/livewire/teacher/aktivitas-pembelajaran/`
Expected: visual/layout changes only; form bindings, validation, routes, and actions unchanged. Do **not** commit unless the user explicitly asks.

---

### Task 8: Teacher — Laporan + Profile re-skin

**Files:**
- Modify: `resources/views/livewire/teacher/laporan.blade.php` (506 lines)
- Modify: `resources/views/livewire/teacher/teacher-profile.blade.php` (107 lines)
- Test: `php vendor/bin/pest --filter="Laporan"` and `--filter="Profile"`; report export gate stays intact.

**Interfaces:**
- **Frozen:** all `wire:model*`/`wire:click` in Laporan filters, the report **export** form/button that posts to `route('reports.class.export')`, and the `export-class-report` gated action. `TeacherProfile.php` has Rector-protected `protected` validation methods — do not touch the PHP.

- [ ] **Step 1: Baseline**

Run: `php vendor/bin/pest --filter=Laporan; php vendor/bin/pest --filter=Profile`
Expected: PASS (or note absence; rely on manual + build).

- [ ] **Step 2: Read both blades fully.**

- [ ] **Step 3: Re-skin `laporan.blade.php`**

- Page header → `<x-ui.section-heading variant="teacher">`.
- Filter controls → wrap in `<x-ui.card variant="teacher">` and, where multiple selects exist, align them in a responsive `grid sm:grid-cols-2 lg:grid-cols-3 gap-3`. Keep every `wire:model` verbatim.
- The report table → wrap in `<x-ui.card flush>`; on mobile give the table `overflow-x-auto` and keep column headers; do not drop columns (data parity).
- The **export button/form** stays functionally identical (same `method="POST"`, `@csrf`, `action`), only restyled to match the primary button style.

- [ ] **Step 4: Re-skin `teacher-profile.blade.php`**

- Wrap profile info + edit form in `<x-ui.card variant="teacher" title="Profil Saya">`.
- Keep all `wire:model`, `@error`, and save `wire:submit`/`wire:click` unchanged.
- Improve avatar/name header block and field spacing (`space-y-4`).

- [ ] **Step 5: Verify build + tests + manual**

Run: `npm run build` → success.
Run: `php vendor/bin/pest --filter=Laporan; php vendor/bin/pest --filter=Profile` → PASS.
Manual: apply a Laporan filter, trigger an export (confirm file downloads), edit + save profile.

- [ ] **Step 6: Checkpoint review**

Run: `git diff -- resources/views/livewire/teacher/laporan.blade.php resources/views/livewire/teacher/teacher-profile.blade.php`
Expected: visual/layout changes only; report filters/export and profile bindings unchanged. Do **not** commit unless the user explicitly asks.

---

### Task 9: Student — Riwayat + Profil re-skin

**Files:**
- Modify: `resources/views/livewire/student/riwayat-aktivitas.blade.php` (228 lines)
- Modify: `resources/views/livewire/student/profil.blade.php` (125 lines)
- Test: `php vendor/bin/pest --filter="Riwayat"` and `--filter="Profil"` (note: student area is read-only).

**Interfaces:**
- **Frozen:** all `wire:model*` filters on Riwayat, pagination, and the `Profil.php` Rector-protected `protected` validation methods (PHP untouched).

- [ ] **Step 1: Baseline**

Run: `php vendor/bin/pest --filter=Riwayat; php vendor/bin/pest --filter=Profil`
Expected: PASS (or note absence).

- [ ] **Step 2: Read both blades fully.**

- [ ] **Step 3: Re-skin `riwayat-aktivitas.blade.php`**

- Header → `<x-ui.section-heading variant="student">`.
- Filters → `<x-ui.card variant="student">`, keep `wire:model` verbatim.
- Activity history list → present as a vertical **timeline of cards** (each entry a `<x-ui.card variant="student">`-style row) with date, subject, attendance status badge (reuse dashboard color mapping), and keaktifan. Mobile-first single column; keep pagination `wire:` controls unchanged.
- Empty state → `<x-ui.empty-state variant="student">`.

- [ ] **Step 4: Re-skin `profil.blade.php`**

- Wrap in `<x-ui.card variant="student" title="Profil Saya">`; keep all bindings and `@error` blocks.
- Improve identity header (avatar initial + name + NIS + class) and field spacing.

- [ ] **Step 5: Verify build + tests + manual**

Run: `npm run build` → success.
Run: `php vendor/bin/pest --filter=Riwayat; php vendor/bin/pest --filter=Profil` → PASS.
Manual: open `/siswa/riwayat`, apply filters + paginate; open `/siswa/profil`, save if editable.

- [ ] **Step 6: Checkpoint review**

Run: `git diff -- resources/views/livewire/student/riwayat-aktivitas.blade.php resources/views/livewire/student/profil.blade.php`
Expected: visual/layout changes only; filters, pagination, profile bindings, and read-only workflows unchanged. Do **not** commit unless the user explicitly asks.

---

### Task 10: Cross-cutting responsive + consistency QA pass

**Files:**
- Modify (as needed for fixes surfaced): any in-scope blade from Tasks 3–9.
- Modify: `resources/views/livewire/components/tahun-ajaran-selector.blade.php` (final polish).
- Test: full suite via `composer review`.

**Interfaces:** none new. This task hardens what exists.

- [ ] **Step 1: Polish the tahun-ajaran selector**

In `tahun-ajaran-selector.blade.php`, keep `wire:model.live="selectedTahunAjaranId"` and the `@tahun-ajaran-changed.window="window.location.reload()"` verbatim; only refine the select's sizing/contrast so it matches the sidebar in both slate and teal variants.

- [ ] **Step 2: Responsive audit at 3 breakpoints**

For each in-scope route (`/guru`, `/guru/aktivitas`, `/guru/aktivitas/create`, a `/guru/aktivitas/{id}`, `/guru/aktivitas/{id}/edit`, `/guru/laporan`, `/guru/profil`, `/siswa`, `/siswa/riwayat`, `/siswa/profil`), verify at **375px**, **768px**, **1280px**:
  - no horizontal page overflow (the layouts already set `overflow-x-hidden`; confirm nothing breaks it),
  - bottom nav does not cover the last element (content has `pb-24`),
  - tap targets ≥ 40px, text ≥ 12px,
  - dark mode has sufficient contrast on every re-skinned card.
Fix any issue inline in the relevant blade.

- [ ] **Step 3: Consistency sweep**

Confirm across all in-scope pages: card radius (`rounded-2xl`), header padding (`px-4 py-3`), page rhythm (`space-y-5`), metric value style (`tabular-nums`), badge color mapping, and that teacher=slate/blue vs student=teal/emerald identities are consistent. Fix stragglers.

- [ ] **Step 4: Full QA gate**

Run: `composer review`
Expected: pint (clean), rector (no changes needed to our blades; if it edits any of the 3 protected PHP files it must be reverted — but we edited none), phpstan level 5 (PASS), pest (**all** tests PASS).

- [ ] **Step 5: Final production build**

Run: `npm run build`
Expected: success; assets emitted.

- [ ] **Step 6: Final checkpoint review**

Run: `git status --short` and `git diff --stat`
Expected: only in-scope Teacher/Student UI files, shared UI components, layout blades, and `resources/css/app.css` changed. Do **not** commit unless the user explicitly asks.

---

## Rollback & safety notes

- Every task is a self-contained checkpoint; review `git diff` after each task before continuing. Commit only if the user explicitly asks.
- Tasks 1–3 (foundation) are additive: shared components + tokens + bottom nav. They do not remove any existing markup path, so even if a later re-skin is reverted from the working tree, the app still runs.
- No PHP, route, migration, query, or Filament/admin file is modified anywhere in this plan. The `#[Computed]` return shapes, cache keys, and Livewire event contracts are untouched, which is why the existing dashboard/attendance tests are sufficient regression guards.
- If ApexCharts fails to render after Task 5, the cause is almost certainly a changed DOM `id`, a lost `wire:ignore`, or a broken `@js($this->...())` call — diff the partial against the Task 4 verbatim version.

---

## Self-Review

**1. Spec coverage:**
- Teacher pages refactor → Tasks 4,5,7,8 (+3,10). ✅
- Student pages refactor → Tasks 6,9 (+3,10). ✅
- Operator/Admin out of scope, kept as context → enforced by Global Constraints + Frozen files list; no admin file touched. ✅
- Teacher dashboard: 3 charts + their filters preserved, logic frozen → Task 5 Step 4–5 + frozen identifiers. ✅
- Student summary: item count (4) + filters preserved → Task 6 Step 3–4. ✅
- Student heatmap: status mapping/behavior preserved, UI-only improvements → Task 6 Step 5. ✅
- Mobile-primary UX → Task 3 (bottom nav, safe-area) + responsive audit Task 10. ✅
- Same design system across two identities → Task 2 shared components with `variant`. ✅
- No backend/DB/logic change → Frozen files list + tests as guards. ✅

**2. Placeholder scan:** No "TBD/TODO/handle edge cases/etc." — every code step contains concrete markup and exact commands. ✅ (Two steps intentionally allow "leave unchanged if not trivial" for chart empty states and optional empty-state swaps — these are explicit safe fallbacks, not placeholders.)

**3. Type/identifier consistency:** Component names (`x-ui.card`, `x-ui.metric-card`, `x-ui.section-heading`, `x-ui.segmented`, `x-ui.empty-state`, `x-nav.bottom-bar`) and props (`variant`, `title`, `subtitle`, `icon`, `accent`, `label`, `shortLabel`, `value`, `unit`, `items`, `fab`) are used identically across Tasks 2–10. Frozen Livewire/DOM identifiers listed once in Global Constraints and referenced consistently. ✅
