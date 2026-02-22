# SIPPEL Code Review Findings

> Reviewed: February 20, 2026
> Scope: Full application codebase (~63 PHP files, ~6,700 lines)

---

## P0 — Critical (Fix Immediately)

### 1. `KenaikanKelasPage` uses unvalidated form data FIXED

**File:** `app/Filament/Pages/KenaikanKelasPage.php` — line ~310
**Type:** Bug

The `create()` method reads `studentDecisions` from raw `$this->data` instead of the validated `$data` returned by `$this->form->getState()`:

```php
// Current (WRONG) — bypasses Filament's form validation
$studentDecisions = $this->data['studentDecisions'] ?? [];

// Should be:
$studentDecisions = $data['studentDecisions'] ?? [];
```

**Impact:** Malicious or malformed student decision data could bypass validation and corrupt the grade promotion process.

---

### 2. Default admin password in config fallback FIXED

**File:** `config/app.php` — lines 32-36
**Type:** Security

```php
'default_user' => [
    'email' => env('DEFAULT_USER_EMAIL', 'admin@example.com'),
    'password' => env('DEFAULT_USER_PASSWORD', 'password'),  // ← trivially guessable
],
```

**Fix:** Remove the hardcoded fallback password, or make the seeder refuse to run in production:

```php
'password' => env('DEFAULT_USER_PASSWORD'),  // no fallback — forces explicit config
```

---

### 3. `getAttendancePercentage` mutates original query builder

**File:** `app/Models/Siswa.php` — lines ~119-125
**Type:** Bug

```php
$total = (clone $query)->count();
// BUG: $query is mutated here — the whereRaw bleeds into the original builder
$hadir = $query->whereRaw('LOWER(detail_aktivitas.kehadiran) = ?', ['hadir'])->count();
```

**Fix:** Clone for the hadir count as well:

```php
$total = (clone $query)->count();
$hadir = (clone $query)->whereRaw('LOWER(detail_aktivitas.kehadiran) = ?', ['hadir'])->count();
```

---

### 21. `getKelasForTahunAjaran` fallback silently triggers N+1 per student FIXED

**File:** `app/Models/Siswa.php` — line 84
**Type:** Bug / Performance

```php
return $history?->kelas ?? ($this->kelas?->tahun_ajaran_id === $tahunAjaranId ? $this->kelas : null);
```

When no history record is found, the fallback accesses `$this->kelas`. If `kelas` is not already eager-loaded, this fires a lazy-load query per student. In batch operations (e.g., `CalculateReports` processing 200 students), this silently adds up to 200 extra queries on top of the history queries. Worse, even if `kelas` *is* loaded, the fallback returns it based only on its `tahun_ajaran_id` — a student's current `kelas_id` may belong to a different academic year than intended.

**Fix:** Ensure callers always eager-load `kelasHistory.kelas` and `kelas`, and optionally guard the method:

```php
public function getKelasForTahunAjaran(int $tahunAjaranId): ?Kelas
{
    // Use already-loaded history to avoid N+1
    $history = $this->kelasHistory
        ->where('tahun_ajaran_id', $tahunAjaranId)
        ->first();

    return $history?->kelas;  // no unreliable fallback
}
```

---

## P1 — High Priority

### 4. Trust all proxies in production — N/A FOR HEROKU

**File:** `bootstrap/app.php` — lines 26-31
**Type:** Security (Platform-Dependent)

```php
$middleware->trustProxies(at: '*', headers: ...);
```

`'*'` is the **correct configuration for Heroku**. Heroku's router IPs are dynamic and not published as a static range, making specific IP whitelisting impossible. More importantly, Heroku dynos have no public IP address — all inbound traffic must pass through Heroku's edge routers, so an external attacker cannot reach your dyno directly to inject spoofed headers.

**Only becomes a risk if** the app is ever migrated to a platform where the server is directly internet-reachable (bare VPS, self-hosted nginx). In that case, restrict to known proxy IPs or use `Request::HEADER_X_FORWARDED_AWS_ELB` with a specific subnet.

**No action needed for the current Heroku deployment.**

---

### 5. N+1 queries in `CalculateReports` command FIXED

**File:** `app/Console/Commands/CalculateReports.php` — line ~115
**Type:** Performance

For each student-subject pair, `getKelasForTahunAjaran()` fires a separate query to `siswa_kelas_history`. With 200 students × 10 subjects = **2,000+ extra queries**.

**Fix:** Eager-load `kelasHistory` and `kelas` when fetching the student list:

```php
$siswaList = $siswaQuery->with(['kelas', 'kelasHistory'])->get();
```

---

### 6. GET-based logout in `/app/api/check-role` FIXED

**File:** `routes/web.php` — lines 38-40
**Type:** Security

```php
// Teachers and students are not authorized - log them out
Auth::logout();
request()->session()->invalidate();
request()->session()->regenerateToken();
```

A GET endpoint that logs out users is vulnerable to CSRF-like attacks. An attacker could embed `<img src="/app/api/check-role">` to force-logout any teacher/student who views the page.

**Fix:** Either remove the logout behavior (just return 403), or change the endpoint to POST with CSRF protection.

---

### 7. Duplicated filter-building logic in `Siswa` model FIXED

**File:** `app/Models/Siswa.php`
**Type:** Code Quality

Three methods — `getAttendancePercentage()`, `getAverageGrade()`, `getAverageParticipation()` — each contain ~30 identical lines of query-building logic (join, filter by mataPelajaranId, startDate, endDate, tahunAjaranId).

**Fix:** Extract a shared private method:

```php
private function buildFilteredDetailQuery(
    ?int $mataPelajaranId,
    ?string $startDate,
    ?string $endDate,
    ?int $tahunAjaranId
): \Illuminate\Database\Eloquent\Builder {
    $query = $this->detailAktivitas()
        ->join('aktivitas_pembelajaran', ...)
        ->whereNull('aktivitas_pembelajaran.deleted_at');

    // Apply shared filters...
    return $query;
}
```

Then each calculation method becomes ~10 lines instead of ~50.

---

## P2 — Medium Priority

### 8. Student dashboard wastes eager-loaded data FIXED

**File:** `app/Livewire/Student/Dashboard.php` — lines ~198-200
**Type:** Performance

The `render()` method eager-loads `detailAktivitas.aktivitasPembelajaran.mataPelajaran`, but then `getAttendancePercentage()`, `getAverageGrade()`, and `getAverageParticipation()` all take `$tahunAjaranId` parameter, which forces them into the query-based code path (not the collection-based path). The eager-loaded data is wasted.

**Fix:** Either:
- Don't pass `$tahunAjaranId` and filter the eager-loaded collection instead, or
- Remove the eager-load and let the methods run their own queries.

---

### 9. `kehadiran` string comparisons should be an Enum FIXED

**Files:** `Siswa.php`, `CalculateReports.php`, `CreateAktivitas.php`, `ClassReportExport.php`
**Type:** Code Quality

Attendance status is compared via `mb_strtolower()` string matching in at least 6 different locations. A typo (e.g., `'Hadri'`) would silently produce wrong results.

**Fix:** Create a backed enum:

```php
// app/Enums/KehadiranStatus.php
enum KehadiranStatus: string {
    case Hadir = 'hadir';
    case Izin = 'izin';
    case Sakit = 'sakit';
    case Alpa = 'alpa';
}
```

Then cast it in `DetailAktivitas`:

```php
protected $casts = [
    'kehadiran' => KehadiranStatus::class,
];
```

---

### 10. `SiswaObserver::forceDeleted` only soft-deletes the User FIXED

**File:** `app/Observers/SiswaObserver.php` — line 18
**Type:** Bug

```php
public function forceDeleted(Siswa $siswa): void
{
    if ($siswa->user) {
        $siswa->user->delete();  // ← soft-delete, not force-delete
    }
}
```

When a `Siswa` is **force-deleted**, the associated `User` is only **soft-deleted** (since `User` uses `SoftDeletes`). This creates orphaned soft-deleted user records.

**Fix:** Use `$siswa->user->forceDelete()` if the intent is to cascade.

---

### 11. Exception swallowed without logging FIXED

**File:** `app/Livewire/Teacher/AktivitasPembelajaran/CreateAktivitas.php` — line ~287
**Type:** Best Practice

```php
} catch (Exception) {
    session()->flash('error', 'Gagal menyimpan data. Silakan coba lagi.');
    return;
}
```

The exception is silently discarded. In production, this makes debugging impossible.

**Fix:**

```php
} catch (Exception $e) {
    report($e);
    session()->flash('error', 'Gagal menyimpan data. Silakan coba lagi.');
    return;
}
```

---

### 12. No database-level constraint on `kehadiran` values — N/A (ALREADY IMPLEMENTED)

**File:** Database migration for `detail_aktivitas`
**Type:** Best Practice

The original migration at `database/migrations/2025_10_21_145659_create_detail_aktivitas_table.php` already uses:

```php
$table->enum('kehadiran', ['hadir', 'izin', 'sakit', 'alpa'])->default('alpa');
```

The DB-level constraint exists. No action needed.

---

## P3 — Low Priority

### 13. Dead code in `TahunAjaranObserver::creating` FIXED

**File:** `app/Observers/TahunAjaranObserver.php` — line 28
**Type:** Code Quality

```php
if ($tahunAjaran->status) {
    $activeExists = TahunAjaran::where('status', true)->exists();
    if ($activeExists) {
        throw ValidationException::withMessages([...]);
    }
    // This line is unreachable when $activeExists is true (exception thrown)
    // and a no-op when $activeExists is false (nothing to deactivate)
    TahunAjaran::query()->update(['status' => false]);
}
```

**Fix:** Remove the dead `update()` call or restructure the logic.

---

### 14. Missing policies for 5 of 7 domain models SKIP

**File:** `app/Policies/`
**Type:** Best Practice

Only `DetailAktivitasPolicy` exists. Missing policies for:
- `AktivitasPembelajaran`
- `Kelas`
- `Siswa`
- `MataPelajaran`
- `Laporan`

Authorization is scattered across Filament `canAccess()` methods and inline role checks in Livewire components. Centralizing into policies would improve maintainability and auditability.

---

### 15. Unused `Helpers.php` placeholder FIXED

**File:** `app/Helpers.php`
**Type:** Code Quality

Contains only a placeholder `example()` function. Should be removed or populated with actual helpers.

---

### 16. Empty `$appends` array on `Siswa` FIXED

**File:** `app/Models/Siswa.php` — line 32
**Type:** Code Quality

```php
protected $appends = [];
```

This serves no purpose and should be removed.

---

### 17. `catatan` condition discards legitimate "0" value FIXED

**File:** `app/Livewire/Teacher/AktivitasPembelajaran/CreateAktivitas.php` — line ~277
**Type:** Bug (minor)

```php
'catatan' => $this->catatan !== '' && $this->catatan !== '0' ? $this->catatan : null,
```

A note containing just the string `"0"` would be silently converted to `null`.

**Fix:**

```php
'catatan' => $this->catatan !== '' ? $this->catatan : null,
```

---

### 18. Health endpoint leaks DB status without auth FIXED

**File:** `routes/web.php` — lines 13-20
**Type:** Security (Low)

The `/health` endpoint is unauthenticated and returns `database => 'disconnected'` status. While standard for health checks, consider returning only `ok`/`error` status codes without internal details, or restrict to specific IPs.

---

### 19. Session-based `TahunAjaran` context is not validated SKIP

**File:** `app/Models/TahunAjaran.php` — lines 49-63
**Type:** Security (Low)

`getContext()` reads `tahun_ajaran_context` from the session and does `self::find($contextId)` without validating whether the user should have access to that academic year. While not exploitable in the current flow, adding a guard would future-proof the system.

---

### 20. No test coverage for critical business flows SKIP

**Location:** `tests/` directory
**Type:** Best Practice

No test files were found for the most critical and complex business flows:
- `GantiSemesterPage` (semester transition)
- `KenaikanKelasPage` (grade promotion)
- `CalculateReports` command
- Role-based access enforcement across all three panels

These are the highest-risk areas for regressions.

---

## Summary

| Priority | Count | Category |
|----------|-------|----------|
| **P0** | 4 | 3 Bugs, 1 Security |
| **P1** | 4 | 1 Security, 2 Performance, 1 Quality |
| **P2** | 5 | 2 Performance, 1 Quality, 1 Bug, 1 Best Practice |
| **P3** | 8 | 3 Quality, 2 Best Practice, 2 Security, 1 Bug |

**Overall:** The codebase is well-structured with consistent style and good separation of concerns. The P0 items should be addressed before the next deployment.
