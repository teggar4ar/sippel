<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Laporan Kelas {{ $kelas->tingkat_kelas }}-{{ $kelas->grup_kelas }} - {{ $mataPelajaran->nama_mapel }}</title>
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
        .class-info {
            margin-bottom: 14px;
        }

        .class-info table {
            width: 100%;
        }

        .class-info td {
            padding: 1px 0;
            vertical-align: top;
            font-size: 12px;
        }

        .class-info .label {
            width: 100px;
            font-weight: normal;
        }

        .class-info .colon {
            width: 10px;
            text-align: center;
        }

        .class-info .value {
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
            text-align: center;
            margin-top: 10px;
            font-size: 11px;
        }

        .signature-wrapper .mengetahui {
            margin-bottom: 6px;
        }

        .signature-section {
            display: table;
            width: 100%;
            margin-top: 0;
        }

        .signature-box {
            display: table-cell;
            width: 50%;
            text-align: center;
            vertical-align: top;
            font-size: 11px;
        }

        .signature-space {
            height: 60px;
        }

        .signature-name {
            font-size: 11px;
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
            <div class="school-address">
                {{ config('app.school_address', 'Kp. Kandang Panjang, Jl. M. Otong, RT 001/006, Desa Tajurhalang, Kec. Tajurhalang, Kab. Bogor') }}
            </div>
        </div>

        {{-- ═══ Judul Dokumen ═══ --}}
        <div class="report-title">Jurnal Rekap Kelas Per Mata Pelajaran</div>

        {{-- ═══ Metadata Info (2 kolom) ═══ --}}
        <div class="class-info">
            <table>
                <tr>
                    <td class="label">Kelas</td>
                    <td class="colon">:</td>
                    <td class="value">{{ $kelas->tingkat_kelas }}-{{ $kelas->grup_kelas }}</td>
                    <td class="label">Wali Kelas</td>
                    <td class="colon">:</td>
                    <td class="value">{{ $kelas->waliKelas?->name ?? '-' }}</td>
                </tr>
                <tr>
                    <td class="label">Mata Pelajaran</td>
                    <td class="colon">:</td>
                    <td class="value">{{ $mataPelajaran->nama_mapel }}</td>
                    <td class="label">Guru Pengampu</td>
                    <td class="colon">:</td>
                    <td class="value">{{ $mataPelajaran->guru?->name ?? '-' }}</td>
                </tr>
                <tr>
                    <td class="label">Tahun Ajaran</td>
                    <td class="colon">:</td>
                    <td class="value">{{ $tahunAjaran->nama_tahun }} - {{ $tahunAjaran->semester }}</td>
                    <td class="label">Total Siswa</td>
                    <td class="colon">:</td>
                    <td class="value">{{ $laporanData->count() }}</td>
                </tr>
            </table>
        </div>

        {{-- ═══ Ringkasan Kelas ═══ --}}
        @if ($laporanData->isNotEmpty())
            @php
                $avgKehadiran = $laporanData->avg('rata_kehadiran');
                $keaktifanWeights = $laporanData->pluck('rata_keaktifan')->filter()->map->weight();
                $avgKeaktifan = $keaktifanWeights->isNotEmpty() ? $keaktifanWeights->avg() : 0;
                $totalPertemuan = $laporanData->max('total_kehadiran') ?? 0;

                $keaktifanLabel = match (true) {
                    $avgKeaktifan >= 3.5 => 'Sangat Aktif',
                    $avgKeaktifan >= 2.5 => 'Aktif',
                    $avgKeaktifan >= 1.5 => 'Cukup',
                    default => 'Pasif',
                };
            @endphp

            <div class="section-title">Ringkasan Kelas</div>
            <table class="summary-table">
                <tr>
                    <td>Rata-rata Kehadiran: <strong>{{ number_format($avgKehadiran, 1) }}%</strong></td>
                    <td>Total Pertemuan: <strong>{{ $totalPertemuan }}</strong></td>
                    <td>Keaktifan: <strong>{{ $keaktifanLabel }}</strong></td>
                </tr>
            </table>
        @endif

        {{-- ═══ Tabel Rekapitulasi Siswa ═══ --}}
        <div class="section-title">Tabel Rekapitulasi Siswa</div>
        <table class="report-table">
            <thead>
                <tr>
                    <th style="width: 25px;">No</th>
                    <th style="width: 65px;">NIS</th>
                    <th>Nama Siswa</th>
                    <th style="width: 50px;">% Kehadiran</th>
                    <th style="width: 25px;">H</th>
                    <th style="width: 25px;">I</th>
                    <th style="width: 25px;">S</th>
                    <th style="width: 25px;">A</th>
                    <th style="width: 60px;">Keaktifan</th>
                </tr>
            </thead>
            <tbody>
                @forelse($laporanData as $index => $laporan)
                    @php
                        $labelKeaktifan = $laporan->rata_keaktifan?->label() ?? '-';
                    @endphp
                    <tr>
                        <td class="center">{{ $index + 1 }}</td>
                        <td class="center">{{ $laporan->siswa->nis }}</td>
                        <td>{{ $laporan->siswa->user->name }}</td>
                        <td class="center">{{ number_format($laporan->rata_kehadiran, 1) }}%</td>
                        <td class="center">{{ $laporan->hadir_count }}</td>
                        <td class="center">{{ $laporan->izin_count }}</td>
                        <td class="center">{{ $laporan->sakit_count }}</td>
                        <td class="center">{{ $laporan->alpa_count }}</td>
                        <td class="center">{{ $labelKeaktifan }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="9" class="center" style="padding: 20px;">
                            Belum ada data laporan untuk kelas dan mata pelajaran ini
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>

        {{-- ═══ Keterangan ═══ --}}
        <div class="keterangan">
            <strong>Keterangan:</strong>
            <ul>
                <li><strong>% Kehadiran</strong>: Persentase kehadiran siswa selama aktivitas pembelajaran berlangsung.
                </li>
                <li><strong>Keaktifan</strong>: Tingkat rata-rata keaktifan siswa (Sangat Aktif / Aktif / Cukup /
                    Pasif).</li>
            </ul>
        </div>

        {{-- ═══ Tanda Tangan ═══ --}}
        <div class="footer">
            <div class="signature-wrapper">
                <div class="mengetahui">Mengetahui,</div>
            </div>

            <div class="signature-section">
                <div class="signature-box">
                    <div>Guru Mata Pelajaran</div>
                    <div class="signature-space"></div>
                    <div class="signature-name">(............................)</div>
                </div>
                <div class="signature-box">
                    <div>Wali Kelas</div>
                    <div class="signature-space"></div>
                    <div class="signature-name">(............................)</div>
                </div>
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
