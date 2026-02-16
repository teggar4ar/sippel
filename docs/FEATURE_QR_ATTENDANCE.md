# Rencana Implementasi: Absensi Mandiri Berbasis QR Code Siswa

**Versi:** 1.1  
**Tanggal:** 10 Februari 2026  
**Status:** Planning  
**Prioritas:** Should Have (Enhancement terhadap sistem absensi manual yang sudah berjalan)

---

## Daftar Isi

1. [Gambaran Konsep & Rasional](#1-gambaran-konsep--rasional)
2. [Alur Sistem (System Flow)](#2-alur-sistem-system-flow)
3. [Business Rules](#3-business-rules)
4. [Desain Perubahan Database](#4-desain-perubahan-database)
5. [Desain Endpoint & Controller](#5-desain-endpoint--controller)
6. [Strategi Keamanan](#6-strategi-keamanan)
7. [Pertimbangan UX](#7-pertimbangan-ux)
8. [Risiko & Keterbatasan](#8-risiko--keterbatasan)
9. [Roadmap Implementasi](#9-roadmap-implementasi)

---

## 1. Gambaran Konsep & Rasional

### 1.1 Latar Belakang

Sistem SIPPEL saat ini menggunakan metode absensi manual: guru membuka aktivitas pembelajaran, lalu mengisi kehadiran tiap siswa satu per satu melalui dropdown (`hadir`, `izin`, `sakit`, `alpa`). Metode ini berfungsi baik tetapi memiliki beberapa kelemahan:

- **Memakan waktu** — guru harus mengisi kehadiran untuk seluruh siswa secara manual
- **Rawan human error** — salah pilih status pada daftar siswa yang panjang
- **Tidak melibatkan siswa** — siswa pasif dalam proses absensi

### 1.2 Solusi: QR Code Pribadi Siswa

Setiap siswa memiliki **QR code statis** yang dicetak dan dibawa secara fisik (kartu/stiker). QR code ini berfungsi sebagai **identitas digital** yang di-scan oleh siswa sendiri melalui perangkat mereka saat sesi absensi dibuka oleh guru.

### 1.3 Mengapa Pendekatan Ini?

| Aspek | Keputusan | Alasan |
|-------|-----------|--------|
| **QR statis vs dinamis** | Statis (sekali cetak) | Praktis untuk lingkungan SMP; tidak perlu infrastruktur tambahan untuk generate QR baru setiap sesi |
| **Self-scan vs guru-scan** | Siswa scan sendiri | Mengurangi beban guru; siswa aktif bertanggung jawab atas kehadirannya |
| **Validasi utama** | Login + sesi aktif + HMAC | QR hanya identitas; keamanan utama ada di sisi server (sesi terbatas waktu, login wajib, HMAC signature) |
| **Hubungan dengan sistem lama** | Berdampingan (dual-mode) | Guru tetap bisa absensi manual untuk siswa yang tidak bisa scan (tidak punya HP, QR rusak, dll.) |

### 1.4 Integrasi dengan Arsitektur Existing

Fitur ini **tidak mengganti** alur absensi manual yang sudah ada. QR attendance menjadi **metode alternatif** yang terintegrasi ke dalam tabel `detail_aktivitas` yang sudah berjalan:

```
                 ┌──────────────────────┐
                 │ AktivitasPembelajaran │
                 └──────────┬───────────┘
                            │
              ┌─────────────┴─────────────┐
              │                           │
     ┌────────▼────────┐       ┌──────────▼──────────┐
     │  Manual Entry   │       │   QR Self-Scan       │
     │  (by Teacher)   │       │   (by Student)       │
     └────────┬────────┘       └──────────┬───────────┘
              │                           │
              └─────────────┬─────────────┘
                            │
                   ┌────────▼────────┐
                   │ detail_aktivitas │
                   │ kehadiran: hadir │
                   └─────────────────┘
```

---

## 2. Alur Sistem (System Flow)

### 2.1 Alur Persiapan (Satu Kali)

```
[Admin/Operator]
    │
    ├─ 1. Admin mendaftarkan siswa (sudah ada di SIPPEL)
    │
    ├─ 2. Admin generate QR code per siswa via panel Filament
    │     └─ Isi QR: "{student_id}:{hmac_signature}"
    │
    ├─ 3. Admin cetak kartu QR (per siswa atau batch per kelas)
    │     └─ Output: gambar PNG per siswa atau PDF batch per kelas
    │
    └─ 4. Kartu QR dibagikan ke siswa (fisik)
```

> **Catatan**: Guru **tidak** memiliki akses cetak/generate QR. Hanya admin/operator yang mengelola kartu QR siswa.

### 2.2 Alur Absensi Harian

```
[Guru — Wizard Create Aktivitas]              [Siswa]
  │                                              │
  ├─ 1. Step 1: Isi info aktivitas               │
  │    (tanggal, kelas, mapel, topik)            │
  │                                              │
  ├─ 2. Step 2: Halaman Absensi & Nilai          │
  │    ├─ Toggle "Absensi Mandiri (QR)" [ON/OFF] │
  │    │   └─ Jika ON:                            │
  │    │       ├─ Pilih durasi: 5/10/15 menit     │
  │    │       └─ Dropdown kehadiran manual        │
  │    │         menjadi OPSIONAL (boleh kosong)   │
  │    │   └─ Jika OFF:                            │
  │    │       └─ Dropdown kehadiran WAJIB diisi   │
  │    │         (logika existing)                  │
  │    │                                           │
  │    └─ Guru klik [Simpan]                       │
  │        └─ Jika toggle ON:                      │
  │            ├─ Aktivitas tersimpan               │
  │            ├─ Sesi absensi QR otomatis dibuka   │
  │            └─ Countdown timer mulai berjalan    │
  │                                                │
  │  ┌──────── SESI AKTIF ─────────┐               │
  │  │                             │               │
  │  │  Timer visible di:          ├─ 3. Siswa login ke /student
  │  │  • Kartu aktivitas (list)   │
  │  │  • Detail aktivitas (/id)   ├─ 4. Buka menu Absensi
  │  │                             │    └─ Kamera terbuka
  │  │                             │
  │  │                             ├─ 5. Scan QR code pribadi
  │  │                             │
  │  │  ┌──────────────────────────────────┐
  │  │  │        SERVER VALIDASI            │
  │  │  ├──────────────────────────────────┤
  │  │  │ ✓ Ada sesi absensi aktif?        │
  │  │  │ ✓ Waktu masih dalam batas?       │
  │  │  │ ✓ HMAC signature valid?          │
  │  │  │ ✓ student_id = user login?       │
  │  │  │ ✓ Siswa di kelas yang benar?     │
  │  │  │ ✓ Belum absen sebelumnya?        │
  │  │  └──────────────────────────────────┘
  │  │                             │
  │  │                             ├─ 6. Feedback: "Berhasil" / "Gagal"
  │  │                             │
  │  └──── SESI BERAKHIR ──────────┘
  │                                                │
  ├─ 7. Guru buka halaman detail/edit aktivitas     │
  │    └─ Detail: lihat status scan per siswa       │
  │    └─ Edit: tombol [Tutup Sesi] tersedia        │
  │    └─ Edit: override kehadiran manual           │
  │                                                 │
  └─ 8. Finalisasi nilai/partisipasi                │
```

### 2.3 Alur Penutupan Sesi

```
Sesi berakhir (otomatis atau manual)
    │
    ├─ Siswa yang sudah scan → kehadiran = 'hadir' (jika belum di-set)
    ├─ Siswa yang belum scan → tetap default ('alpa')
    ├─ Guru masih bisa edit kehadiran secara manual setelah sesi ditutup
    └─ Status sesi berubah menjadi 'closed'
```

---

## 3. Business Rules

### 3.1 QR Code

| No | Rule | Deskripsi |
|----|------|-----------|
| BR-01 | **QR unik per siswa** | Setiap siswa memiliki tepat satu QR code aktif yang berisi `siswa_id` dan HMAC signature |
| BR-02 | **QR statis** | QR tidak berubah kecuali di-regenerate secara eksplisit oleh admin (misalnya saat QR hilang/rusak) |
| BR-03 | **QR bukan satu-satunya identitas** | QR hanya data tambahan; validasi utama tetap melalui login session siswa |
| BR-04 | **Regenerasi QR** | Hanya admin/operator yang dapat regenerate QR siswa; QR lama otomatis invalid (signature key berubah) |

### 3.2 Sesi Absensi

| No | Rule | Deskripsi |
|----|------|-----------|
| BR-05 | **Satu sesi per aktivitas** | Setiap `aktivitas_pembelajaran` hanya boleh memiliki satu sesi absensi aktif pada satu waktu |
| BR-06 | **Durasi terbatas** | Sesi memiliki durasi tetap (default 5 menit, opsi: 5/10/15 menit) yang ditentukan guru di wizard step 2 saat membuat aktivitas |
| BR-07 | **Auto-close** | Sesi otomatis berstatus `closed` setelah waktu habis |
| BR-08 | **Manual close** | Guru dapat menutup sesi secara manual melalui halaman **edit** aktivitas (`/teacher/aktivitas/{id}/edit`) |
| BR-09 | **Buka ulang** | Guru dapat membuka sesi baru melalui halaman edit jika sesi sebelumnya sudah ditutup (untuk siswa yang terlambat) |
| BR-09a | **Sesi dimulai saat simpan** | Sesi absensi QR otomatis aktif ketika guru menyimpan aktivitas pembelajaran (klik Simpan di wizard step 2), bukan melalui tombol terpisah |

### 3.3 Validasi Scan

| No | Rule | Deskripsi |
|----|------|-----------|
| BR-10 | **Login wajib** | Siswa harus dalam keadaan login untuk melakukan scan |
| BR-11 | **Self-scan only** | `siswa_id` di QR harus sama dengan siswa yang sedang login |
| BR-12 | **Sesi harus aktif** | Scan ditolak jika tidak ada sesi absensi yang sedang berjalan |
| BR-13 | **Validasi kelas** | Siswa harus terdaftar di kelas yang sama dengan aktivitas pembelajaran |
| BR-14 | **Sekali scan** | Siswa hanya bisa scan sekali per sesi; scan ulang ditolak dengan pesan informatif |
| BR-15 | **HMAC valid** | Signature di QR harus cocok dengan kalkulasi server |

### 3.4 Integrasi dengan Absensi Manual

| No | Rule | Deskripsi |
|----|------|-----------|
| BR-16 | **Dual-mode** | Guru tetap bisa mengubah status kehadiran secara manual kapan saja, terlepas dari status QR scan |
| BR-17 | **Override manual** | Perubahan manual oleh guru di-prioritaskan di atas hasil QR scan |
| BR-18 | **Default alpa** | Siswa yang tidak scan dan tidak diubah manual tetap berstatus `alpa` (sesuai default existing) |

### 3.5 Validasi Penyimpanan Aktivitas (Wizard Step 2)

| No | Rule | Deskripsi |
|----|------|-----------|
| BR-19 | **Absensi mandiri ON → kehadiran opsional** | Jika toggle absensi mandiri diaktifkan, guru dapat menyimpan aktivitas **tanpa** mengisi dropdown kehadiran manual per siswa. Kehadiran akan diisi oleh siswa melalui self-scan QR. |
| BR-20 | **Absensi mandiri OFF → kehadiran wajib** | Jika toggle absensi mandiri tidak aktif (mode konvensional), guru **wajib** mengisi dropdown kehadiran untuk seluruh siswa sebelum menyimpan. Ini adalah logika validasi existing yang sudah berjalan. |
| BR-21 | **Hybrid diperbolehkan** | Saat toggle ON, guru tetap boleh mengisi kehadiran manual sebagian siswa. Siswa yang sudah diisi manual tidak akan di-override oleh QR scan, kecuali guru mengubahnya kembali. |

---

## 4. Desain Perubahan Database

### 4.1 Tabel Baru: `sesi_absensi`

Tabel ini menyimpan informasi sesi absensi QR yang dibuka oleh guru.

```
┌──────────────────────────────────────────────────────────────────┐
│                        sesi_absensi                              │
├──────────────────────────────────────────────────────────────────┤
│ id                    BIGINT PK AUTO_INCREMENT                   │
│ aktivitas_pembelajaran_id  BIGINT FK → aktivitas_pembelajaran    │
│ status                ENUM('open', 'closed') DEFAULT 'open'      │
│ durasi_menit          TINYINT UNSIGNED (5, 10, 15)               │
│ dibuka_pada           TIMESTAMP (waktu sesi dibuka)              │
│ ditutup_pada          TIMESTAMP NULLABLE (waktu sesi ditutup)    │
│ created_at            TIMESTAMP                                  │
│ updated_at            TIMESTAMP                                  │
├──────────────────────────────────────────────────────────────────┤
│ INDEX: aktivitas_pembelajaran_id                                 │
│ INDEX: status                                                    │
│ INDEX: (aktivitas_pembelajaran_id, status)                       │
└──────────────────────────────────────────────────────────────────┘
```

### 4.2 Tabel Baru: `log_scan_absensi`

Tabel audit log yang mencatat setiap percobaan scan (berhasil maupun gagal).

```
┌──────────────────────────────────────────────────────────────────┐
│                      log_scan_absensi                            │
├──────────────────────────────────────────────────────────────────┤
│ id                    BIGINT PK AUTO_INCREMENT                   │
│ sesi_absensi_id       BIGINT FK → sesi_absensi                   │
│ siswa_id              BIGINT FK → siswa                          │
│ status_scan           ENUM('berhasil', 'gagal') DEFAULT 'gagal'  │
│ alasan_gagal          VARCHAR(100) NULLABLE                      │
│                       (misal: 'sesi_expired', 'sudah_absen',     │
│                        'kelas_salah', 'signature_invalid',       │
│                        'bukan_pemilik_qr')                       │
│ ip_address            VARCHAR(45) NULLABLE                       │
│ user_agent            VARCHAR(255) NULLABLE                      │
│ waktu_scan            TIMESTAMP                                  │
│ created_at            TIMESTAMP                                  │
│ updated_at            TIMESTAMP                                  │
├──────────────────────────────────────────────────────────────────┤
│ INDEX: sesi_absensi_id                                           │
│ INDEX: siswa_id                                                  │
│ INDEX: (sesi_absensi_id, siswa_id)                               │
│ INDEX: status_scan                                               │
└──────────────────────────────────────────────────────────────────┘
```

### 4.3 Perubahan pada Tabel Existing: `detail_aktivitas`

Tambahkan kolom opsional untuk menandai metode absensi:

```
ALTER TABLE detail_aktivitas ADD COLUMN:
│ metode_kehadiran      ENUM('manual', 'qr_scan') DEFAULT 'manual' │
│ waktu_kehadiran       TIMESTAMP NULLABLE                         │
```

Kolom ini bersifat informatif untuk tracking, **tidak mengubah logika existing**.

### 4.4 Perubahan pada Tabel Existing: `siswa`

Tambahkan kolom untuk menyimpan secret key per siswa (untuk HMAC):

```
ALTER TABLE siswa ADD COLUMN:
│ qr_secret             VARCHAR(64) NULLABLE                       │
│ qr_generated_at       TIMESTAMP NULLABLE                         │
```

> **Catatan**: `qr_secret` adalah random string yang di-generate saat QR dibuat. Digunakan untuk kalkulasi HMAC. Jika admin regenerate QR, `qr_secret` diganti → QR lama otomatis invalid.

### 4.5 Perubahan pada Tabel Existing: `aktivitas_pembelajaran`

Tambahkan kolom untuk menandai mode absensi pada aktivitas:

```
ALTER TABLE aktivitas_pembelajaran ADD COLUMN:
│ absensi_mandiri        BOOLEAN DEFAULT FALSE                     │
│ durasi_absensi_menit   TINYINT UNSIGNED NULLABLE (5, 10, 15)     │
```

- `absensi_mandiri`: Menyimpan pilihan guru saat create aktivitas (toggle ON/OFF di wizard step 2)
- `durasi_absensi_menit`: Durasi sesi QR, hanya terisi jika `absensi_mandiri = true`

### 4.6 Entity Relationship (Perubahan)

```
aktivitas_pembelajaran (+ absensi_mandiri, durasi_absensi_menit)
    │
    ├─── 1:N ─── detail_aktivitas (existing, + metode_kehadiran, waktu_kehadiran)
    │
    └─── 1:N ─── sesi_absensi (BARU)
                     │
                     └─── 1:N ─── log_scan_absensi (BARU)

siswa (+ qr_secret, qr_generated_at)
    │
    ├─── 1:N ─── detail_aktivitas (existing)
    └─── 1:N ─── log_scan_absensi (BARU)
```

---

## 5. Desain Endpoint & Controller

### 5.1 Arsitektur Komponen

Fitur ini menggunakan **Livewire components** yang terintegrasi ke panel Teacher dan Student yang sudah ada, bukan API REST terpisah.

### 5.2 Teacher-Side (Modifikasi Komponen Existing)

Fitur QR attendance terintegrasi ke dalam komponen Livewire yang **sudah ada**, bukan komponen baru terpisah.

#### a) `CreateAktivitas` (modifikasi wizard step 2)

Tambahan di step 2 (di atas daftar absensi manual):

| Elemen | Deskripsi |
|--------|-----------|
| Toggle "Absensi Mandiri (QR)" | Switch ON/OFF untuk mengaktifkan mode self-scan |
| Dropdown durasi | Pilihan 5/10/15 menit, muncul jika toggle ON |

**Perubahan logika `save()`:**

```
save()
    → Jika toggle absensi mandiri OFF:
        → Validasi kehadiran wajib diisi semua (logika existing)
    → Jika toggle absensi mandiri ON:
        → Validasi kehadiran menjadi nullable (boleh kosong)
        → Simpan aktivitas dalam DB transaction
        → Otomatis buat record sesi_absensi (status: open)
        → Set dibuka_pada = now(), durasi_menit = pilihan guru
    → Redirect ke halaman list aktivitas
```

#### b) `EditAktivitas` (tombol tutup sesi)

Tambahan di halaman edit:

| Elemen | Deskripsi |
|--------|-----------|
| Info sesi aktif | Menampilkan status sesi + countdown timer jika sesi masih open |
| Tombol "Tutup Sesi Absensi" | Menutup sesi secara manual sebelum waktu habis |
| Tombol "Buka Sesi Baru" | Membuat sesi baru jika sesi sebelumnya sudah ditutup |

**Actions:**

```
tutupSession(int $sesiId)
    → Validasi: guru pemilik sesi
    → Update status → closed, set ditutup_pada
    → Sisa siswa yang belum scan tetap alpa

bukaSesiBaru(int $aktivitasId, int $durasiMenit)
    → Validasi: tidak ada sesi aktif
    → Buat record sesi_absensi baru (status: open)
```

#### c) `ListAktivitas` (countdown timer di kartu aktivitas)

Tambahan di kartu aktivitas pada halaman list:

| Elemen | Deskripsi |
|--------|-----------|
| Badge countdown timer | Tampil pada kartu aktivitas yang memiliki sesi QR aktif, di dekat tombol CRUD |
| Indikator "QR Aktif" | Badge kecil yang menunjukkan sesi sedang berjalan |

#### d) `ViewAktivitas` (status scan per siswa)

Tambahan di halaman detail aktivitas (`/teacher/aktivitas/{id}`):

| Elemen | Deskripsi |
|--------|-----------|
| Countdown timer | Tampil jelas jika sesi QR masih aktif |
| Status scan per siswa | Deskripsi sederhana di kartu tiap siswa: "Hadir via QR (09:02)" atau "Belum scan" |
| Ringkasan scan | Counter: "Hadir via QR: 15/32" |

### 5.3 Student-Side (Livewire Components)

| Komponen | Route | Fungsi |
|----------|-------|--------|
| `ScanAbsensi` | `GET /student/absensi/scan` | Halaman scan QR dengan akses kamera |

**Actions pada komponen `ScanAbsensi`:**

```
prosesQrCode(string $qrData)
    → Parse QR: extract siswa_id dan signature
    → Validasi (lihat BR-10 sampai BR-15)
    → Jika valid:
        → Update detail_aktivitas.kehadiran = 'hadir'
        → Set detail_aktivitas.metode_kehadiran = 'qr_scan'
        → Set detail_aktivitas.waktu_kehadiran = now()
        → Buat log_scan_absensi (status: berhasil)
    → Jika gagal:
        → Buat log_scan_absensi (status: gagal, alasan)
        → Return pesan error yang informatif
```

### 5.4 QR Code Management (Admin/Operator Only)

Akses cetak dan generate QR **hanya tersedia di panel admin** (FilamentPHP). Guru tidak memiliki akses ke fitur ini.

| Komponen / Route | Panel | Fungsi |
|------------------|-------|--------|
| Filament Action di SiswaResource | Admin (`/app`) | Generate QR per siswa (tombol action di tabel) |
| Filament Bulk Action di SiswaResource | Admin (`/app`) | Generate/regenerate QR untuk siswa terpilih |
| Filament Action "Cetak QR Kelas" di KelasResource | Admin (`/app`) | Download batch QR PDF untuk satu kelas |

**Logic generate QR:**

```
generateQr(Siswa $siswa)
    → Generate random qr_secret (32 bytes, hex)
    → Simpan ke siswa.qr_secret
    → Set siswa.qr_generated_at = now()
    → Data QR = "{siswa_id}:{hmac_sha256(siswa_id, qr_secret)}"
    → Render QR image menggunakan endroid/qr-code
    → Return PNG/SVG
```

### 5.5 Service Class

Buat satu **service class** untuk enkapsulasi logika bisnis:

```
App\Services\QrAttendanceService
    │
    ├─ generateQrData(Siswa $siswa): string
    ├─ generateQrImage(Siswa $siswa): string (data URI)
    ├─ generateBatchPdf(Kelas $kelas): string (PDF path)
    ├─ validateQrScan(string $qrData, User $loggedInUser): ValidationResult
    ├─ openSession(AktivitasPembelajaran $aktivitas, int $durasiMenit): SesiAbsensi
    ├─ closeSession(SesiAbsensi $sesi): void
    ├─ recordAttendance(SesiAbsensi $sesi, Siswa $siswa): void
    └─ isSessionActive(SesiAbsensi $sesi): bool
```

---

## 6. Strategi Keamanan

### 6.1 HMAC Signature pada QR

**Format data QR:**

```
{siswa_id}:{hmac_sha256_hex}
```

**Contoh:**

```
42:a1b2c3d4e5f6...  (64 karakter hex)
```

**Proses generate signature:**

```php
$signature = hash_hmac('sha256', (string) $siswa->id, $siswa->qr_secret);
$qrData = $siswa->id . ':' . $signature;
```

**Proses validasi:**

```php
[$siswaId, $signature] = explode(':', $qrData, 2);
$siswa = Siswa::find($siswaId);
$expectedSignature = hash_hmac('sha256', (string) $siswaId, $siswa->qr_secret);
$valid = hash_equals($expectedSignature, $signature);
```

### 6.2 Mengapa HMAC per-siswa secret?

- **Bukan app-wide secret**: Jika menggunakan satu secret untuk semua, bocornya satu secret = semua QR bisa di-forge. Dengan per-siswa secret, dampak kebocoran terisolasi.
- **Regenerasi mudah**: Ganti `qr_secret` satu siswa saja jika QR hilang/disalahgunakan, tanpa mempengaruhi siswa lain.
- **Timing-safe comparison**: Gunakan `hash_equals()` untuk mencegah timing attack.

### 6.3 Lapisan Keamanan Berlapis

```
Layer 1: Authentication
    └─ Siswa HARUS login (middleware auth + role:student)

Layer 2: Session Validity
    └─ Sesi absensi harus berstatus 'open'
    └─ Waktu scan harus dalam rentang durasi sesi

Layer 3: Identity Matching
    └─ siswa_id di QR HARUS = siswa_id dari user yang login
    └─ Mencegah siswa scan QR teman (titip absen)

Layer 4: Signature Validation
    └─ HMAC signature di QR harus cocok dengan kalkulasi server
    └─ Mencegah pembuatan QR palsu

Layer 5: Class Membership
    └─ Siswa harus terdaftar di kelas aktivitas tersebut

Layer 6: Duplicate Prevention
    └─ Satu scan per siswa per sesi
    └─ Check di log_scan_absensi

Layer 7: Audit Trail
    └─ Semua percobaan scan (berhasil/gagal) tercatat di log
    └─ Termasuk IP address dan user agent
```

### 6.4 Mitigasi Penyalahgunaan

| Ancaman | Mitigasi |
|---------|----------|
| **Titip absen** (scan QR teman) | Self-scan: QR siswa_id harus = user login. Siswa tidak bisa login ke akun orang lain. |
| **QR palsu/fotokopi** | HMAC signature mencegah pembuatan QR tanpa secret. Walaupun QR difotokopi, Layer 3 (identity matching) tetap mengharuskan login sebagai siswa yang benar. |
| **Scan di luar kelas** | Validasi kelas (BR-13) memastikan siswa terdaftar di kelas yang sama. Sesi waktu terbatas mengurangi window penyalahgunaan. |
| **Brute force signature** | HMAC SHA-256 dengan secret 32 bytes tidak feasible untuk brute force. Rate limiting pada endpoint scan. |
| **Replay attack** | Tidak relevan karena QR statis + validasi login session. QR tanpa login tidak berguna. |
| **QR hilang/rusak** | Admin regenerate QR → secret baru → QR lama invalid |

### 6.5 Rate Limiting

Terapkan rate limiting pada action scan:

```php
// Maksimal 10 percobaan scan per menit per user
RateLimiter::for('qr-scan', function (Request $request) {
    return Limit::perMinute(10)->by($request->user()->id);
});
```

---

## 7. Pertimbangan UX

### 7.1 Perspektif Guru

#### Mengaktifkan Absensi Mandiri (Wizard Step 2)

- **Lokasi**: Toggle di bagian atas wizard step 2 (Create Aktivitas), di atas daftar absensi manual
- **Interaksi**: Toggle ON → muncul dropdown pilih durasi (5/10/15 menit) → dropdown kehadiran manual menjadi opsional
- **Default**: Toggle OFF (mode konvensional, kehadiran manual wajib)
- **Saat simpan**: Jika toggle ON, sesi absensi QR langsung aktif setelah klik Simpan

```
┌─────────────────────────────────────────────────┐
│  Step 2: Absensi & Nilai                        │
│                                                 │
│  Ringkasan: 7A │ Matematika │ 10 Feb 2026       │
│                                                 │
│  ┌─────────────────────────────────────────┐    │
│  │ 📱 Absensi Mandiri (QR)    [====ON====] │    │
│  │ Durasi sesi: [5 menit ▼]               │    │
│  │                                         │    │
│  │ ℹ Siswa akan scan QR sendiri setelah    │    │
│  │   aktivitas disimpan. Kehadiran manual  │    │
│  │   di bawah menjadi opsional.            │    │
│  └─────────────────────────────────────────┘    │
│                                                 │
│  [Semua Hadir] Belum diisi: 32                  │
│  ┌─────────────────────────────────────────┐    │
│  │ Ahmad Fadli       [H] [I] [S] [A]      │    │
│  │ ... (kehadiran opsional jika QR ON)     │    │
│  └─────────────────────────────────────────┘    │
│                                                 │
│  [← Kembali]                       [Simpan]    │
└─────────────────────────────────────────────────┘
```

#### Countdown Timer di Kartu Aktivitas (List Page)

- **Lokasi**: Badge pada kartu aktivitas di halaman list (`/teacher/aktivitas`), di dekat tombol CRUD
- **Visual**: Badge berwarna dengan countdown timer "⏱ 03:47" yang update periodik
- **Saat sesi berakhir**: Badge berubah menjadi "QR Selesai" atau hilang

```
┌─────────────────────────────────────────────────┐
│  Matematika — Kelas 7A               ⏱ 03:47   │
│  10 Feb 2026 │ Aljabar Dasar                    │
│                           [👁] [✏️] [🗑]        │
└─────────────────────────────────────────────────┘
```

#### Status Scan di Halaman Detail Aktivitas (View Page)

- **Lokasi**: Halaman detail aktivitas (`/teacher/aktivitas/{id}`)
- **Komponen**: Countdown timer (jika sesi masih aktif) + deskripsi sederhana per siswa
- **Tidak perlu realtime monitoring** — cukup informasi statis yang di-refresh saat halaman dimuat

```
┌─────────────────────────────────────────────────┐
│  Detail Aktivitas — Matematika Kelas 7A         │
│  10 Feb 2026 │ Topik: Aljabar Dasar             │
│                                                 │
│  ⏱ Sesi Absensi QR — Sisa: 03:47               │
│  Hadir via QR: 15/32                            │
├─────────────────────────────────────────────────┤
│  Ahmad Fadli      Hadir (QR 09:01)              │
│  Budi Santoso     Hadir (QR 09:01)              │
│  Citra Dewi       Belum scan                    │
│  Dimas Pratama    Hadir (Manual)                │
│  Eka Putri        Hadir (QR 09:02)              │
│  ...                                            │
└─────────────────────────────────────────────────┘
```

#### Menutup Sesi & Override Manual (Edit Page)

- **Lokasi**: Halaman edit aktivitas (`/teacher/aktivitas/{id}/edit`)
- **Tombol "Tutup Sesi"**: Tersedia jika sesi masih aktif
- **Tombol "Buka Sesi Baru"**: Tersedia jika sesi sudah ditutup (untuk siswa terlambat)
- **Override manual**: Guru tetap bisa mengubah dropdown kehadiran per siswa

#### Generate & Cetak QR (Admin Only)

Guru **tidak memiliki akses** cetak QR. Fitur ini hanya tersedia di panel admin.

- **Lokasi**: Panel Filament (`/app`) — SiswaResource dan KelasResource
- **Opsi cetak**:
  - Per siswa (action di tabel siswa, unduh PNG)
  - Per kelas (action di tabel kelas, batch PDF, layout kartu, siap potong)
- **Format kartu**: Nama siswa + NIS + QR code + nama sekolah
- **Ukuran**: Kartu kredit / KTP (85.6mm × 53.98mm) agar praktis

### 7.2 Perspektif Siswa

#### Proses Scan

- **Lokasi**: Menu "Absensi" di panel student (`/student/absensi/scan`)
- **Alur minimal**: Buka halaman → kamera aktif → arahkan ke QR → selesai
- **Tidak perlu ketik apa pun** — cukup scan
- **Feedback instan**: Pesan sukses hijau atau pesan error merah dengan alasan jelas

#### State Halaman Scan

```
State 1: Tidak Ada Sesi Aktif
┌─────────────────────────────────────┐
│  Tidak ada sesi absensi yang aktif  │
│  saat ini.                          │
│                                     │
│  Tunggu guru membuka sesi absensi.  │
└─────────────────────────────────────┘

State 2: Sesi Aktif, Belum Scan
┌─────────────────────────────────────┐
│  📷 Scan QR Code Kamu               │
│  Mapel: Matematika │ Kelas: 7A     │
│  Sisa waktu: 04:12                  │
│                                     │
│  ┌─────────────────────────┐        │
│  │                         │        │
│  │    [AREA KAMERA]        │        │
│  │                         │        │
│  └─────────────────────────┘        │
│                                     │
│  Arahkan kamera ke QR code mu       │
└─────────────────────────────────────┘

State 3: Berhasil
┌─────────────────────────────────────┐
│  ✅ Absensi Berhasil!               │
│                                     │
│  Mapel: Matematika                  │
│  Waktu: 09:02 WIB                   │
│  Status: Hadir                      │
└─────────────────────────────────────┘

State 4: Gagal
┌─────────────────────────────────────┐
│  ❌ Absensi Gagal                    │
│                                     │
│  Alasan: Kamu sudah absen di sesi   │
│  ini.                               │
│                                     │
│  Hubungi guru jika ada masalah.     │
└─────────────────────────────────────┘
```

### 7.3 Aksesibilitas & Keterbatasan Perangkat

| Situasi | Solusi |
|---------|--------|
| Siswa tidak punya smartphone | Guru absensi manual (dual-mode, BR-16) |
| Smartphone tanpa kamera | Guru absensi manual |
| Koneksi internet lambat | Halaman scan minimalis, tanpa asset berat |
| QR rusak/hilang | Admin regenerate QR, cetak ulang; guru fallback ke absensi manual |
| Guru tidak punya laptop/proyektor | Guru buka sesi dari HP sendiri; tidak perlu menampilkan apa pun ke siswa |

### 7.4 Library JavaScript untuk QR Scanning

Gunakan library ringan yang bekerja di browser tanpa dependensi server:

- **Rekomendasi**: [`html5-qrcode`](https://github.com/mebjas/html5-qrcode) — lightweight, well-maintained, mobile-friendly
- Alternatif: `jsQR` (lebih minimal, tapi kurang fitur UI)
- **Tidak perlu** native app atau PWA khusus — cukup halaman web biasa dengan akses kamera via `getUserMedia`

---

## 8. Risiko & Keterbatasan

### 8.1 Risiko Teknis

| Risiko | Dampak | Probabilitas | Mitigasi |
|--------|--------|-------------|----------|
| **Kamera tidak berfungsi di browser tertentu** | Siswa tidak bisa scan | Rendah | Fallback manual; test di Chrome/Firefox mobile |
| **Izin kamera ditolak siswa** | Siswa tidak bisa scan | Sedang | Instruksi jelas di halaman; fallback manual |
| **HTTPS wajib untuk akses kamera** | Kamera tidak tersedia di HTTP | Tinggi (jika dev) | Gunakan HTTPS di production; `localhost` aman untuk development |
| **QR rusak/buram saat dicetak** | Scan gagal | Rendah | Error correction level HIGH pada generate QR; ukuran cukup besar (min 3cm × 3cm) |

### 8.2 Risiko Operasional

| Risiko | Dampak | Probabilitas | Mitigasi |
|--------|--------|-------------|----------|
| **Siswa lupa bawa kartu QR** | Tidak bisa scan | Tinggi | Guru absensi manual; dorong siswa simpan QR di tempat aman |
| **Siswa meminjamkan QR ke teman** | Titip absen | Sedang | Identity matching (Layer 3): siswa_id QR harus = user login. Meminjamkan QR fisik tidak cukup tanpa login ke akun pemilik. |
| **Guru enggan menggunakan fitur baru** | Fitur tidak dipakai | Sedang | Tetap sediakan mode manual; QR sebagai opsi tambahan, bukan pengganti |
| **Siswa tidak punya HP** | Tidak bisa self-scan | Tinggi (di SMP) | Dual-mode wajib; guru tetap bisa absensi manual untuk siswa tanpa HP |

### 8.3 Keterbatasan Fitur

1. **Tidak ada validasi lokasi/GPS**: Tidak memastikan siswa berada di kelas fisik. Ini dianggap di luar scope karena menambah kompleksitas signifikan dan isu privasi.

2. **Tidak real-time monitoring di sisi guru**: Status scan per siswa di halaman detail/view bersifat statis (di-load saat halaman dibuka). Guru harus refresh halaman untuk melihat update terbaru. Trade-off yang wajar untuk menghindari kompleksitas polling/WebSocket.

3. **Tidak ada face recognition**: Validasi identitas bergantung pada login account, bukan biometrik.

4. **QR statis bisa difotokopi**: Namun fotokopi tidak berguna tanpa login ke akun siswa pemilik QR tersebut.

5. **Dependent pada koneksi internet**: Tidak ada offline-first scan. Trade-off yang wajar untuk proyek skripsi.

---


## Lampiran A: Package Dependencies

| Package | Versi | Fungsi | Install |
|---------|-------|--------|---------|
| `endroid/qr-code` | ^6.0 | Generate QR code image (PNG/SVG) | `composer require endroid/qr-code` |
| `html5-qrcode` | ^2.3 | JavaScript QR scanner di browser | `npm install html5-qrcode` |

> Tidak ada package tambahan yang diperlukan. Semua kebutuhan lain (HMAC, PDF, autentikasi) sudah tersedia di Laravel core dan DomPDF yang sudah terinstall.

## Lampiran B: Contoh Format QR Data

```
Format:  {siswa_id}:{hmac_sha256_hex}
Contoh:  42:e3b0c44298fc1c149afbf4c8996fb92427ae41e4649b934ca495991b7852b855

Parsing:
  siswa_id  = 42
  signature = e3b0c44298fc1c149afbf4c8996fb92427ae41e4649b934ca495991b7852b855

Validasi:
  expected = hash_hmac('sha256', '42', $siswa->qr_secret)
  valid    = hash_equals(expected, signature)
```

## Lampiran C: Checklist Keamanan

- [ ] HMAC menggunakan per-siswa secret, bukan app secret
- [ ] `hash_equals()` digunakan untuk timing-safe comparison
- [ ] Rate limiting aktif pada endpoint scan
- [ ] Semua percobaan scan tercatat di `log_scan_absensi`
- [ ] HTTPS wajib di production (untuk akses kamera)
- [ ] CSRF protection aktif pada Livewire actions
- [ ] Identity matching: `siswa_id` QR = `siswa_id` user login
- [ ] Sesi absensi memiliki batas waktu otomatis
- [ ] `qr_secret` tidak pernah di-expose ke frontend/QR

---

*Dokumen ini adalah rencana teknis dan belum merupakan implementasi final. Detail teknis dapat berubah selama proses pengembangan.*
