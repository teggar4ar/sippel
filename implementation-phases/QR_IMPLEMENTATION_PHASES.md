# 9. Roadmap Implementasi

## Fase 1: Foundation (Estimasi: 3-4 hari) COMPLETED

**Tujuan**: Setup infrastruktur dasar — migrasi database, model, service class.

| No | Tugas | Detail |
|----|-------|--------|
| 1.1 | Install `endroid/qr-code` | `composer require endroid/qr-code` |
| 1.2 | Install `html5-qrcode` (frontend) | `npm install html5-qrcode` atau CDN |
| 1.3 | Buat migration `sesi_absensi` | Tabel baru sesuai desain di §4.1 |
| 1.4 | Buat migration `log_scan_absensi` | Tabel baru sesuai desain di §4.2 |
| 1.5 | Buat migration alter `detail_aktivitas` | Tambah `metode_kehadiran`, `waktu_kehadiran` |
| 1.6 | Buat migration alter `aktivitas_pembelajaran` | Tambah `absensi_mandiri`, `durasi_absensi_menit` |
| 1.7 | Buat migration alter `siswa` | Tambah `qr_secret`, `qr_generated_at` |
| 1.8 | Buat model `SesiAbsensi` | Dengan relasi ke `AktivitasPembelajaran` dan `LogScanAbsensi` |
| 1.9 | Buat model `LogScanAbsensi` | Dengan relasi ke `SesiAbsensi` dan `Siswa` |
| 1.10 | Update model `AktivitasPembelajaran` | Tambah fillable + cast untuk kolom baru |
| 1.11 | Update model `Siswa` | Tambah fillable, relasi, method `generateQrSecret()` |
| 1.12 | Update model `DetailAktivitas` | Tambah fillable untuk kolom baru |
| 1.13 | Buat `QrAttendanceService` | Core business logic sesuai §5.5 |

## Fase 2: QR Generation — Admin Only (Estimasi: 2-3 hari) COMPLETED

**Tujuan**: Admin/operator bisa generate dan cetak QR code siswa melalui panel Filament.

| No | Tugas | Detail |
|----|-------|--------|
| 2.1 | Implement `generateQrData()` di service | Format: `{siswa_id}:{hmac}` ✅ |
| 2.2 | Implement `generateQrImage()` di service | Menggunakan `endroid/qr-code` Builder ✅ |
| 2.3 | Tambah Filament Action di SiswaResource | Tombol generate/download QR per siswa (admin only) ✅ |
| 2.4 | Implement batch PDF generation | Download QR seluruh siswa satu kelas (layout kartu) ✅ |
| 2.5 | Tambah Filament Action di KelasResource | Tombol "Cetak QR Kelas" untuk batch PDF ✅ |
| 2.6 | Tambah Filament Bulk Action di SiswaResource | Regenerate QR untuk siswa terpilih ✅ |

## Fase 3: Sesi Absensi — Teacher Side (Estimasi: 3-4 hari) COMPLETED

**Tujuan**: Guru bisa mengaktifkan absensi mandiri saat create, memonitor, dan menutup sesi.

| No | Tugas | Detail |
|----|-------|--------|
| 3.1 | Modifikasi `CreateAktivitas` wizard step 2 | Tambah toggle absensi mandiri + dropdown durasi di atas daftar manual |
| 3.2 | Modifikasi logika validasi `save()` | Toggle ON → kehadiran nullable; Toggle OFF → kehadiran wajib (existing) |
| 3.3 | Implement auto-create sesi saat simpan | Jika toggle ON, buat `sesi_absensi` otomatis setelah aktivitas tersimpan |
| 3.4 | Implement countdown timer (frontend) | Alpine.js timer pada kartu aktivitas (ListAktivitas) dan halaman detail (ViewAktivitas) |
| 3.5 | Implement auto-close logic | Backend check apakah sesi sudah expired (via middleware atau saat halaman dimuat) |
| 3.6 | Modifikasi `EditAktivitas` | Tambah tombol "Tutup Sesi" dan "Buka Sesi Baru" di halaman edit |
| 3.7 | Modifikasi `ViewAktivitas` | Tampilkan status scan per siswa (deskripsi sederhana di kartu siswa: "Hadir via QR", "Belum scan") |

## Fase 4: QR Scanning — Student Side (Estimasi: 3-4 hari) COMPLETED

**Tujuan**: Siswa bisa scan QR code mereka untuk absensi.

| No | Tugas | Detail |
|----|-------|--------|
| 4.1 | Buat Livewire component `ScanAbsensi` | Halaman scan dengan kamera |
| 4.2 | Integrasikan `html5-qrcode` | Kamera → decode QR → kirim ke server |
| 4.3 | Implement `prosesQrCode()` action | Validasi berlapis sesuai §6.3 |
| 4.4 | Implement feedback UI | State management untuk 4 state (§7.2) |
| 4.5 | Tambah route di `web.php` | `GET /student/absensi/scan` |
| 4.6 | Tambah menu item di layout student | Link ke halaman scan |
| 4.7 | Handle error kamera | Izin ditolak, kamera tidak tersedia |
| 4.8 | Implement rate limiting | Maks 10 scan/menit per user |

## Fase 5: Polish & Testing (Estimasi: 2-3 hari)

**Tujuan**: Quality assurance, edge case handling, dan dokumentasi.

| No | Tugas | Detail |
|----|-------|--------|
| 5.1 | Tulis unit test untuk `QrAttendanceService` | Generate, validate, session management |
| 5.2 | Tulis feature test untuk alur scan | End-to-end test dengan Pest |
| 5.3 | Test di mobile browser (Chrome, Firefox) | Pastikan kamera dan scan berfungsi |
| 5.4 | Test edge cases | QR expired, sesi tutup saat scan, concurrent scan |
| 5.5 | Optimasi performa | N+1 queries, polling impact |
| 5.6 | Update dokumentasi | README, technical docs |
| 5.7 | Run `composer review` | Pint + Rector + PHPStan + Pest |

## Timeline Total Estimasi

```
Fase 1: Foundation         ████░░░░░░░░░░░░  3-4 hari
Fase 2: QR Generation      ░░░░███░░░░░░░░░  2-3 hari
Fase 3: Teacher Session     ░░░░░░░████░░░░░  3-4 hari
Fase 4: Student Scan        ░░░░░░░░░░░████░  3-4 hari
Fase 5: Testing & Polish    ░░░░░░░░░░░░░░██  2-3 hari
                            ──────────────────
                            Total: 13-18 hari kerja (~3-4 minggu)
```

### Dependensi Antar Fase

```
Fase 1 (Foundation) ──→ Fase 2 (QR Gen) ──→ Fase 3 (Teacher) ──→ Fase 4 (Student) ──→ Fase 5 (Testing)
                                             └─────────────────────────┘
                                             (Fase 3 & 4 bisa paralel sebagian)
```

---
