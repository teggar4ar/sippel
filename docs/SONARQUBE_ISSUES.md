# SonarQube Issues — SIPPEL

> **Project:** `my-first-project` | **Total:** 112 issues | **Quality Gate:** ✅ OK  
> **Diambil pada:** 2026-02-21 | Diurutkan berdasarkan severity lalu file

---

## Ringkasan per Severity

| Severity | Jumlah |
|----------|--------|
| 🔴 CRITICAL | 17 |
| 🟠 MAJOR | 72 |
| 🟡 MINOR | 23 |

---

## Ringkasan per Rule

| Rule | Keterangan | Jumlah |
|------|-----------|--------|
| `php:S3776` | Cognitive Complexity terlalu tinggi (>15) | 11 |
| `php:S1192` | String literal duplikat, harus jadi konstanta | 9 |
| `php:S1142` | Terlalu banyak `return` dalam satu method (>3) | 13 |
| `php:S4144` | Method identik dengan method lain | 7 |
| `php:S1172` | Parameter fungsi tidak digunakan | 4 |
| `php:S3358` | Nested ternary operator | 4 |
| `Web:S6853` | Form label tidak terhubung ke control | 6 |
| `Web:S5255` | Elemen navigasi tanpa `aria-label` | 8 |
| `css:S4662` | Unknown at-rule `@source` (Tailwind v4) | 10 |
| `javascript:S7764` | Gunakan `globalThis` bukan `window` | 8 |
| `php:S116` | Penamaan field tidak sesuai konvensi camelCase | 12 |
| `php:S138` | Fungsi terlalu panjang (>150 baris) | 2 |
| `Web:S5256` | Tabel HTML tanpa `<th>` header | 2 |
| `javascript:S6582` | Gunakan optional chaining (`?.`) | 2 |
| `php:S1448` | Class terlalu banyak method (>20) | 1 |
| `php:S1871` | Branch duplikat di kondisi | 1 |
| `php:S1066` | Gabungkan nested `if` | 1 |
| `php:S112` | Gunakan dedicated exception, bukan generic | 1 |
| `php:S125` | Hapus kode yang di-comment | 2 |
| `php:S1488` | Langsung return, jangan assign ke variabel sementara | 1 |
| `php:S3626` | Redundant jump (return/continue) | 2 |
| `php:S1481` | Variabel lokal tidak digunakan | 1 |
| `javascript:S7721` | Pindahkan fungsi ke outer scope | 1 |
| `javascript:S7773` | Gunakan `Number.parseInt` bukan `parseInt` | 2 |

---

## 🔴 CRITICAL Issues

### `app/Console/Commands/CalculateReports.php`

| # | Rule | Baris | Pesan |
|---|------|-------|-------|
| 1 | `php:S3776` | [L45](../app/Console/Commands/CalculateReports.php#L45) | Cognitive Complexity **21** > 15 yang diizinkan |

### `app/Filament/Pages/ClassReport.php`

| # | Rule | Baris | Pesan |
|---|------|-------|-------|
| 2 | `php:S1192` | [L92](../app/Filament/Pages/ClassReport.php#L92) | String `"Data tidak ditemukan"` diduplikasi 3 kali — buat konstanta |

### `app/Filament/Pages/GantiSemesterPage.php`

| # | Rule | Baris | Pesan |
|---|------|-------|-------|
| 3 | `php:S3776` | [L178](../app/Filament/Pages/GantiSemesterPage.php#L178) | Cognitive Complexity **16** > 15 yang diizinkan |

### `app/Filament/Pages/KenaikanKelasPage.php`

| # | Rule | Baris | Pesan |
|---|------|-------|-------|
| 4 | `php:S3776` | [L118](../app/Filament/Pages/KenaikanKelasPage.php#L118) | Cognitive Complexity **40** > 15 yang diizinkan |
| 5 | `php:S3776` | [L280](../app/Filament/Pages/KenaikanKelasPage.php#L280) | Cognitive Complexity **36** > 15 yang diizinkan |

### `app/Filament/Resources/Kelas/Schemas/KelasForm.php`

| # | Rule | Baris | Pesan |
|---|------|-------|-------|
| 6 | `php:S3776` | [L15](../app/Filament/Resources/Kelas/Schemas/KelasForm.php#L15) | Cognitive Complexity **18** > 15 yang diizinkan |

### `app/Filament/Resources/MataPelajarans/MataPelajaranResource.php`

| # | Rule | Baris | Pesan |
|---|------|-------|-------|
| 7 | `php:S1192` | [L31](../app/Filament/Resources/MataPelajarans/MataPelajaranResource.php#L31) | String `"Mata Pelajaran"` diduplikasi 3 kali — buat konstanta |

### `app/Filament/Resources/MataPelajarans/Pages/CreateMataPelajaran.php`

| # | Rule | Baris | Pesan |
|---|------|-------|-------|
| 8 | `php:S3776` | [L24](../app/Filament/Resources/MataPelajarans/Pages/CreateMataPelajaran.php#L24) | Cognitive Complexity **29** > 15 yang diizinkan |

### `app/Filament/Resources/MataPelajarans/Schemas/MataPelajaranForm.php`

| # | Rule | Baris | Pesan |
|---|------|-------|-------|
| 9 | `php:S3776` | [L24](../app/Filament/Resources/MataPelajarans/Schemas/MataPelajaranForm.php#L24) | Cognitive Complexity **30** > 15 yang diizinkan |

### `app/Filament/Resources/TahunAjarans/TahunAjaranResource.php`

| # | Rule | Baris | Pesan |
|---|------|-------|-------|
| 10 | `php:S1192` | [L31](../app/Filament/Resources/TahunAjarans/TahunAjaranResource.php#L31) | String `"Tahun Ajaran"` diduplikasi 3 kali — buat konstanta |

### `app/Filament/Resources/Users/Pages/EditUser.php`

| # | Rule | Baris | Pesan |
|---|------|-------|-------|
| 11 | `php:S1192` | [L28](../app/Filament/Resources/Users/Pages/EditUser.php#L28) | String `"Tidak dapat menghapus user"` diduplikasi 3 kali — buat konstanta |

### `app/Filament/Resources/Users/Tables/UsersTable.php`

| # | Rule | Baris | Pesan |
|---|------|-------|-------|
| 12 | `php:S3776` | [L19](../app/Filament/Resources/Users/Tables/UsersTable.php#L19) | Cognitive Complexity **20** > 15 yang diizinkan |
| 13 | `php:S1192` | [L111](../app/Filament/Resources/Users/Tables/UsersTable.php#L111) | String `"Tidak dapat menghapus user"` diduplikasi 3 kali — buat konstanta |

### `app/Livewire/Student/Dashboard.php`

| # | Rule | Baris | Pesan |
|---|------|-------|-------|
| 14 | `php:S3776` | [L128](../app/Livewire/Student/Dashboard.php#L128) | Cognitive Complexity **18** > 15 yang diizinkan |

### `app/Livewire/Teacher/AktivitasPembelajaran/CreateAktivitas.php`

| # | Rule | Baris | Pesan |
|---|------|-------|-------|
| 15 | `php:S3776` | [L257](../app/Livewire/Teacher/AktivitasPembelajaran/CreateAktivitas.php#L257) | Cognitive Complexity **25** > 15 yang diizinkan |

### `app/Livewire/Teacher/AktivitasPembelajaran/EditAktivitas.php`

| # | Rule | Baris | Pesan |
|---|------|-------|-------|
| 16 | `php:S3776` | [L138](../app/Livewire/Teacher/AktivitasPembelajaran/EditAktivitas.php#L138) | Cognitive Complexity **24** > 15 yang diizinkan |

### `app/Livewire/Teacher/Laporan.php`

| # | Rule | Baris | Pesan |
|---|------|-------|-------|
| 17 | `php:S1192` | [L360](../app/Livewire/Teacher/Laporan.php#L360) | String `"Anda tidak memiliki akses ke kelas ini."` diduplikasi 3 kali — buat konstanta |
| 18 | `php:S1192` | [L376](../app/Livewire/Teacher/Laporan.php#L376) | String `"Data tidak lengkap."` diduplikasi 3 kali — buat konstanta |

### `config/database.php`

| # | Rule | Baris | Pesan |
|---|------|-------|-------|
| 19 | `php:S1192` | [L49](../config/database.php#L49) | String `"127.0.0.1"` diduplikasi 5 kali — buat konstanta |

---

## 🟠 MAJOR Issues

### `app/Exports/ClassReportExport.php`

> *(Catatan: `css:S4662` di bawah adalah false positive — `@source` adalah Tailwind CSS v4 syntax)*

| # | Rule | Baris | Pesan |
|---|------|-------|-------|
| 20 | `php:S1481` | [L198](../app/Exports/ClassReportExport.php#L198) | Variabel `$date` tidak digunakan — hapus |

### `app/Filament/Pages/Auth/Login.php`

| # | Rule | Baris | Pesan |
|---|------|-------|-------|
| 21 | `php:S1142` | [L110](../app/Filament/Pages/Auth/Login.php#L110) | Method ini punya **5 return**, maks yang diizinkan 3 |

### `app/Filament/Pages/ClassReport.php`

| # | Rule | Baris | Pesan |
|---|------|-------|-------|
| 22 | `php:S1142` | [L167](../app/Filament/Pages/ClassReport.php#L167) | Method ini punya **4 return**, maks yang diizinkan 3 |

### `app/Filament/Pages/KenaikanKelasPage.php`

| # | Rule | Baris | Pesan |
|---|------|-------|-------|
| 23 | `php:S1142` | [L214](../app/Filament/Pages/KenaikanKelasPage.php#L214) | Fungsi ini punya **4 return**, maks yang diizinkan 3 |

### `app/Filament/Resources/Kelas/KelasResource.php`

| # | Rule | Baris | Pesan |
|---|------|-------|-------|
| 24 | `php:S4144` | [L68](../app/Filament/Resources/Kelas/KelasResource.php#L68) | Method identik dengan `shouldRegisterNavigation` di L60 — gabungkan |

### `app/Filament/Resources/Kelas/Schemas/KelasForm.php`

| # | Rule | Baris | Pesan |
|---|------|-------|-------|
| 25 | `php:S1172` | [L90](../app/Filament/Resources/Kelas/Schemas/KelasForm.php#L90) | Parameter `$attribute` tidak digunakan — hapus atau ganti `$_` |

### `app/Filament/Resources/MataPelajarans/MataPelajaranResource.php`

| # | Rule | Baris | Pesan |
|---|------|-------|-------|
| 26 | `php:S4144` | [L63](../app/Filament/Resources/MataPelajarans/MataPelajaranResource.php#L63) | Method identik dengan `shouldRegisterNavigation` di L50 — gabungkan |

### `app/Filament/Resources/MataPelajarans/Schemas/MataPelajaranForm.php`

| # | Rule | Baris | Pesan |
|---|------|-------|-------|
| 27 | `php:S138` | [L24](../app/Filament/Resources/MataPelajarans/Schemas/MataPelajaranForm.php#L24) | Fungsi `configure` punya **190 baris** > 150 yang diizinkan — pecah jadi fungsi kecil |
| 28 | `php:S1172` | [L36](../app/Filament/Resources/MataPelajarans/Schemas/MataPelajaranForm.php#L36) | Parameter `$attribute` tidak digunakan |
| 29 | `php:S1142` | [L155](../app/Filament/Resources/MataPelajarans/Schemas/MataPelajaranForm.php#L155) | Fungsi ini punya **4 return**, maks yang diizinkan 3 |

### `app/Filament/Resources/Siswas/Schemas/SiswaForm.php`

| # | Rule | Baris | Pesan |
|---|------|-------|-------|
| 30 | `php:S1172` | [L73](../app/Filament/Resources/Siswas/Schemas/SiswaForm.php#L73) | Parameter `$get` tidak digunakan |

### `app/Filament/Resources/Siswas/SiswaResource.php`

| # | Rule | Baris | Pesan |
|---|------|-------|-------|
| 31 | `php:S4144` | [L51](../app/Filament/Resources/Siswas/SiswaResource.php#L51) | Method identik dengan `shouldRegisterNavigation` di L38 — gabungkan |

### `app/Filament/Resources/TahunAjarans/Tables/TahunAjaransTable.php`

| # | Rule | Baris | Pesan |
|---|------|-------|-------|
| 32 | `php:S138` | [L25](../app/Filament/Resources/TahunAjarans/Tables/TahunAjaransTable.php#L25) | Fungsi `configure` punya **158 baris** > 150 yang diizinkan |

### `app/Filament/Resources/TahunAjarans/TahunAjaranResource.php`

| # | Rule | Baris | Pesan |
|---|------|-------|-------|
| 33 | `php:S4144` | [L50](../app/Filament/Resources/TahunAjarans/TahunAjaranResource.php#L50) | Method identik dengan `shouldRegisterNavigation` di L42 |

### `app/Filament/Resources/Users/Pages/EditUser.php`

| # | Rule | Baris | Pesan |
|---|------|-------|-------|
| 34 | `php:S3626` | [L63](../app/Filament/Resources/Users/Pages/EditUser.php#L63) | Hapus redundant jump (return/continue tidak perlu) |

### `app/Filament/Resources/Users/Schemas/UserForm.php`

| # | Rule | Baris | Pesan |
|---|------|-------|-------|
| 35 | `php:S1172` | [L69](../app/Filament/Resources/Users/Schemas/UserForm.php#L69) | Parameter `$attribute` tidak digunakan |

### `app/Filament/Resources/Users/UserResource.php`

| # | Rule | Baris | Pesan |
|---|------|-------|-------|
| 36 | `php:S4144` | [L45](../app/Filament/Resources/Users/UserResource.php#L45) | Method identik dengan `shouldRegisterNavigation` di L32 |

### `app/Http/Middleware/EnsureUserIsAdmin.php`

| # | Rule | Baris | Pesan |
|---|------|-------|-------|
| 37 | `php:S1142` | [L20](../app/Http/Middleware/EnsureUserIsAdmin.php#L20) | Method ini punya **6 return**, maks yang diizinkan 3 |

### `app/Http/Responses/LoginResponse.php`

| # | Rule | Baris | Pesan |
|---|------|-------|-------|
| 38 | `php:S1142` | [L16](../app/Http/Responses/LoginResponse.php#L16) | Method ini punya **5 return**, maks yang diizinkan 3 |

### `app/Livewire/Student/Dashboard.php`

| # | Rule | Baris | Pesan |
|---|------|-------|-------|
| 39 | `php:S1871` | [L174](../app/Livewire/Student/Dashboard.php#L174) | Branch L174-177 identik dengan branch di L171 — gabungkan kondisi |

### `app/Livewire/Teacher/AktivitasPembelajaran/CreateAktivitas.php`

| # | Rule | Baris | Pesan |
|---|------|-------|-------|
| 40 | `php:S1142` | [L257](../app/Livewire/Teacher/AktivitasPembelajaran/CreateAktivitas.php#L257) | Method ini punya **4 return**, maks yang diizinkan 3 |
| 41 | `php:S3358` | [L315](../app/Livewire/Teacher/AktivitasPembelajaran/CreateAktivitas.php#L315) | Nested ternary — pisahkan ke statement terpisah |
| 42 | `php:S3358` | [L316](../app/Livewire/Teacher/AktivitasPembelajaran/CreateAktivitas.php#L316) | Nested ternary — pisahkan ke statement terpisah |

### `app/Livewire/Teacher/AktivitasPembelajaran/EditAktivitas.php`

| # | Rule | Baris | Pesan |
|---|------|-------|-------|
| 43 | `php:S3358` | [L180](../app/Livewire/Teacher/AktivitasPembelajaran/EditAktivitas.php#L180) | Nested ternary — pisahkan ke statement terpisah |
| 44 | `php:S3358` | [L181](../app/Livewire/Teacher/AktivitasPembelajaran/EditAktivitas.php#L181) | Nested ternary — pisahkan ke statement terpisah |

### `app/Livewire/Teacher/Laporan.php`

| # | Rule | Baris | Pesan |
|---|------|-------|-------|
| 45 | `php:S1448` | [L40](../app/Livewire/Teacher/Laporan.php#L40) | Class `Laporan` punya **22 method** > 20 yang diizinkan — pecah jadi service/trait |
| 46 | `php:S1488` | [L64](../app/Livewire/Teacher/Laporan.php#L64) | Langsung `return` ekspresi, jangan assign ke `$user` dulu |
| 47 | `php:S4144` | [L296](../app/Livewire/Teacher/Laporan.php#L296) | Method identik dengan `updatedReportType` di L286 |
| 48 | `php:S1142` | [L330](../app/Livewire/Teacher/Laporan.php#L330) | Method ini punya **4 return** |
| 49 | `php:S1142` | [L372](../app/Livewire/Teacher/Laporan.php#L372) | Method ini punya **4 return** |
| 50 | `php:S1142` | [L429](../app/Livewire/Teacher/Laporan.php#L429) | Method ini punya **5 return** |
| 51 | `php:S1142` | [L482](../app/Livewire/Teacher/Laporan.php#L482) | Method ini punya **5 return** |

### `app/Models/Siswa.php`

| # | Rule | Baris | Pesan |
|---|------|-------|-------|
| 52 | `php:S1142` | [L97](../app/Models/Siswa.php#L97) | Method ini punya **4 return** |

### `app/Models/User.php`

| # | Rule | Baris | Pesan |
|---|------|-------|-------|
| 53 | `php:S125` | [L7](../app/Models/User.php#L7) | Hapus kode yang di-comment |

### `app/Policies/DetailAktivitasPolicy.php`

| # | Rule | Baris | Pesan |
|---|------|-------|-------|
| 54 | `php:S1142` | [L39](../app/Policies/DetailAktivitasPolicy.php#L39) | Method ini punya **4 return** |
| 55 | `php:S4144` | [L101](../app/Policies/DetailAktivitasPolicy.php#L101) | Method identik dengan `update` di L78 — refactor |

### `app/Providers/AppServiceProvider.php`

| # | Rule | Baris | Pesan |
|---|------|-------|-------|
| 56 | `php:S1066` | [L32](../app/Providers/AppServiceProvider.php#L32) | Gabungkan `if` ini dengan `if` pembungkusnya |

### `database/migrations/2025_10_21_143714_create_permission_tables.php`

| # | Rule | Baris | Pesan |
|---|------|-------|-------|
| 57 | `php:S112` | [L129](../database/migrations/2025_10_21_143714_create_permission_tables.php#L129) | Gunakan dedicated exception, bukan generic `\Exception` |

### `database/seeders/DatabaseSeeder.php`

| # | Rule | Baris | Pesan |
|---|------|-------|-------|
| 58 | `php:S125` | [L8](../database/seeders/DatabaseSeeder.php#L8) | Hapus kode yang di-comment |

### `database/seeders/RolePermissionSeeder.php`

| # | Rule | Baris | Pesan |
|---|------|-------|-------|
| 59 | `php:S1192` | [L47](../database/seeders/RolePermissionSeeder.php#L47) | String `"view reports"` diduplikasi 3 kali — buat konstanta |

### `resources/css/app.css`

> ⚠️ **False positive** — `@source` adalah Tailwind CSS v4 syntax, bukan error nyata. Bisa di-mark sebagai *Won't Fix* di SonarQube.

| # | Rule | Baris | Pesan |
|---|------|-------|-------|
| 60 | `css:S4662` | L3 | Unknown at-rule `@source` |
| 61 | `css:S4662` | L4 | Unknown at-rule `@source` |
| 62 | `css:S4662` | L5 | Unknown at-rule `@source` |
| 63 | `css:S4662` | L6 | Unknown at-rule `@source` |
| 64 | `css:S4662` | L7 | Unknown at-rule `@source` |
| 65 | `css:S4662` | L8 | Unknown at-rule `@source` |
| 66 | `css:S4662` | L9 | Unknown at-rule `@source` |
| 67 | `css:S4662` | L10 | Unknown at-rule `@source` |
| 68 | `css:S4662` | L11 | Unknown at-rule `@source` |

### `resources/css/filament/admin/theme.css`

| # | Rule | Baris | Pesan |
|---|------|-------|-------|
| 69 | `css:S4662` | L3 | Unknown at-rule `@source` (Tailwind v4 — false positive) |
| 70 | `css:S4662` | L4 | Unknown at-rule `@source` (Tailwind v4 — false positive) |

### `resources/js/bootstrap.js`

| # | Rule | Baris | Pesan |
|---|------|-------|-------|
| 71 | `javascript:S6582` | [L12](../resources/js/bootstrap.js#L12) | Gunakan optional chain (`?.`) daripada chained `&&` |

### `resources/js/inactivity-timer.js`

| # | Rule | Baris | Pesan |
|---|------|-------|-------|
| 72 | `javascript:S7721` | [L121](../resources/js/inactivity-timer.js#L121) | Pindahkan fungsi `formatTime` ke outer scope |
| 73 | `javascript:S6582` | [L245](../resources/js/inactivity-timer.js#L245) | Gunakan optional chain (`?.`) |

### `resources/views/layouts/student.blade.php`

| # | Rule | Baris | Pesan |
|---|------|-------|-------|
| 74 | `Web:S5255` | [L32](../resources/views/layouts/student.blade.php#L32) | Tambahkan `aria-label` atau `aria-labelledby` pada elemen ini |
| 75 | `Web:S5255` | [L49](../resources/views/layouts/student.blade.php#L49) | Tambahkan `aria-label` atau `aria-labelledby` pada elemen ini |
| 76 | `Web:S5255` | [L107-L111](../resources/views/layouts/student.blade.php#L107) | Tambahkan `aria-label` atau `aria-labelledby` pada elemen ini |
| 77 | `Web:S5255` | [L129](../resources/views/layouts/student.blade.php#L129) | Tambahkan `aria-label` atau `aria-labelledby` pada elemen ini |

### `resources/views/layouts/teacher.blade.php`

| # | Rule | Baris | Pesan |
|---|------|-------|-------|
| 78 | `Web:S5255` | [L32](../resources/views/layouts/teacher.blade.php#L32) | Tambahkan `aria-label` atau `aria-labelledby` pada elemen ini |
| 79 | `Web:S5255` | [L49](../resources/views/layouts/teacher.blade.php#L49) | Tambahkan `aria-label` atau `aria-labelledby` pada elemen ini |
| 80 | `Web:S5255` | [L97-L101](../resources/views/layouts/teacher.blade.php#L97) | Tambahkan `aria-label` atau `aria-labelledby` pada elemen ini |
| 81 | `Web:S5255` | [L119](../resources/views/layouts/teacher.blade.php#L119) | Tambahkan `aria-label` atau `aria-labelledby` pada elemen ini |

### `resources/views/livewire/teacher/aktivitas-pembelajaran/create-aktivitas.blade.php`

| # | Rule | Baris | Pesan |
|---|------|-------|-------|
| 82 | `Web:S6853` | [L340](../resources/views/livewire/teacher/aktivitas-pembelajaran/create-aktivitas.blade.php#L340) | Form label harus terhubung ke control (tambahkan `for` atau `id`) |
| 83 | `Web:S6853` | [L352](../resources/views/livewire/teacher/aktivitas-pembelajaran/create-aktivitas.blade.php#L352) | Form label harus terhubung ke control |
| 84 | `Web:S6853` | [L368](../resources/views/livewire/teacher/aktivitas-pembelajaran/create-aktivitas.blade.php#L368) | Form label harus terhubung ke control |

### `resources/views/livewire/teacher/aktivitas-pembelajaran/edit-aktivitas.blade.php`

| # | Rule | Baris | Pesan |
|---|------|-------|-------|
| 85 | `Web:S6853` | [L212](../resources/views/livewire/teacher/aktivitas-pembelajaran/edit-aktivitas.blade.php#L212) | Form label harus terhubung ke control |
| 86 | `Web:S6853` | [L224](../resources/views/livewire/teacher/aktivitas-pembelajaran/edit-aktivitas.blade.php#L224) | Form label harus terhubung ke control |
| 87 | `Web:S6853` | [L240](../resources/views/livewire/teacher/aktivitas-pembelajaran/edit-aktivitas.blade.php#L240) | Form label harus terhubung ke control |

### `resources/views/reports/class-report.blade.php`

| # | Rule | Baris | Pesan |
|---|------|-------|-------|
| 88 | `Web:S5256` | [L310](../resources/views/reports/class-report.blade.php#L310) | Tambahkan `<th>` headers pada `<table>` ini |

### `resources/views/reports/student-report.blade.php`

| # | Rule | Baris | Pesan |
|---|------|-------|-------|
| 89 | `Web:S5256` | [L267](../resources/views/reports/student-report.blade.php#L267) | Tambahkan `<th>` headers pada `<table>` ini |

---

## 🟡 MINOR Issues

### `app/Exports/ClassReportExport.php`

| # | Rule | Baris | Pesan |
|---|------|-------|-------|
| 90 | `php:S1481` | [L198](../app/Exports/ClassReportExport.php#L198) | Variabel lokal `$date` tidak digunakan — hapus |

### `app/Filament/Pages/ClassReport.php`

| # | Rule | Baris | Pesan |
|---|------|-------|-------|
| 91 | `php:S116` | [L32](../app/Filament/Pages/ClassReport.php#L32) | Field `$kelas_id` tidak sesuai konvensi camelCase — ganti `$kelasId` |
| 92 | `php:S116` | [L34](../app/Filament/Pages/ClassReport.php#L34) | Field `$mata_pelajaran_id` → `$mataPelajaranId` |
| 93 | `php:S116` | [L36](../app/Filament/Pages/ClassReport.php#L36) | Field `$tahun_ajaran_id` → `$tahunAjaranId` |
| 94 | `php:S116` | [L38](../app/Filament/Pages/ClassReport.php#L38) | Field `$sort_by` → `$sortBy` |

### `app/Filament/Pages/StudentReport.php`

| # | Rule | Baris | Pesan |
|---|------|-------|-------|
| 95 | `php:S116` | [L26](../app/Filament/Pages/StudentReport.php#L26) | Field `$siswa_id` → `$siswaId` |
| 96 | `php:S116` | [L28](../app/Filament/Pages/StudentReport.php#L28) | Field `$tahun_ajaran_id` → `$tahunAjaranId` |

### `app/Filament/Resources/Users/Pages/EditUser.php`

| # | Rule | Baris | Pesan |
|---|------|-------|-------|
| 97 | `php:S3626` | [L63](../app/Filament/Resources/Users/Pages/EditUser.php#L63) | Hapus redundant jump |

### `app/Livewire/Teacher/AktivitasPembelajaran/CreateAktivitas.php`

| # | Rule | Baris | Pesan |
|---|------|-------|-------|
| 98 | `php:S116` | [L33](../app/Livewire/Teacher/AktivitasPembelajaran/CreateAktivitas.php#L33) | Field `$tingkat_kelas` → `$tingkatKelas` |
| 99 | `php:S116` | [L35](../app/Livewire/Teacher/AktivitasPembelajaran/CreateAktivitas.php#L35) | Field `$grup_kelas` → `$grupKelas` |
| 100 | `php:S116` | [L37](../app/Livewire/Teacher/AktivitasPembelajaran/CreateAktivitas.php#L37) | Field `$mata_pelajaran_id` → `$mataPelajaranId` |
| 101 | `php:S116` | [L44](../app/Livewire/Teacher/AktivitasPembelajaran/CreateAktivitas.php#L44) | Field `$kelas_id` → `$kelasId` |
| 102 | `php:S3626` | [L80](../app/Livewire/Teacher/AktivitasPembelajaran/CreateAktivitas.php#L80) | Hapus redundant jump |

### `app/Livewire/Teacher/AktivitasPembelajaran/EditAktivitas.php`

| # | Rule | Baris | Pesan |
|---|------|-------|-------|
| 103 | `php:S116` | [L30](../app/Livewire/Teacher/AktivitasPembelajaran/EditAktivitas.php#L30) | Field `$mata_pelajaran_id` → `$mataPelajaranId` |
| 104 | `php:S116` | [L36](../app/Livewire/Teacher/AktivitasPembelajaran/EditAktivitas.php#L36) | Field `$kelas_id` → `$kelasId` |

### `app/Livewire/Teacher/Laporan.php`

| # | Rule | Baris | Pesan |
|---|------|-------|-------|
| 105 | `php:S1488` | [L64](../app/Livewire/Teacher/Laporan.php#L64) | Langsung `return` ekspresi, jangan assign `$user` dulu |

### `resources/js/bootstrap.js`

| # | Rule | Baris | Pesan |
|---|------|-------|-------|
| 106 | `javascript:S7764` | [L2](../resources/js/bootstrap.js#L2) | Gunakan `globalThis` bukan `window` |
| 107 | `javascript:S7764` | [L4](../resources/js/bootstrap.js#L4) | Gunakan `globalThis` bukan `window` |
| 108 | `javascript:S7764` | [L12](../resources/js/bootstrap.js#L12) | Gunakan `globalThis` bukan `window` (2 kemunculan) |
| 109 | `javascript:S7764` | [L13](../resources/js/bootstrap.js#L13) | Gunakan `globalThis` bukan `window` |

### `resources/js/inactivity-timer.js`

| # | Rule | Baris | Pesan |
|---|------|-------|-------|
| 110 | `javascript:S7764` | [L326](../resources/js/inactivity-timer.js#L326) | Gunakan `globalThis` bukan `window` |
| 111 | `javascript:S7764` | [L332](../resources/js/inactivity-timer.js#L332) | Gunakan `globalThis` bukan `window` |
| 112 | `javascript:S7764` | [L389](../resources/js/inactivity-timer.js#L389) | Gunakan `globalThis` bukan `window` |
| 113 | `javascript:S7773` | [L384](../resources/js/inactivity-timer.js#L384) | Gunakan `Number.parseInt` bukan `parseInt` |
| 114 | `javascript:S7773` | [L385](../resources/js/inactivity-timer.js#L385) | Gunakan `Number.parseInt` bukan `parseInt` |

---

## Catatan Perbaikan

### Yang Bisa Langsung Diabaikan / Mark Won't Fix

- **`css:S4662`** di `app.css` dan `theme.css` — `@source` adalah valid Tailwind CSS v4 syntax, bukan error nyata.

### Prioritas Perbaikan

1. **CRITICAL Cognitive Complexity** (`php:S3776`) — Pecah method-method besar jadi method helper private kecil-kecil.
2. **MAJOR Terlalu banyak return** (`php:S1142`) — Refactor dengan early return pattern atau extract method.
3. **CRITICAL String duplikat** (`php:S1192`) — Definisikan sebagai `const` di class masing-masing.
4. **MAJOR Identical method** (`php:S4144`) — Pada Filament Resource (`canAccess` vs `shouldRegisterNavigation`), hapus salah satu atau jadikan satu.
5. **MINOR Naming convention** (`php:S116`) — Rename Livewire properties dengan snake_case ke camelCase, update semua referensi di view.

> ⚠️ Untuk `php:S116` di Livewire, perubahan nama property akan mempengaruhi binding `wire:model` di Blade file — pastikan rename secara bersamaan.
