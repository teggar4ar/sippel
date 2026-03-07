# Rencana Perbaikan SonarQube — SIPPEL

> **Referensi:** [`SONARQUBE_ISSUES.md`](./SONARQUBE_ISSUES.md)
> **Dibuat:** 2026-02-23 | **Total Issue:** 112 (17 Critical, 72 Major, 23 Minor)
> **Metodologi:** Berdasarkan [Refactor Skill](../.agents/skills/refactor/SKILL.md)

---

## Daftar Isi

1. [Ringkasan Strategi](#ringkasan-strategi)
2. [Fase 0 — Yang Bisa Diabaikan (Won't Fix)](#fase-0--yang-bisa-diabaikan-wont-fix)
3. [Fase 1 — Quick Wins: Dead Code & Redundant Jumps](#fase-1--quick-wins-dead-code--redundant-jumps)
4. [Fase 2 — String Duplikat → Konstanta](#fase-2--string-duplikat--konstanta)
5. [Fase 3 — Method Identik → Deduplikasi](#fase-3--method-identik--deduplikasi)
6. [Fase 4 — Cognitive Complexity → Extract Method](#fase-4--cognitive-complexity--extract-method)
7. [Fase 5 — Terlalu Banyak Return → Early Return / Extract Method](#fase-5--terlalu-banyak-return--early-return--extract-method)
8. [Fase 6 — Penamaan & Konvensi](#fase-6--penamaan--konvensi)
9. [Fase 7 — Aksesibilitas & HTML Semantik](#fase-7--aksesibilitas--html-semantik)
10. [Fase 8 — JavaScript Modernization](#fase-8--javascript-modernization)
11. [Rencana Verifikasi](#rencana-verifikasi)

---

## Ringkasan Strategi

| Fase | Kategori | Issues | Risiko | Teknik Refactoring |
|------|----------|--------|--------|---------------------|
| 0 | Won't Fix | 11 | — | Mark sebagai Won't Fix di SonarQube |
| 1 | Quick Wins | 7 | 🟢 Rendah | Remove dead code, redundant jumps |
| 2 | String Constants | 9 | 🟢 Rendah | Replace Magic String with Constant |
| 3 | Method Dedup | 7 | 🟡 Sedang | Extract Method, Consolidate Conditional |
| 4 | Complexity | 13 | 🟡 Sedang | Extract Method, Decompose Conditional |
| 5 | Too Many Returns | 13 | 🟡 Sedang | Guard Clauses, Extract Method |
| 6 | Naming Convention | 16 | 🔴 Tinggi | Rename (breaking change di Livewire) |
| 7 | Accessibility | 16 | 🟢 Rendah | Tambah atribut ARIA & semantic HTML |
| 8 | JS Modernization | 10 | 🟢 Rendah | `globalThis`, `?.`, `Number.parseInt` |

> [!IMPORTANT]
> **Fase 6 (Rename Livewire Properties)** memiliki risiko tertinggi karena mengubah nama property akan memutus binding `wire:model` di Blade. Harus dikerjakan **bersamaan** antara PHP class dan Blade view.

---

## Fase 0 — Yang Bisa Diabaikan (Won't Fix)

**Issues:** #60–#70 (11 issues) | **Rule:** `css:S4662`

`@source` adalah syntax valid **Tailwind CSS v4**. SonarQube belum mendukung parsing ini.

### Tindakan

- Buka SonarQube → navigate ke setiap issue → klik **"Won't Fix"**
- Atau tambahkan komentar di file CSS:

```css
/* sonarqube: css:S4662 is a false positive — @source is Tailwind CSS v4 syntax */
```

### File yang terpengaruh

| File | Issues |
|------|--------|
| [`resources/css/app.css`](../resources/css/app.css) | #60–#68 |
| [`resources/css/filament/admin/theme.css`](../resources/css/filament/admin/theme.css) | #69–#70 |

---

## Fase 1 — Quick Wins: Dead Code & Redundant Jumps ***DONE***

**Issues:** #20, #34, #53, #56, #58, #90, #97, #102 (8 issues)
**Rules:** `php:S1481`, `php:S3626`, `php:S125`, `php:S1066`, `php:S1488`
**Teknik:** Remove Dead Code, Inline Variable

> [!TIP]
> Fase ini aman dan berdampak rendah. Bisa dikerjakan pertama sebagai pemanasan.

---

### 1.1 Hapus variabel tidak digunakan (`php:S1481`)

#### [ClassReportExport.php](../app/Exports/ClassReportExport.php) — Issue #20, #90

**Masalah:** Variabel `$date` di-assign tapi tidak digunakan.

```diff
  // Sekitar baris 198
- $date = now()->format('d-m-Y');
  // Hapus baris ini seluruhnya
```

---

### 1.2 Hapus redundant jump (`php:S3626`)

#### [EditUser.php](../app/Filament/Resources/Users/Pages/EditUser.php) — Issue #34, #97

**Masalah:** Ada `return` yang tidak perlu di akhir method void.

```diff
  // Sekitar baris 63
  // Hapus statement `return;` yang redundan di akhir method
- return;
```

#### [CreateAktivitas.php](../app/Livewire/Teacher/AktivitasPembelajaran/CreateAktivitas.php) — Issue #102

**Masalah:** Redundant `return` di akhir method.

```diff
  // Sekitar baris 80
- return;
```

---

### 1.3 Hapus kode yang di-comment (`php:S125`)

#### [User.php](../app/Models/User.php) — Issue #53

```diff
  // Baris 7 — hapus baris commented-out
- // use Illuminate\Contracts\Auth\MustVerifyEmail;
```

#### [DatabaseSeeder.php](../database/seeders/DatabaseSeeder.php) — Issue #58

```diff
  // Baris 8 — hapus baris commented-out code
- // $this->call(SomeSeeder::class);
```

---

### 1.4 Gabungkan nested `if` (`php:S1066`)

#### [AppServiceProvider.php](../app/Providers/AppServiceProvider.php) — Issue #56

**Masalah:** Nested `if` yang bisa digabung.

```diff
  // Sekitar baris 32
- if ($conditionA) {
-     if ($conditionB) {
-         // code
-     }
- }
+ if ($conditionA && $conditionB) {
+     // code
+ }
```

---

### 1.5 Langsung return (`php:S1488`)

#### [Laporan.php](../app/Livewire/Teacher/Laporan.php) — Issue #46, #105

**Masalah:** Assign ke variabel lalu langsung return — bisa di-inline.

```diff
  // Sekitar baris 64
- $user = Auth::user();
- return $user;
+ return Auth::user();
```

---

### 1.6 Gunakan dedicated exception (`php:S112`) ***Wont Fix*** (Karena file migrasi dari package Spatie Permission)    

#### [create_permission_tables.php](../database/migrations/2025_10_21_143714_create_permission_tables.php) — Issue #57

**Masalah:** Menggunakan generic `\Exception`.

```diff
- throw new \Exception('...');
+ throw new \RuntimeException('...');
```

> [!NOTE]
> Karena ini file migrasi dari package Spatie Permission, periksa apakah file ini auto-generated. Jika ya, biarkan saja dan mark sebagai Won't Fix.

---

## Fase 2 — String Duplikat → Konstanta ***DONE***

**Issues:** #2, #7, #10, #11, #13, #17, #18, #19, #59 (9 issues)
**Rule:** `php:S1192`
**Teknik:** Replace Magic String with Constant

> [!TIP]
> Definisikan string sebagai `const` di class masing-masing, atau di file `constants.php` untuk string lintas-class.

---

### 2.1 ClassReport — "Data tidak ditemukan"

#### [ClassReport.php](../app/Filament/Pages/ClassReport.php) — Issue #2

```diff
+ private const MSG_DATA_NOT_FOUND = 'Data tidak ditemukan';

  // Ganti ketiga kemunculan:
- 'Data tidak ditemukan'
+ self::MSG_DATA_NOT_FOUND
```

---

### 2.2 MataPelajaranResource — "Mata Pelajaran"

#### [MataPelajaranResource.php](../app/Filament/Resources/MataPelajarans/MataPelajaranResource.php) — Issue #7

String `"Mata Pelajaran"` sudah digunakan di 3 property statis (`$modelLabel`, `$pluralModelLabel`, `$navigationLabel`). Karena ini property Filament yang memerlukan string literal, alternatifnya:

```diff
+ private const RESOURCE_LABEL = 'Mata Pelajaran';

  protected static ?string $modelLabel = self::RESOURCE_LABEL;
  // Catatan: Filament mungkin tidak mendukung `const` di property statis.
  // Jika tidak support, mark sebagai Won't Fix di SonarQube.
```

> [!WARNING]
> Filament mungkin tidak mendukung penggunaan `const` dalam deklarasi property statis. Uji terlebih dahulu. Jika tidak bisa, mark sebagai **Won't Fix**.

---

### 2.3 TahunAjaranResource — "Tahun Ajaran"

#### [TahunAjaranResource.php](../app/Filament/Resources/TahunAjarans/TahunAjaranResource.php) — Issue #10

Perlakuan sama seperti 2.2 di atas.

---

### 2.4 EditUser & UsersTable — "Tidak dapat menghapus user"

#### [EditUser.php](../app/Filament/Resources/Users/Pages/EditUser.php) — Issue #11
#### [UsersTable.php](../app/Filament/Resources/Users/Tables/UsersTable.php) — Issue #13

Karena string yang sama digunakan di **2 file berbeda**, buat shared constant:

```php
// Opsi A: Tambahkan di UserResource.php
final class UserResource extends Resource
{
    public const MSG_CANNOT_DELETE = 'Tidak dapat menghapus user';
    // ...
}

// Opsi B: Tambahkan di model User.php
class User extends Model
{
    public const MSG_CANNOT_DELETE = 'Tidak dapat menghapus user';
}
```

Kemudian ganti di `EditUser.php` dan `UsersTable.php`:

```diff
- 'Tidak dapat menghapus user'
+ UserResource::MSG_CANNOT_DELETE
```

---

### 2.5 Laporan — String pesan error

#### [Laporan.php](../app/Livewire/Teacher/Laporan.php) — Issue #17, #18

```diff
+ private const MSG_NO_CLASS_ACCESS = 'Anda tidak memiliki akses ke kelas ini.';
+ private const MSG_INCOMPLETE_DATA = 'Data tidak lengkap.';

  // Ganti semua kemunculan
- 'Anda tidak memiliki akses ke kelas ini.'
+ self::MSG_NO_CLASS_ACCESS

- 'Data tidak lengkap.'
+ self::MSG_INCOMPLETE_DATA
```

---

### 2.6 config/database.php — "127.0.0.1"

#### [database.php](../config/database.php) — Issue #19

```diff
  // Di awal file config
+ $defaultHost = env('DB_HOST', '127.0.0.1');

  // Ganti semua kemunculan env('DB_HOST', '127.0.0.1')
  // dengan $defaultHost
```

> [!NOTE]
> `config/database.php` adalah file Laravel core config. Perubahan ini aman tapi perlu di-test bahwa semua koneksi database masih bekerja.

---

### 2.7 RolePermissionSeeder — "view reports"

#### [RolePermissionSeeder.php](../database/seeders/RolePermissionSeeder.php) — Issue #59

```diff
+ private const PERM_VIEW_REPORTS = 'view reports';

  // Ganti semua kemunculan
- 'view reports'
+ self::PERM_VIEW_REPORTS
```

---

## Fase 3 — Method Identik → Deduplikasi ***DONE***

**Issues:** #24, #26, #31, #33, #36, #47, #55 (7 issues)
**Rule:** `php:S4144`
**Teknik:** Extract Method, Consolidate Duplicate

---

### 3.1 Filament Resource `canAccess` ≡ `shouldRegisterNavigation`

Pola yang sama terulang di 5 Resource class: method `canAccess()` dan `shouldRegisterNavigation()` memiliki body **identik**.

**File yang terpengaruh:**

| File | Issue |
|------|-------|
| [KelasResource.php](../app/Filament/Resources/Kelas/KelasResource.php) | #24 |
| [MataPelajaranResource.php](../app/Filament/Resources/MataPelajarans/MataPelajaranResource.php) | #26 |
| [SiswaResource.php](../app/Filament/Resources/Siswas/SiswaResource.php) | #31 |
| [TahunAjaranResource.php](../app/Filament/Resources/TahunAjarans/TahunAjaranResource.php) | #33 |
| [UserResource.php](../app/Filament/Resources/Users/UserResource.php) | #36 |

**Strategi: Extract helper method, delegasi dari kedua method**

```php
// Contoh di KelasResource.php
private static function isAdminUser(): bool
{
    $user = Auth::user();
    /** @var \App\Models\User|null $user */
    return Auth::check() && $user && $user->hasRole('admin');
}

public static function shouldRegisterNavigation(): bool
{
    return self::isAdminUser();
}

public static function canAccess(): bool
{
    return self::isAdminUser();
}
```

**Alternatif (lebih DRY):**

Buat **trait** `AdminOnlyResource`:

```php
// app/Filament/Concerns/AdminOnlyResource.php [NEW]
namespace App\Filament\Concerns;

use App\Models\User;
use Illuminate\Support\Facades\Auth;

trait AdminOnlyResource
{
    private static function isAdminUser(): bool
    {
        $user = Auth::user();
        if (! $user instanceof User) {
            return false;
        }
        return $user->hasRole('admin');
    }

    public static function shouldRegisterNavigation(): bool
    {
        return self::isAdminUser();
    }

    public static function canAccess(): bool
    {
        return self::isAdminUser();
    }
}
```

Lalu `use AdminOnlyResource;` di setiap resource yang terpengaruh, dan hapus method `shouldRegisterNavigation()` + `canAccess()` dari masing-masing class.

---

### 3.2 Laporan — `updatedKelasId` ≡ `updatedReportType`

#### [Laporan.php](../app/Livewire/Teacher/Laporan.php) — Issue #47

**Masalah:** Method `updatedKelasId()` (L293–L301) identik dengan `updatedReportType()` (L283–L291).

```diff
+ private function resetDependentFields(): void
+ {
+     $this->siswaId = '';
+     $this->mataPelajaranId = '';
+     $this->sortBy = 'nama';
+     $this->showPreview = false;
+     $this->previewData = null;
+ }

  public function updatedReportType(): void
  {
-     // body identik
+     $this->kelasId = '';
+     $this->resetDependentFields();
  }

  public function updatedKelasId(): void
  {
-     // body identik
+     $this->resetDependentFields();
  }
```

---

### 3.3 DetailAktivitasPolicy — `delete` ≡ `update`

#### [DetailAktivitasPolicy.php](../app/Policies/DetailAktivitasPolicy.php) — Issue #55

**Masalah:** `delete()` body identik dengan `update()`.

```diff
+ private function canModify(User $user, DetailAktivitas $detailAktivitas): bool
+ {
+     if ($user->hasRole('admin')) {
+         return true;
+     }
+     if ($user->hasRole('teacher')) {
+         $allowedKelasIds = $this->getTeacherKelasIds($user);
+         $aktivitas = $detailAktivitas->aktivitasPembelajaran;
+         return $aktivitas !== null && in_array($aktivitas->kelas_id, $allowedKelasIds, true);
+     }
+     return false;
+ }

  public function update(User $user, DetailAktivitas $detailAktivitas): bool
  {
-     // body lama
+     return $this->canModify($user, $detailAktivitas);
  }

  public function delete(User $user, DetailAktivitas $detailAktivitas): bool
  {
-     // body identik
+     return $this->canModify($user, $detailAktivitas);
  }
```

---

## Fase 4 — Cognitive Complexity → Extract Method ***DONE*** ***TEST PASSED***

**Issues:** #1, #3, #4, #5, #6, #8, #9, #12, #14, #15, #16 (11 issues)
**Rule:** `php:S3776`
**Teknik:** Extract Method, Decompose Conditional

> [!IMPORTANT]
> Ini adalah fase paling berdampak. Setiap method yang dipecah harus di-test ulang.

---

### 4.1 CalculateReports — Complexity 21 ***TEST PASSED***

#### [CalculateReports.php](../app/Console/Commands/CalculateReports.php) — Issue #1, L45

**Strategi:**
1. Identifikasi blok logika di dalam method `handle()` (atau method yang dimulai di L45)
2. Pecah menurut tanggung jawab:
   - `fetchReportData()` — query data
   - `calculateScores()` — hitung nilai
   - `saveResults()` — simpan hasil
3. Method utama menjadi **orchestrator** yang hanya memanggil sub-method

---

### 4.2 GantiSemesterPage — Complexity 16 ***TEST PASSED***

#### [GantiSemesterPage.php](../app/Filament/Pages/GantiSemesterPage.php) — Issue #3, L178

**Strategi:** Pecah method di L178 menjadi:
- `validateSemesterTransition()` — validasi kondisi pergantian
- `executeSemesterChange()` — eksekusi perubahan
- `notifySemesterChange()` — kirim notifikasi

---

### 4.3 KenaikanKelasPage — Complexity 40 dan 36 ***TEST PASSED***

#### [KenaikanKelasPage.php](../app/Filament/Pages/KenaikanKelasPage.php) — Issue #4 (L118, CC=40), #5 (L280, CC=36)

**Ini adalah file paling kompleks.** 2 method melebihi batas dengan sangat jauh.

**Method `form()` (L118–L278, CC=40) — Strategi:**

```php
// Pecah konfigurasi wizard ke method terpisah
public function form(Schema $form): Schema
{
    return $form->schema([
        Wizard::make([
            $this->buildStepSelectTahunAjaran(),
            $this->buildStepSelectKelas(),
            $this->buildStepReviewSiswa(),
        ])
    ]);
}

private function buildStepSelectTahunAjaran(): Wizard\Step { /* ... */ }
private function buildStepSelectKelas(): Wizard\Step { /* ... */ }
private function buildStepReviewSiswa(): Wizard\Step { /* ... */ }
```

**Method `create()` (L280–L411, CC=36) — Strategi:**

```php
public function create(): void
{
    $this->validateKenaikanData();
    $newKelas = $this->resolveOrCreateTargetKelas();
    $this->processStudentPromotion($newKelas);
    $this->sendSuccessNotification();
}

private function validateKenaikanData(): void { /* ... */ }
private function resolveOrCreateTargetKelas(): Kelas { /* ... */ }
private function processStudentPromotion(Kelas $kelas): void { /* ... */ }
```

---

### 4.4 KelasForm — Complexity 18 ***TEST PASSED***

#### [KelasForm.php](../app/Filament/Resources/Kelas/Schemas/KelasForm.php) — Issue #6, L15

**Strategi:** Pecah form builder menjadi:
- `buildNamaFields()` — field tingkat & grup kelas
- `buildWaliKelasField()` — field wali kelas dengan validation
- `buildTahunAjaranField()` — field tahun ajaran

---

### 4.5 CreateMataPelajaran — Complexity 29 ***TEST PASSED***

#### [CreateMataPelajaran.php](../app/Filament/Resources/MataPelajarans/Pages/CreateMataPelajaran.php) — Issue #8, L24

**Strategi:** Extract validation dan business logic ke helper methods.

---

### 4.6 MataPelajaranForm — Complexity 30 ***TEST PASSED***

#### [MataPelajaranForm.php](../app/Filament/Resources/MataPelajarans/Schemas/MataPelajaranForm.php) — Issue #9, L24

Juga terkait Issue #27 (fungsi >150 baris) dan #28 (parameter tidak digunakan).

**Strategi gabungan:**
1. Pecah `configure()` menjadi sub-methods per field group
2. Hapus parameter `$attribute` yang tidak digunakan (#28)
3. Hasil: complexity turun + panjang fungsi <150 baris

```php
public static function configure(Schema $schema): Schema
{
    return $schema->schema([
        ...self::buildBasicInfoFields(),
        ...self::buildScheduleFields(),
        ...self::buildRelationFields(),
    ]);
}
```

---

### 4.7 UsersTable — Complexity 20 ***TEST PASSED***

#### [UsersTable.php](../app/Filament/Resources/Users/Tables/UsersTable.php) — Issue #12, L19

**Strategi:** Pecah `configure()`:
- `buildColumns()` — definisi kolom
- `buildFilters()` — definisi filter
- `buildActions()` — definisi actions

---

### 4.8 Student Dashboard — Complexity 18  ***TEST PASSED***

#### [Dashboard.php](../app/Livewire/Student/Dashboard.php) — Issue #14, L128

Juga terkait Issue #39 (branch duplikat).

**Strategi:** Extract data loading dan calculation ke helper methods, gabungkan branch duplikat (#39).

```diff
  // Issue #39: Gabungkan branch identik
- case 'A':
-     $result = 'Lulus';
-     break;
- case 'B':
-     $result = 'Lulus';
-     break;
+ case 'A':
+ case 'B':
+     $result = 'Lulus';
+     break;
```

---

### 4.9 CreateAktivitas — Complexity 25 ***TEST PASSED***

#### [CreateAktivitas.php](../app/Livewire/Teacher/AktivitasPembelajaran/CreateAktivitas.php) — Issue #15, L257

**Strategi:** Method `save()` harus dipecah:
- `validateSaveData()` — validasi input
- `createAktivitasRecord()` — simpan aktivitas utama
- `createDetailRecords()` — simpan detail per siswa
- `handleSaveSuccess()` — redirect & flash message

---

### 4.10 EditAktivitas — Complexity 24 ***TEST PASSED***

#### [EditAktivitas.php](../app/Livewire/Teacher/AktivitasPembelajaran/EditAktivitas.php) — Issue #16, L138

**Strategi:** Serupa dengan 4.9. Pecah method kompleks menjadi step-by-step helpers.

---

## Fase 5 — Terlalu Banyak Return → Early Return / Extract Method ***DONE***

**Issues:** #21, #22, #23, #29, #37, #38, #40, #48, #49, #50, #51, #52, #54 (13 issues)
**Rules:** `php:S1142`, `php:S3358`, `php:S1871`
**Teknik:** Guard Clauses, Extract Method

---

### 5.1 Strategi Umum: Early Return Pattern

Untuk method dengan terlalu banyak `return`, gunakan **guard clauses** di awal:

```diff
  // Contoh pola yang sering muncul
- public function doSomething() {
-     if ($condA) {
-         return $this->handleA();
-     } elseif ($condB) {
-         return $this->handleB();
-     } elseif ($condC) {
-         return $this->handleC();
-     } else {
-         return default;
-     }
- }

  // Refactor: extract validasi dan logika ke method terpisah
+ public function doSomething() {
+     $this->validatePreconditions(); // throws on failure
+     return $this->resolveResult();
+ }
```

---

### 5.2 File-by-file breakdown

| File | Issue | Method (Baris) | Returns | Solusi |
|------|-------|----------------|---------|--------|
| [Login.php](../app/Filament/Pages/Auth/Login.php) | #21 | `authenticate()` L110 | 5 | Extract validation ke `validateCredentials()` |
| [ClassReport.php](../app/Filament/Pages/ClassReport.php) | #22 | L167 | 4 | Guard clause + early return |
| [KenaikanKelasPage.php](../app/Filament/Pages/KenaikanKelasPage.php) | #23 | L214 | 4 | Sudah di-handle di Fase 4.3 |
| [MataPelajaranForm.php](../app/Filament/Resources/MataPelajarans/Schemas/MataPelajaranForm.php) | #29 | L155 | 4 | Sudah di-handle di Fase 4.6 |
| [EnsureUserIsAdmin.php](../app/Http/Middleware/EnsureUserIsAdmin.php) | #37 | `handle()` L20 | 6 | Guard clauses di awal |
| [LoginResponse.php](../app/Http/Responses/LoginResponse.php) | #38 | `toResponse()` L16 | 5 | Map role ke route |
| [CreateAktivitas.php](../app/Livewire/Teacher/AktivitasPembelajaran/CreateAktivitas.php) | #40 | `save()` L257 | 4 | Sudah di-handle di Fase 4.9 |
| [Laporan.php](../app/Livewire/Teacher/Laporan.php) | #48–#51 | Multiple | 4–5 | Extract validation & download logic |
| [Siswa.php](../app/Models/Siswa.php) | #52 | L97 | 4 | Guard clause |
| [DetailAktivitasPolicy.php](../app/Policies/DetailAktivitasPolicy.php) | #54 | `view()` L39 | 4 | Sudah di-handle di Fase 3.3 |

---

### 5.3 Nested Ternary → If/Else (`php:S3358`)

#### [CreateAktivitas.php](../app/Livewire/Teacher/AktivitasPembelajaran/CreateAktivitas.php) — Issue #41, #42

```diff
  // Baris 315-316: nested ternary
- $value = $a ? ($b ? 'x' : 'y') : 'z';
+ if ($a && $b) {
+     $value = 'x';
+ } elseif ($a) {
+     $value = 'y';
+ } else {
+     $value = 'z';
+ }
```

#### [EditAktivitas.php](../app/Livewire/Teacher/AktivitasPembelajaran/EditAktivitas.php) — Issue #43, #44

Perlakuan sama.

---

### 5.4 LoginResponse — Map Role ke Route

#### [LoginResponse.php](../app/Http/Responses/LoginResponse.php) — Issue #38

```diff
- public function toResponse($request)
- {
-     if ($user->hasRole('admin')) return redirect('/admin');
-     if ($user->hasRole('teacher')) return redirect('/teacher/dashboard');
-     if ($user->hasRole('student')) return redirect('/student/dashboard');
-     // ...lebih banyak return
- }

+ private const ROLE_ROUTES = [
+     'admin'   => '/admin',
+     'teacher' => '/teacher/dashboard',
+     'student' => '/student/dashboard',
+ ];
+
+ public function toResponse($request)
+ {
+     $user = Auth::user();
+     foreach (self::ROLE_ROUTES as $role => $route) {
+         if ($user->hasRole($role)) {
+             return redirect($route);
+         }
+     }
+     return redirect('/');
+ }
```

---

### 5.5 EnsureUserIsAdmin — Guard Clauses

#### [EnsureUserIsAdmin.php](../app/Http/Middleware/EnsureUserIsAdmin.php) — Issue #37

```diff
+ public function handle($request, Closure $next)
+ {
+     if (! Auth::check()) {
+         return redirect()->route('login');
+     }
+
+     $user = Auth::user();
+     if (! $user instanceof User || ! $user->hasRole('admin')) {
+         abort(403);
+     }
+
+     return $next($request);
+ }
```

---

## Fase 6 — Penamaan & Konvensi ***DONE***

**Issues:** #25, #28, #30, #35, #91–#104 (16 issues)
**Rules:** `php:S116`, `php:S1172`
**Teknik:** Rename Variable, Remove Unused Parameter

> [!CAUTION]
> **Risiko tinggi!** Rename Livewire property `$snake_case` → `$camelCase` **akan memutus `wire:model` binding di Blade view**. Setiap rename harus dilakukan bersamaan di PHP class **dan** Blade template.

---

### 6.1 Hapus parameter tidak digunakan (`php:S1172`)

| File | Issue | Baris | Parameter | Solusi |
|------|-------|-------|-----------|--------|
| [KelasForm.php](../app/Filament/Resources/Kelas/Schemas/KelasForm.php) | #25 | L90 | `$attribute` | Ganti dengan `$_` |
| [MataPelajaranForm.php](../app/Filament/Resources/MataPelajarans/Schemas/MataPelajaranForm.php) | #28 | L36 | `$attribute` | Ganti dengan `$_` |
| [SiswaForm.php](../app/Filament/Resources/Siswas/Schemas/SiswaForm.php) | #30 | L73 | `$get` | Ganti dengan `$_` |
| [UserForm.php](../app/Filament/Resources/Users/Schemas/UserForm.php) | #35 | L69 | `$attribute` | Ganti dengan `$_` |

```diff
  // Contoh: callback di Filament validation
- ->rules(function ($attribute, $value, $fail) {
+ ->rules(function ($_, $value, $fail) {
      // ...
  })
```

> [!NOTE]
> Periksa apakah callback signature diperlukan oleh Filament. Jika Filament mewajibkan parameter tersebut dalam closure, gunakan `$_` (underscore) sebagai nama.

---

### 6.2 Rename Livewire properties snake_case → camelCase (`php:S116`)

Ini harus dikerjakan per-komponen, **PHP + Blade secara bersamaan**.

#### 6.2.1 ClassReport.php — Issue #91–#94

| Property lama | Property baru |
|---------------|---------------|
| `$kelas_id` | `$kelasId` |
| `$mata_pelajaran_id` | `$mataPelajaranId` |
| `$tahun_ajaran_id` | `$tahunAjaranId` |
| `$sort_by` | `$sortBy` |

**Blade file yang perlu di-update:** Cari semua `wire:model` yang merujuk property lama.

#### 6.2.2 StudentReport.php — Issue #95, #96

| Property lama | Property baru |
|---------------|---------------|
| `$siswa_id` | `$siswaId` |
| `$tahun_ajaran_id` | `$tahunAjaranId` |

#### 6.2.3 CreateAktivitas.php — Issue #98–#101

| Property lama | Property baru |
|---------------|---------------|
| `$tingkat_kelas` | `$tingkatKelas` |
| `$grup_kelas` | `$grupKelas` |
| `$mata_pelajaran_id` | `$mataPelajaranId` |
| `$kelas_id` | `$kelasId` |

**Blade:** [`create-aktivitas.blade.php`](../resources/views/livewire/teacher/aktivitas-pembelajaran/create-aktivitas.blade.php)

#### 6.2.4 EditAktivitas.php — Issue #103, #104

| Property lama | Property baru |
|---------------|---------------|
| `$mata_pelajaran_id` | `$mataPelajaranId` |
| `$kelas_id` | `$kelasId` |

**Blade:** [`edit-aktivitas.blade.php`](../resources/views/livewire/teacher/aktivitas-pembelajaran/edit-aktivitas.blade.php)

---

### 6.3 Fungsi terlalu panjang (`php:S138`)

| File | Issue | Baris Total | Solusi |
|------|-------|-------------|--------|
| [MataPelajaranForm.php](../app/Filament/Resources/MataPelajarans/Schemas/MataPelajaranForm.php) | #27 | 190 | Sudah di-cover Fase 4.6 |
| [TahunAjaransTable.php](../app/Filament/Resources/TahunAjarans/Tables/TahunAjaransTable.php) | #32 | 158 | Pecah ke `buildColumns()`, `buildActions()`, `buildBulkActions()` |

---

### 6.4 Class terlalu banyak method (`php:S1448`)

#### [Laporan.php](../app/Livewire/Teacher/Laporan.php) — Issue #45

**Masalah:** Class `Laporan` punya 22 method (maks 20).

**Strategi:** Extract group of methods ke **trait** atau **service class**:

```
Laporan.php (component utama)
├── Traits/
│   ├── LaporanDownloads.php    → downloadStudentPdf(), downloadClassPdf(), exportClassExcel()
│   └── LaporanComputed.php     → computed properties (kelasWali, siswaList, etc.)
└── (sisanya di Laporan.php)
```

Atau gunakan **Service Class**:

```php
// app/Services/LaporanService.php [NEW]
class LaporanService
{
    public function downloadStudentPdf(...): StreamedResponse { /* ... */ }
    public function downloadClassPdf(...): StreamedResponse { /* ... */ }
    public function exportClassExcel(...): StreamedResponse { /* ... */ }
}
```

---

## Fase 7 — Aksesibilitas & HTML Semantik

**Issues:** #74–#89 (16 issues)
**Rules:** `Web:S5255`, `Web:S6853`, `Web:S5256`
**Teknik:** Tambah atribut ARIA, perbaiki semantic HTML

---

### 7.1 Tambah `aria-label` pada elemen navigasi (`Web:S5255`)

#### [student.blade.php](../resources/views/layouts/student.blade.php) — Issue #74–#77
#### [teacher.blade.php](../resources/views/layouts/teacher.blade.php) — Issue #78–#81

```diff
  <!-- Navbar utama -->
- <nav>
+ <nav aria-label="Navigasi Utama">

  <!-- Sidebar / mobile menu -->
- <nav>
+ <nav aria-label="Menu Sidebar">

  <!-- Footer nav -->
- <nav>
+ <nav aria-label="Navigasi Footer">
```

---

### 7.2 Hubungkan label ke form control (`Web:S6853`)

#### [create-aktivitas.blade.php](../resources/views/livewire/teacher/aktivitas-pembelajaran/create-aktivitas.blade.php) — Issue #82–#84
#### [edit-aktivitas.blade.php](../resources/views/livewire/teacher/aktivitas-pembelajaran/edit-aktivitas.blade.php) — Issue #85–#87

```diff
  <!-- Setiap <label> harus punya atribut `for` yang cocok dengan `id` pada input -->
- <label>Nama Aktivitas</label>
- <input type="text" wire:model="nama">
+ <label for="nama-aktivitas">Nama Aktivitas</label>
+ <input type="text" id="nama-aktivitas" wire:model="nama">
```

---

### 7.3 Tambah `<th>` pada tabel (`Web:S5256`)

#### [class-report.blade.php](../resources/views/reports/class-report.blade.php) — Issue #88
#### [student-report.blade.php](../resources/views/reports/student-report.blade.php) — Issue #89

```diff
  <table>
+   <thead>
+     <tr>
+       <th>No</th>
+       <th>Nama</th>
+       <th>Nilai</th>
+     </tr>
+   </thead>
    <tbody>
      <!-- data rows -->
    </tbody>
  </table>
```

---

## Fase 8 — JavaScript Modernization

**Issues:** #71–#73, #106–#114 (10 issues)
**Rules:** `javascript:S7764`, `javascript:S6582`, `javascript:S7721`, `javascript:S7773`
**Teknik:** API Modernization

---

### 8.1 `window` → `globalThis` (`javascript:S7764`)

#### [bootstrap.js](../resources/js/bootstrap.js) — Issue #106–#109

```diff
- window.axios = axios;
+ globalThis.axios = axios;

- window.axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';
+ globalThis.axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';
```

#### [inactivity-timer.js](../resources/js/inactivity-timer.js) — Issue #110–#112

```diff
- window.addEventListener(...)
+ globalThis.addEventListener(...)

- window.location.href = ...
+ globalThis.location.href = ...
```

---

### 8.2 Optional chaining `?.` (`javascript:S6582`)

#### [bootstrap.js](../resources/js/bootstrap.js) — Issue #71

```diff
- if (token && token.content) {
+ if (token?.content) {
```

#### [inactivity-timer.js](../resources/js/inactivity-timer.js) — Issue #73

```diff
- if (element && element.style) {
+ if (element?.style) {
```

---

### 8.3 `formatTime` ke outer scope (`javascript:S7721`)

#### [inactivity-timer.js](../resources/js/inactivity-timer.js) — Issue #72

```diff
  // Pindahkan fungsi formatTime dari dalam closure ke module scope
+ function formatTime(seconds) {
+     const mins = Math.floor(seconds / 60);
+     const secs = seconds % 60;
+     return `${mins}:${secs.toString().padStart(2, '0')}`;
+ }

  function initTimer() {
-     function formatTime(seconds) { ... }
      // gunakan formatTime() yang sekarang di outer scope
  }
```

---

### 8.4 `Number.parseInt` bukan `parseInt` (`javascript:S7773`)

#### [inactivity-timer.js](../resources/js/inactivity-timer.js) — Issue #113, #114

```diff
- parseInt(value, 10)
+ Number.parseInt(value, 10)
```

---

## Rencana Verifikasi

### Automated Tests

```bash
# 1. Jalankan semua unit tests yang sudah ada
php artisan test

# 2. Jalankan PHPStan/Larastan (jika tersedia)
./vendor/bin/phpstan analyse

# 3. Jalankan Laravel Pint untuk code style
./vendor/bin/pint --test

# 4. Re-scan SonarQube setelah semua perubahan
# (via CI/CD pipeline atau manual scan)
```

### Manual Verification (per Fase)

| Fase | Cara Verifikasi |
|------|-----------------|
| 0 | Cek SonarQube dashboard — 11 issues hilang |
| 1 | `php artisan test` — semua test tetap hijau |
| 2 | `php artisan test` + grep string lama memastikan tidak ada yang tertinggal |
| 3 | `php artisan test` + buka halaman admin (Kelas, Mata Pelajaran, User) — pastikan navigasi & akses masih bekerja |
| 4 | `php artisan test` + test manual di halaman Kenaikan Kelas, Ganti Semester, Create Aktivitas |
| 5 | `php artisan test` + login dengan role admin/teacher/student — pastikan routing benar |
| 6 | **KRITIS:** Buka setiap halaman Livewire yang terpengaruh, pastikan semua `wire:model` binding masih bekerja (dropdown, input, dll) |
| 7 | Jalankan Lighthouse accessibility audit di Chrome DevTools |
| 8 | `npm run build` — pastikan tidak ada error. Buka halaman, pastikan inactivity timer bekerja |

### SonarQube Re-scan

Setelah semua fase selesai:
1. Commit semua perubahan
2. Push ke branch
3. Trigger SonarQube scan
4. **Target:** 0 Critical, <10 Major, sisanya Won't Fix

---

> **Catatan akhir:** Kerjakan fase secara berurutan. Commit setelah setiap fase. Jangan gabungkan refactoring dengan perubahan fitur baru.
