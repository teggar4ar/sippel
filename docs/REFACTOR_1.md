# PANDUAN REFACTOR SIPPEL: TRANSISI KE JURNAL OBSERVASI

**Proyek:** Sistem Informasi Pencatatan Aktivitas Pembelajaran (SIPPEL) — SMPIT Al-Itqon  
**Fokus Utama:** Menghilangkan kesan "Sistem Penilaian Akademik/Raport" menjadi murni "Jurnal Observasi Perilaku dan Aktivitas" tanpa mengubah struktur *database*.

---

## TAHAP 1: PENYESUAIAN LOGIC & MODEL (BACKEND)

> **Tujuan:** Membuat "jembatan penerjemah" agar angka di database otomatis menjadi label teks di frontend.

### 1.1 Update Model `DetailAktivitas`

Tambahkan Accessor untuk menerjemahkan angka partisipasi menjadi label.

- Buka file `app/Models/DetailAktivitas.php`
- Tambahkan *method* berikut:

```php
public function getLabelPartisipasiAttribute()
{
    if ($this->kehadiran !== 'Hadir') return '-';

    return match((int)$this->partisipasi) {
        1    => 'Pasif',
        2    => 'Cukup',
        3    => 'Aktif',
        4    => 'Sangat Aktif',
        default => '-',
    };
}
```

- [x] File dibuka
- [x] Method ditambahkan — accessor `label_partisipasi` via Laravel `Attribute::make()`, membandingkan enum `KehadiranStatus::Hadir`
- [x] Test via PHPStan level 5 — No errors

---

### 1.2 Update Model `Siswa`

Sesuaikan kalkulasi rata-rata agar menghasilkan label, bukan angka desimal.

- Buka file `app/Models/Siswa.php`
- Modifikasi atau tambahkan Accessor untuk rata-rata partisipasi (berdasarkan angka konversi 1–5)
- Terapkan logic `match` yang sama seperti di atas untuk hasil rata-ratanya

- [x] Method `getAverageParticipationLabel()` ditambahkan dengan threshold: <1.5 Pasif, <2.5 Cukup, <3.5 Aktif, ≥3.5 Sangat Aktif
- [x] Accessor `averageParticipationLabel` ditambahkan → akses via `$siswa->average_participation_label`
- [x] Output berupa label teks — `getAverageParticipation()` lama tetap utuh (tidak merusak kode yang pakai angka)

---

### 1.3 Logic Penyimpanan Data (Controller / Livewire Component)

Buat mapping saat form **"Simpan Aktivitas"** di-submit:

| Input UI      | `partisipasi` (DB) | `nilai` (DB) |
|---------------|--------------------|--------------|
| Pasif         | `1`                | `60`         |
| Cukup         | `2`                | `75`         |
| Aktif         | `3`                | `85`         |
| Sangat Aktif  | `4`                | `95`         |

> **Rule Khusus:** Jika `kehadiran` **bukan** `'Hadir'`, pastikan `partisipasi` dan `nilai` di-set `null`.

- [x] Mapping `resolveNilaiFromPartisipasi()` diterapkan di `CreateAktivitas` dan `EditAktivitas`
- [x] Rule null untuk non-hadir sudah aktif — partisipasi & nilai di-set `null` jika bukan Hadir

---

## TAHAP 2: REFACTOR HALAMAN KELOLA AKTIVITAS (INPUT OBSERVASI)

> **Tujuan:** Mempercepat proses input data oleh guru di dalam kelas menggunakan layar sentuh *(mobile-first)*.

### 2.1 UI Card Siswa (Livewire + Flux UI)

- Gunakan `<flux:card>` bertumpuk ke bawah untuk setiap siswa
- **Header Card:** Tampilkan Nama Siswa (tebal) dan NIS
- **Tombol Kehadiran:** Gunakan `<flux:radio.group variant="segmented">` berjejer horizontal → `H | I | S | A`
- **Tombol Partisipasi:** Gunakan grid 1 baris untuk opsi: `Pasif | Cukup | Aktif | Sangat Aktif`
- **Catatan:** Gunakan wireframe yang dilampirkan sebagai acuan UI, Sembunyikan `<textarea>` default, lalu ganti dengan `<flux:button icon="pencil">` di sudut kanan atas card → memunculkan `<flux:modal>` untuk mengisi catatan

- [x] Card siswa diimplementasikan — satu baris horizontal per siswa: Avatar + Nama/NIS + H/I/S/A + Pasif/Cukup/Aktif/Sangat Aktif
- [x] Radio group kehadiran aktif — tombol H/I/S/A dengan warna emerald/sky/amber/rose
- [x] Grid partisipasi 1 baris aktif — 4 tombol teks: Pasif, Cukup, Aktif, Sangat Aktif
- [x] Modal catatan siswa — skip (dikerjakan nanti sesuai instruksi)

---

### 2.2 Conditional Rendering (Alpine.js / Livewire)

Pasang logic reaktif:

> Jika status kehadiran diset ke **Izin (I)**, **Sakit (S)**, atau **Alpa (A)** → blok tombol "Partisipasi" dan icon "Catatan" **wajib disembunyikan** agar form lebih ringkas.

- [x] Conditional rendering terpasang — Alpine.js x-show="kehadiran === 'Hadir'" pada blok partisipasi, auto-null via setKehadiran()
- [x] Diuji untuk semua status non-hadir — setKehadiran() reset partisipasi ke null saat non-Hadir

---

### 2.3 Fitur Mass-Action

- Sediakan toggle **"Tandai Semua Hadir"** di bagian atas daftar kelas untuk menghemat waktu guru

- [x] Toggle mass-action tersedia — tombol "Tandai Semua Hadir" di header section aktivitas kelas
- [x] Fungsi berjalan dengan benar — memanggil wire:click="setAllAttendance('Hadir')" yang sudah ada di component

---

## TAHAP 3: REFACTOR DASHBOARD GURU (DESKTOP VIEW)

> **Tujuan:** Mengoptimalkan ruang kosong di layar desktop dan menyajikan data observasi terkini secara padat.

### 3.1 Struktur Layout (Grid 2 Kolom)

Ubah layout menjadi:

| Kolom Kiri | Kolom Kanan |
|------------|-------------|
| 60%        | 40%         |

- [ ] Layout grid 2 kolom diterapkan

---

### 3.2 Top Widget (Ringkasan — 4 Card Statistik)

| Card                   | Contoh Value  |
|------------------------|---------------|
| Kelas Diampu           | 2 Kelas       |
| Total Siswa            | 50 Siswa      |
| Aktivitas Minggu Ini   | 4 Aktivitas   |
| Rata-rata Kehadiran    | 85.5%         |

- [ ] 4 card statistik tampil di bagian atas dashboard

---

### 3.3 Konten Kolom Kiri: Aktivitas Terkini

Tampilkan **Data Table** berisi 5 aktivitas terakhir dengan kolom:

| Tanggal | Kelas | Mata Pelajaran | Topik | % Kehadiran | Partisipasi |
|---------|-------|----------------|-------|-------------|-------------|
| ...     | ...   | ...            | ...   | ...         | Aktif/Pasif |

- [ ] Tabel aktivitas terkini ditampilkan
- [ ] Kolom Partisipasi menampilkan label teks

---

### 3.4 Konten Kolom Kanan: Daftar Kelas Saya

Buat card vertikal per kelas, menampilkan:

- Nama Kelas
- Total Siswa
- Rata-rata Partisipasi **(label teks — hapus angka seperti "4/5")**
- Tombol jalan pintas **"+ Buat Aktivitas"**

- [ ] Card per kelas ditampilkan
- [ ] Angka rata-rata partisipasi diganti label teks
- [ ] Tombol shortcut aktif

---

## TAHAP 4: REFACTOR HALAMAN LAPORAN & PDF

> **Tujuan:** Menghilangkan jejak "Raport Akademik" pada halaman pratinjau web dan hasil cetak dokumen.

### 4.1 Laporan Rekap Kelas (Halaman Web) ***DONE***

**Ringkasan — hapus metrik berikut:**
- ~~Nilai Tertinggi~~
- ~~Nilai Terendah~~
- ~~Tuntas~~

**Ganti dengan:**
- Rata-rata Kehadiran (%)
- Total Pertemuan
- Partisipasi Kelas (Label Teks)

**Tabel:**
- Hapus kolom **"Nilai"** dan **"Rank"**
- Ubah angka kolom **"Partisipasi"** menjadi label konversi (`Aktif / Pasif / dst`)

- [ ] Metrik ringkasan diperbarui
- [ ] Kolom Nilai dan Rank dihapus dari tabel
- [ ] Partisipasi tampil sebagai label

---

### 4.2 Laporan Riwayat Siswa (Halaman Web)

- **Judul Laporan:** Ubah menjadi → `JURNAL RIWAYAT AKTIVITAS SISWA`

**Ringkasan — hapus:**
- ~~Rata-rata Nilai~~

**Pertahankan:**
- Kehadiran (%)
- Total Pertemuan
- Rata-rata Partisipasi (Label)

**Struktur Tabel — rombak menjadi format Timeline harian:**

| Tanggal | Mata Pelajaran | Kehadiran | Partisipasi | Catatan Guru |
|---------|----------------|-----------|-------------|--------------|
| ...     | ...            | H/I/S/A   | Label       | ...          |

- [ ] Judul laporan diubah
- [ ] Rata-rata nilai dihapus dari ringkasan
- [ ] Tabel diubah ke format timeline

---

### 4.3 Penyesuaian DomPDF (`student-report.blade.php` & `class-report.blade.php`)

- Panggil accessor `$model->label_partisipasi` pada file blade cetak agar angka tidak bocor ke dokumen
- **Hapus** area tanda tangan untuk ~~Orang Tua/Wali~~
- **Sisakan** hanya penanda tangan untuk:
  - Guru Mata Pelajaran
  - Wali Kelas
- Ubah deskripsi pada bagian **"Keterangan"** menjadi:
  > *"Partisipasi adalah tingkat keaktifan siswa selama observasi"*  
  *(bukan "Nilai rata-rata tugas")*

- [ ] Accessor `label_partisipasi` dipanggil di blade cetak
- [ ] Kolom tanda tangan orang tua dihapus
- [ ] Teks keterangan diperbarui

---

## RINGKASAN PROGRESS

| Tahap | Deskripsi                          | Status     |
|-------|------------------------------------|------------|
| 1     | Penyesuaian Logic & Model          | ✅ Done    |
| 2     | Refactor Halaman Input Observasi   | ✅ Done    |
| 3     | Refactor Dashboard Guru            | ⬜ Pending |
| 4     | Refactor Laporan & PDF             | ⬜ Pending |

> Kerjakan satu per satu sesuai urutan. Pastikan setiap checklist di atas dicentang sebelum lanjut ke tahap berikutnya.