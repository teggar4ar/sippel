<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Laporan Siswa - {{ $siswa->user->name }}</title>
    <style>
        /* Base styles */
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'DejaVu Sans', Arial, sans-serif;
            font-size: 11px;
            line-height: 1.4;
            color: #333;
            background: #fff;
        }

        /* Page layout for A4 - DomPDF compatible */
        .page {
            width: 100%;
            max-width: 700px;
            padding: 20px 30px;
            margin: 0 auto;
            background: #fff;
        }

        /* Header */
        .header {
            text-align: center;
            border-bottom: 3px double #333;
            padding-bottom: 15px;
            margin-bottom: 20px;
        }

        .school-name {
            font-size: 16px;
            font-weight: bold;
            text-transform: uppercase;
            margin-bottom: 5px;
        }

        .school-address {
            font-size: 10px;
            color: #555;
        }

        .report-title {
            font-size: 14px;
            font-weight: bold;
            text-transform: uppercase;
            margin-top: 15px;
            letter-spacing: 1px;
        }

        /* Student info */
        .student-info {
            margin-bottom: 15px;
            padding: 10px;
            background: #f9f9f9;
            border: 1px solid #ddd;
        }

        .student-info table {
            width: 100%;
        }

        .student-info td {
            padding: 3px 5px;
            vertical-align: top;
            font-size: 10px;
        }

        .student-info .label {
            width: 100px;
            font-weight: bold;
        }

        /* Report table */
        .report-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 15px;
            font-size: 10px;
        }

        .report-table th,
        .report-table td {
            border: 1px solid #333;
            padding: 6px 8px;
            text-align: left;
        }

        .report-table th {
            background: #f0f0f0;
            font-weight: bold;
            text-align: center;
            font-size: 10px;
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

        /* Summary section */
        .summary {
            margin-bottom: 15px;
            padding: 10px;
            background: #f0f7ff;
            border: 1px solid #bdd7ff;
        }

        .summary-title {
            font-size: 12px;
            font-weight: bold;
            margin-bottom: 10px;
        }

        .summary-grid {
            display: table;
            width: 100%;
        }

        .summary-item {
            display: table-cell;
            width: 33.33%;
            text-align: center;
            padding: 8px;
        }

        .summary-value {
            font-size: 18px;
            font-weight: bold;
            color: #1e40af;
        }

        .summary-label {
            font-size: 9px;
            color: #555;
            margin-top: 3px;
        }

        /* Footer */
        .footer {
            margin-top: 20px;
        }

        .signature-section {
            display: table;
            width: 100%;
            margin-top: 25px;
        }

        .signature-box {
            display: table-cell;
            width: 50%;
            text-align: center;
            vertical-align: top;
            font-size: 10px;
        }

        .signature-line {
            margin-top: 50px;
            border-top: 1px solid #333;
            width: 150px;
            display: inline-block;
        }

        .signature-name {
            margin-top: 5px;
            font-weight: bold;
            font-size: 10px;
        }

        .generated-date {
            font-size: 9px;
            color: #666;
            text-align: right;
            margin-top: 15px;
            padding-top: 8px;
            border-top: 1px solid #ddd;
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

        /* Notes section */
        .notes {
            font-size: 9px;
            margin-bottom: 15px;
        }

        .notes ul {
            margin-left: 15px;
            margin-top: 3px;
        }
    </style>
</head>
<body>
    <div class="page">
        {{-- Header --}}
        <div class="header">
            <div class="school-name">{{ config('app.name', 'SMP Islam Terpadu Al-Itqon') }}</div>
            <div class="school-address">Kp. Kandang Panjang RT. 01/06 Desa Tajurhalang Kec. Tajurhalang Kab. Bogor.</div>
            <div class="report-title">Laporan Perkembangan Belajar Siswa</div>
        </div>

        {{-- Student Information --}}
        <div class="student-info">
            <table>
                <tr>
                    <td class="label">NIS</td>
                    <td>: {{ $siswa->nis }}</td>
                    <td class="label">Tahun Ajaran</td>
                    <td>: {{ $tahunAjaran->nama_tahun }} - {{ $tahunAjaran->semester }}</td>
                </tr>
                <tr>
                    <td class="label">Nama Siswa</td>
                    <td>: {{ $siswa->user->name }}</td>
                    <td class="label">Kelas</td>
                    <td>: {{ $siswa->kelas->tingkat_kelas }}-{{ $siswa->kelas->grup_kelas }}</td>
                </tr>
            </table>
        </div>

        {{-- Overall Summary --}}
        @if($laporanData->isNotEmpty())
            @php
                $avgKehadiran = $laporanData->avg('rata_kehadiran');
                $avgNilai = $laporanData->avg('rata_nilai');
                $avgPartisipasi = $laporanData->avg('rata_partisipasi');
            @endphp
            <div class="summary">
                <div class="summary-title">Ringkasan Keseluruhan</div>
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
                </div>
            </div>
        @endif

        {{-- Subject Details Table --}}
        <table class="report-table">
            <thead>
                <tr>
                    <th style="width: 30px;">No</th>
                    <th>Mata Pelajaran</th>
                    <th style="width: 70px;">Kehadiran</th>
                    <th style="width: 60px;">Nilai</th>
                    <th style="width: 70px;">Partisipasi</th>
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
                    @endphp
                    <tr>
                        <td class="center">{{ $index + 1 }}</td>
                        <td>{{ $laporan->mataPelajaran->nama_mapel }}</td>
                        <td class="center">{{ number_format($laporan->rata_kehadiran, 1) }}%</td>
                        <td class="center {{ $gradeClass }}">{{ number_format($laporan->rata_nilai, 1) }}</td>
                        <td class="center">{{ $laporan->rata_partisipasi }}/5</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="center" style="padding: 20px; color: #666;">
                            Belum ada data laporan untuk periode ini
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>

        {{-- Notes section --}}
        <div class="notes">
            <strong>Keterangan:</strong>
            <ul>
                <li>Kehadiran: Persentase kehadiran siswa pada mata pelajaran</li>
                <li>Nilai: Rata-rata nilai dari seluruh aktivitas pembelajaran</li>
                <li>Partisipasi: Tingkat keaktifan siswa (skala 1-5)</li>
            </ul>
        </div>

        {{-- Footer with signatures --}}
        <div class="footer">
            <div class="signature-section">
                <div class="signature-box">
                    <div>Mengetahui,</div>
                    <div>Wali Kelas</div>
                    <div class="signature-line"></div>
                    <div class="signature-name">{{ $siswa->kelas->waliKelas?->name ?? '.............................' }}</div>
                </div>
                <div class="signature-box">
                    <div>Orang Tua/Wali</div>
                    <div>&nbsp;</div>
                    <div class="signature-line"></div>
                    <div class="signature-name">...............................</div>
                </div>
            </div>

            <div class="generated-date">
                Dicetak pada: {{ now()->setTimezone('Asia/Jakarta')->translatedFormat('d F Y, H:i') }} WIB
            </div>
        </div>
    </div>
</body>
</html>
