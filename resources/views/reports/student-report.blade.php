<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Laporan Siswa - {{ $siswa->user->name }}</title>
    <style>
        /* Base styles — monochrome formal */
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Times New Roman', Times, serif;
            font-size: 12px;
            line-height: 1.4;
            color: #000;
            background: #fff;
        }

        /* Page layout for A4 - DomPDF compatible */
        .page {
            width: 100%;
            max-width: 595px;
            padding: 20px 50px;
            margin: 0 auto;
            background: #fff;
        }

        /* ── Header / Kop Surat ── */
        .header {
            text-align: center;
            border-bottom: 3px double #000;
            padding-bottom: 10px;
            margin-bottom: 18px;
        }

        .school-name {
            font-size: 16pt;
            font-weight: bold;
            text-transform: uppercase;
            margin-bottom: 2px;
        }

        .school-address {
            font-size: 11pt;
            color: #000;
        }

        /* ── Judul Dokumen ── */
        .report-title {
            font-size: 12pt;
            font-weight: bold;
            text-transform: uppercase;
            text-align: center;
            margin-bottom: 14px;
            letter-spacing: 0.5px;
        }

        /* ── Metadata Info ── */
        .student-info {
            margin-bottom: 14px;
        }

        .student-info table {
            width: 100%;
        }

        .student-info td {
            padding: 1px 0;
            vertical-align: top;
            font-size: 12px;
        }

        .student-info .label {
            width: 100px;
            font-weight: normal;
        }

        .student-info .colon {
            width: 10px;
            text-align: center;
        }

        .student-info .value {
            font-weight: bold;
        }

        /* ── Section title ── */
        .section-title {
            font-size: 12px;
            font-weight: bold;
            margin-bottom: 6px;
        }

        /* ── Summary table ── */
        .summary-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 16px;
        }

        .summary-table td {
            border: 1px solid #000;
            padding: 6px 10px;
            font-size: 11px;
            text-align: center;
        }

        /* ── Report table ── */
        .report-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 14px;
            font-size: 10px;
        }

        .report-table th,
        .report-table td {
            border: 1px solid #000;
            padding: 3px 5px;
            text-align: left;
        }

        .report-table th {
            font-weight: bold;
            text-align: center;
            font-size: 10px;
        }

        .report-table td.center {
            text-align: center;
        }

        /* ── Keterangan ── */
        .keterangan {
            margin-bottom: 20px;
            font-size: 10px;
        }

        .keterangan strong {
            font-size: 10px;
        }

        .keterangan ul {
            margin-left: 15px;
            margin-top: 4px;
        }

        .keterangan li {
            margin-bottom: 2px;
        }

        /* ── Signature section ── */
        .signature-wrapper {
            margin-top: 10px;
        }

        .signature-right {
            text-align: right;
            font-size: 11px;
            padding-right: 40px;
        }

        .signature-space {
            height: 60px;
        }

        .signature-name {
            font-size: 11px;
            text-align: right;
            padding-right: 40px;
        }

        /* ── Footer watermarks ── */
        .generated-date {
            font-size: 8px;
            color: #666;
            text-align: center;
            margin-top: 20px;
            padding-top: 6px;
            border-top: 1px solid #ddd;
        }

        .watermark {
            font-size: 7px;
            color: #999;
            text-align: center;
            margin-top: 4px;
            font-style: italic;
        }

        /* Print styles */
        @media print {
            body {
                print-color-adjust: exact;
                -webkit-print-color-adjust: exact;
            }

            .page {
                width: 100%;
                padding: 0;
                margin: 0;
            }
        }

        .page-break {
            page-break-after: always;
        }
    </style>
</head>
<body>
    <div class="page">

        {{-- ═══ Kop Surat ═══ --}}
        <div class="header">
            <div class="school-name">{{ config('app.school_name', 'SMP Islam Terpadu Al-Itqon') }}</div>
            <div class="school-address">{{ config('app.school_address', 'Kp. Kandang Panjang, Jl. M. Otong, RT 001/006, Desa Tajurhalang, Kec. Tajurhalang, Kab. Bogor') }}</div>
        </div>

        {{-- ═══ Judul Dokumen ═══ --}}
        <div class="report-title">Jurnal Riwayat Aktivitas Siswa</div>

        {{-- ═══ Metadata Info (2 kolom) ═══ --}}
        <div class="student-info">
            <table>
                <tr>
                    <td class="label">NIS</td>
                    <td class="colon">:</td>
                    <td class="value">{{ $siswa->nis }}</td>
                    <td class="label">Tahun Ajaran</td>
                    <td class="colon">:</td>
                    <td class="value">{{ $tahunAjaran->nama_tahun }} - {{ $tahunAjaran->semester }}</td>
                </tr>
                <tr>
                    <td class="label">Nama Siswa</td>
                    <td class="colon">:</td>
                    <td class="value">{{ $siswa->user->name }}</td>
                    <td class="label">Kelas</td>
                    <td class="colon">:</td>
                    <td class="value">{{ ($contextKelas ?? $siswa->kelas)?->tingkat_kelas }}-{{ ($contextKelas ?? $siswa->kelas)?->grup_kelas }}</td>
                </tr>
            </table>
        </div>

        {{-- ═══ Ringkasan Keseluruhan ═══ --}}
        @php
            $activityData = $activityData ?? collect();
        @endphp
        @if($activityData->isNotEmpty())
            @php
                $totalActivities = $activityData->count();
                $hadirCount = $activityData->where('kehadiran', \App\Enums\KehadiranStatus::Hadir)->count();
                $avgKehadiranPct = $totalActivities > 0 ? ($hadirCount / $totalActivities) * 100 : 0;
                $hadirActivities = $activityData->where('kehadiran', \App\Enums\KehadiranStatus::Hadir);
                $keaktifanWeights = $hadirActivities->whereNotNull('keaktifan')->map(fn($detail) => $detail->keaktifan->weight());
                $avgKeaktifan = $keaktifanWeights->isNotEmpty() ? $keaktifanWeights->avg() : 0;

                $keaktifanLabel = match(true) {
                    $avgKeaktifan >= 3.5 => 'Sangat Aktif',
                    $avgKeaktifan >= 2.5 => 'Aktif',
                    $avgKeaktifan >= 1.5 => 'Cukup',
                    default => 'Pasif',
                };
            @endphp

            <div class="section-title">Ringkasan Keseluruhan</div>
            <table class="summary-table">
                <tr>
                    <td>Rata-rata Kehadiran: <strong>{{ number_format($avgKehadiranPct, 1) }}%</strong></td>
                    <td>Total Pertemuan: <strong>{{ $totalActivities }}</strong></td>
                    <td>Keaktifan: <strong>{{ $keaktifanLabel }}</strong></td>
                </tr>
            </table>
        @endif

        {{-- ═══ Tabel Riwayat Aktivitas Pembelajaran ═══ --}}
        <div class="section-title">Riwayat Aktivitas Pembelajaran</div>
        <table class="report-table">
            <thead>
                <tr>
                    <th style="width: 65px;">Tanggal</th>
                    <th>Mata Pelajaran</th>
                    <th style="width: 60px;">Kehadiran</th>
                    <th style="width: 70px;">Keaktifan</th>
                    <th>Catatan Guru</th>
                </tr>
            </thead>
            <tbody>
                @forelse($activityData as $detail)
                    @php
                        $kehadiranLabel = match($detail->kehadiran) {
                            \App\Enums\KehadiranStatus::Hadir => 'Hadir',
                            \App\Enums\KehadiranStatus::Izin  => 'Izin',
                            \App\Enums\KehadiranStatus::Sakit => 'Sakit',
                            \App\Enums\KehadiranStatus::Alpa  => 'Alpa',
                            default => '-',
                        };

                        $keaktifanLabel = $detail->kehadiran === \App\Enums\KehadiranStatus::Hadir
                            ? ($detail->keaktifan?->label() ?? '-')
                            : '-';
                    @endphp
                    <tr>
                        <td class="center">{{ $detail->aktivitasPembelajaran->tanggal->format('d/m/Y') }}</td>
                        <td>{{ $detail->aktivitasPembelajaran->mataPelajaran?->nama_mapel }}</td>
                        <td class="center">{{ $kehadiranLabel }}</td>
                        <td class="center">{{ $keaktifanLabel }}</td>
                        <td>{{ $detail->catatan ?? '-' }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="center" style="padding: 20px;">
                            Belum ada data riwayat aktivitas untuk periode ini
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>

        {{-- ═══ Keterangan ═══ --}}
        <div class="keterangan">
            <strong>Keterangan:</strong>
            <ul>
                <li><strong>Kehadiran</strong>: Status kehadiran siswa pada hari tersebut (Hadir/Izin/Sakit/Alpa).</li>
                <li><strong>Keaktifan</strong>: Tingkat keaktifan siswa pada sesi pembelajaran terkait.</li>
                <li><strong>Catatan Guru</strong>: Observasi khusus dari guru pengampu (jika ada).</li>
            </ul>
        </div>

        {{-- ═══ Tanda Tangan ═══ --}}
        <div class="footer">
            <div class="signature-wrapper">
                <div class="signature-right">Mengetahui,</div>
                <div class="signature-right">Wali Kelas</div>
                <div class="signature-space"></div>
                <div class="signature-name">(............................)</div>
            </div>

            <div class="generated-date">
                Dicetak pada: {{ now()->setTimezone('Asia/Jakarta')->translatedFormat('d F Y, H:i') }} WIB
            </div>

            <div class="watermark">
                Laporan ini dibuat oleh sistem {{ config('app.name') }}
            </div>
        </div>
    </div>

</body>
</html>
