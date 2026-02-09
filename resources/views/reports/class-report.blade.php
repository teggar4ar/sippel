<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Laporan Kelas {{ $kelas->tingkat_kelas }}-{{ $kelas->grup_kelas }} - {{ $mataPelajaran->nama_mapel }}</title>
    <style>
        /* Base styles */
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Times New Roman', Times, serif;
            font-size: 10px;
            line-height: 1.3;
            color: #333;
            background: #fff;
        }

        /* Page layout for A4 - DomPDF compatible */
        /* A4: 210mm x 297mm = 595pt x 842pt */
        /* Margins: 2.54cm = 72pt (1 inch) */
        .page {
            width: 100%;
            max-width: 595px;
            padding: 20px 72px; /* Top/bottom: 20px, Left/right: 72px (2.54cm) */
            margin: 0 auto;
            background: #fff;
        }

        /* Header */
        .header {
            text-align: center;
            border-bottom: 3px double #333;
            padding-bottom: 12px;
            margin-bottom: 15px;
        }

        .school-name {
            font-size: 14px;
            font-weight: bold;
            text-transform: uppercase;
            margin-bottom: 3px;
        }

        .school-address {
            font-size: 9px;
            color: #555;
        }

        .report-title {
            font-size: 12px;
            font-weight: bold;
            text-transform: uppercase;
            margin-top: 12px;
            letter-spacing: 1px;
        }

        /* Class info */
        .class-info {
            margin-bottom: 12px;
            padding: 8px;
            background: #f9f9f9;
            border: 1px solid #ddd;
        }

        .class-info table {
            width: 100%;
        }

        .class-info td {
            padding: 2px 5px;
            vertical-align: top;
            font-size: 9px;
        }

        .class-info .label {
            width: 90px;
            font-weight: bold;
        }

        /* Report table */
        .report-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 12px;
            font-size: 9px;
        }

        .report-table th,
        .report-table td {
            border: 1px solid #333;
            padding: 4px 6px;
            text-align: left;
        }

        .report-table th {
            background: #e8e8e8;
            font-weight: bold;
            text-align: center;
            font-size: 9px;
        }

        .report-table td.center {
            text-align: center;
        }

        .report-table td.number {
            text-align: right;
        }

        .report-table tr:nth-child(even) {
            background: #fafafa;
        }

        /* Ranking colors */
        .rank-1 {
            background: #fef3c7 !important;
        }

        .rank-2 {
            background: #e5e7eb !important;
        }

        .rank-3 {
            background: #fed7aa !important;
        }

        /* Grade colors */
        .grade-excellent {
            color: #16a34a;
            font-weight: bold;
        }

        .grade-good {
            color: #2563eb;
        }

        .grade-fair {
            color: #ca8a04;
        }

        .grade-poor {
            color: #dc2626;
            font-weight: bold;
        }

        /* Attendance colors */
        .attendance-excellent {
            color: #16a34a;
        }

        .attendance-good {
            color: #2563eb;
        }

        .attendance-fair {
            color: #ca8a04;
        }

        .attendance-poor {
            color: #dc2626;
        }

        /* Summary section */
        .summary {
            margin-bottom: 12px;
            padding: 10px;
            background: #f0f7ff;
            border: 1px solid #bdd7ff;
        }

        .summary-title {
            font-size: 10px;
            font-weight: bold;
            margin-bottom: 8px;
        }

        .summary-grid {
            display: table;
            width: 100%;
        }

        .summary-item {
            display: table-cell;
            width: 25%;
            text-align: center;
            padding: 6px;
        }

        .summary-value {
            font-size: 14px;
            font-weight: bold;
            color: #1e40af;
        }

        .summary-label {
            font-size: 8px;
            color: #555;
            margin-top: 2px;
        }

        /* Footer */
        .footer {
            margin-top: 15px;
        }

        .signature-section {
            display: table;
            width: 100%;
            margin-top: 20px;
        }

        .signature-box {
            display: table-cell;
            width: 50%;
            text-align: center;
            vertical-align: top;
            font-size: 9px;
        }

        .signature-line {
            margin-top: 40px;
            border-top: 1px solid #333;
            width: 140px;
            display: inline-block;
        }

        .signature-name {
            margin-top: 4px;
            font-weight: bold;
            font-size: 9px;
        }

        .signature-nip {
            font-size: 8px;
            color: #555;
        }

        .generated-date {
            font-size: 8px;
            color: #666;
            text-align: right;
            margin-top: 12px;
            padding-top: 6px;
            border-top: 1px solid #ddd;
        }

        .watermark {
            font-size: 7px;
            color: #999;
            text-align: center;
            margin-top: 8px;
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

        /* Page break for multi-page */
        .page-break {
            page-break-after: always;
        }

        /* Statistics box */
        .stats-box {
            display: inline-block;
            padding: 5px 10px;
            margin: 2px;
            border-radius: 3px;
            font-size: 10px;
        }

        .stats-highest {
            background: #dcfce7;
            color: #166534;
        }

        .stats-lowest {
            background: #fee2e2;
            color: #991b1b;
        }
    </style>
</head>
<body>
    <div class="page">
        {{-- Header --}}
        <div class="header">
            <div class="school-name">{{ config('app.school_name', 'SMP Islam Terpadu Al-Itqon') }}</div>
            <div class="school-address">{{ config('app.school_address', 'Kp. Kandang Panjang RT. 01/06 Desa Tajurhalang Kec. Tajurhalang Kab. Bogor.') }}</div>
            <div class="report-title">Laporan Rekap Kelas per Mata Pelajaran</div>
        </div>

        {{-- Class Information --}}
        <div class="class-info">
            <table>
                <tr>
                    <td class="label">Kelas</td>
                    <td>: {{ $kelas->tingkat_kelas }}-{{ $kelas->grup_kelas }}</td>
                    <td class="label">Tahun Ajaran</td>
                    <td>: {{ $tahunAjaran->nama_tahun }} - {{ $tahunAjaran->semester }}</td>
                </tr>
                <tr>
                    <td class="label">Mata Pelajaran</td>
                    <td>: {{ $mataPelajaran->nama_mapel }}</td>
                    <td class="label">Wali Kelas</td>
                    <td>: {{ $kelas->waliKelas?->name ?? '-' }}</td>
                </tr>
                <tr>
                    <td class="label">Jumlah Siswa</td>
                    <td>: {{ $laporanData->count() }} siswa</td>
                    <td class="label">Guru Pengampu</td>
                    <td>: {{ $mataPelajaran->guru?->name ?? '-' }}</td>
                </tr>
            </table>
        </div>

        {{-- Class Summary --}}
        @if($laporanData->isNotEmpty())
            @php
                $avgKehadiran = $laporanData->avg('rata_kehadiran');
                $avgNilai = $laporanData->avg('rata_nilai');
                $avgPartisipasi = $laporanData->avg('rata_partisipasi');

                $highestNilai = $laporanData->max('rata_nilai');
                $lowestNilai = $laporanData->min('rata_nilai');
                $highestKehadiran = $laporanData->max('rata_kehadiran');
                $lowestKehadiran = $laporanData->min('rata_kehadiran');
            @endphp
            <div class="summary">
                <div class="summary-title">Ringkasan Kelas</div>
                <div class="summary-grid">
                    <div class="summary-item">
                        <div class="summary-value">{{ number_format($avgKehadiran, 1) }}%</div>
                        <div class="summary-label">Rata-rata Kehadiran</div>
                    </div>
                    <div class="summary-item">
                        <div class="summary-value">{{ number_format($avgNilai, 1) }}</div>
                        <div class="summary-label">Rata-rata Nilai</div>
                    </div>
                    <div class="summary-item">
                        <div class="summary-value">{{ number_format($avgPartisipasi, 1) }}/5</div>
                        <div class="summary-label">Rata-rata Partisipasi</div>
                    </div>
                    <div class="summary-item">
                        <div class="summary-value">{{ $laporanData->where('rata_nilai', '>=', 70)->count() }}</div>
                        <div class="summary-label">Siswa Tuntas (≥70)</div>
                    </div>
                </div>
                <div style="text-align: center; margin-top: 10px;">
                    <span class="stats-box stats-highest">Nilai Tertinggi: {{ number_format($highestNilai, 1) }}</span>
                    <span class="stats-box stats-lowest">Nilai Terendah: {{ number_format($lowestNilai, 1) }}</span>
                </div>
            </div>
        @endif

        {{-- Student Details Table --}}
        <table class="report-table">
            <thead>
                <tr>
                    <th style="width: 30px;">No</th>
                    <th style="width: 60px;">NIS</th>
                    <th>Nama Siswa</th>
                    <th style="width: 50px;">%</th>
                    <th style="width: 35px;">H</th>
                    <th style="width: 35px;">I</th>
                    <th style="width: 35px;">S</th>
                    <th style="width: 35px;">A</th>
                    <th style="width: 50px;">Nilai</th>
                    <th style="width: 50px;">Part</th>
                    <th style="width: 40px;">Rank</th>
                </tr>
            </thead>
            <tbody>
                @forelse($laporanData as $index => $laporan)
                    @php
                        $gradeClass = match(true) {
                            $laporan->rata_nilai >= 85 => 'grade-excellent',
                            $laporan->rata_nilai >= 70 => 'grade-good',
                            $laporan->rata_nilai >= 55 => 'grade-fair',
                            default => 'grade-poor',
                        };

                        $attendanceClass = match(true) {
                            $laporan->rata_kehadiran >= 90 => 'attendance-excellent',
                            $laporan->rata_kehadiran >= 75 => 'attendance-good',
                            $laporan->rata_kehadiran >= 60 => 'attendance-fair',
                            default => 'attendance-poor',
                        };

                        $rankClass = match($index + 1) {
                            1 => 'rank-1',
                            2 => 'rank-2',
                            3 => 'rank-3',
                            default => '',
                        };
                    @endphp
                    <tr class="{{ $rankClass }}">
                        <td class="center">{{ $index + 1 }}</td>
                        <td class="center">{{ $laporan->siswa->nis }}</td>
                        <td>{{ $laporan->siswa->user->name }}</td>
                        <td class="center {{ $attendanceClass }}">{{ number_format($laporan->rata_kehadiran, 1) }}%</td>
                        <td class="center">{{ $laporan->hadir_count }}</td>
                        <td class="center">{{ $laporan->izin_count }}</td>
                        <td class="center">{{ $laporan->sakit_count }}</td>
                        <td class="center">{{ $laporan->alpa_count }}</td>
                        <td class="center {{ $gradeClass }}">{{ number_format($laporan->rata_nilai, 1) }}</td>
                        <td class="center">{{ $laporan->rata_partisipasi }}/5</td>
                        <td class="center"><strong>{{ $index + 1 }}</strong></td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="11" class="center" style="padding: 20px; color: #666;">
                            Belum ada data laporan untuk kelas dan mata pelajaran ini
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>

        {{-- Notes section --}}
        <div style="margin-bottom: 15px; font-size: 10px;">
            <strong>Keterangan:</strong>
            <ul style="margin-left: 15px; margin-top: 5px;">
                <li>Kehadiran: Persentase kehadiran siswa (Hijau ≥90%, Biru ≥75%, Kuning ≥60%, Merah &lt;60%)</li>
                <li>Nilai: Rata-rata nilai aktivitas (Hijau ≥85, Biru ≥70, Kuning ≥55, Merah &lt;55)</li>
                <li>Partisipasi: Tingkat keaktifan siswa (skala 1-5)</li>
                <li>Siswa diurutkan berdasarkan nilai tertinggi</li>
            </ul>
        </div>

        {{-- Footer with signatures --}}
        <div class="footer">
            <div class="signature-section">
                <div class="signature-box">
                    <div>Guru Mata Pelajaran</div>
                    <div class="signature-line"></div>
                    <div class="signature-name">{{ $mataPelajaran->guru?->name ?? '.............................' }}</div>
                </div>
                <div class="signature-box">
                    <div>Wali Kelas</div>
                    <div class="signature-line"></div>
                    <div class="signature-name">{{ $kelas->waliKelas?->name ?? '.............................' }}</div>
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
