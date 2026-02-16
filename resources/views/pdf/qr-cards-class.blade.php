<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>QR Cards - Kelas {{ $kelas->nama_lengkap }}</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'DejaVu Sans', sans-serif;
            font-size: 10pt;
        }

        .page-header {
            text-align: center;
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 2px solid #333;
        }

        .page-header h1 {
            font-size: 18pt;
            margin-bottom: 5px;
        }

        .page-header .subtitle {
            font-size: 12pt;
            color: #666;
        }

        .cards-container {
            display: flex;
            flex-wrap: wrap;
            gap: 15px;
            justify-content: center;
        }

        .qr-card {
            width: 180px;
            border: 2px solid #333;
            border-radius: 8px;
            padding: 12px;
            text-align: center;
            page-break-inside: avoid;
            background: white;
        }

        .qr-card .school-name {
            font-size: 9pt;
            font-weight: bold;
            margin-bottom: 8px;
            color: #333;
        }

        .qr-card .qr-image {
            width: 140px;
            height: 140px;
            margin: 0 auto 10px;
            border: 1px solid #ddd;
            padding: 5px;
            background: white;
        }

        .qr-card .qr-image img {
            width: 100%;
            height: 100%;
            display: block;
        }

        .qr-card .student-info {
            margin-top: 8px;
            padding-top: 8px;
            border-top: 1px solid #ddd;
        }

        .qr-card .student-name {
            font-weight: bold;
            font-size: 10pt;
            margin-bottom: 3px;
            word-wrap: break-word;
        }

        .qr-card .student-nis {
            font-size: 9pt;
            color: #666;
            margin-bottom: 2px;
        }

        .qr-card .student-class {
            font-size: 8pt;
            color: #999;
            font-style: italic;
        }

        .qr-card .instruction {
            font-size: 7pt;
            color: #999;
            margin-top: 8px;
            padding-top: 5px;
            border-top: 1px dashed #ddd;
        }

        .page-break {
            page-break-after: always;
        }

        @media print {
            .qr-card {
                break-inside: avoid;
            }
        }
    </style>
</head>
<body>
    <div class="page-header">
        <h1>Kartu QR Absensi Mandiri</h1>
        <div class="subtitle">
            Kelas {{ $kelas->tingkat_kelas }}{{ $kelas->grup_kelas }}
            @if($kelas->tahunAjaran)
                - {{ $kelas->tahunAjaran->nama_tahun }} {{ $kelas->tahunAjaran->semester }}
            @endif
        </div>
        <div class="subtitle" style="font-size: 10pt; margin-top: 5px;">
            Total: {{ $students->count() }} Siswa
        </div>
    </div>

    <div class="cards-container">
        @foreach($students as $index => $item)
            <div class="qr-card">
                <div class="school-name">SIPPEL - SMP</div>

                <div class="qr-image">
                    <img src="{{ $item['qr_image'] }}" alt="QR Code {{ $item['siswa']->nis }}">
                </div>

                <div class="student-info">
                    <div class="student-name">{{ $item['siswa']->user->nama }}</div>
                    <div class="student-nis">NIS: {{ $item['siswa']->nis }}</div>
                    <div class="student-class">
                        Kelas {{ $kelas->tingkat_kelas }}{{ $kelas->grup_kelas }}
                    </div>
                </div>

                <div class="instruction">
                    Scan QR ini untuk absensi mandiri
                </div>
            </div>

            {{-- Page break after every 12 cards (3 columns x 4 rows) --}}
            @if(($index + 1) % 12 === 0 && !$loop->last)
                <div class="page-break"></div>
                <div class="page-header" style="margin-top: 20px;">
                    <h1>Kartu QR Absensi Mandiri</h1>
                    <div class="subtitle">
                        Kelas {{ $kelas->tingkat_kelas }}{{ $kelas->grup_kelas }}
                        @if($kelas->tahunAjaran)
                            - {{ $kelas->tahunAjaran->nama_tahun }} {{ $kelas->tahunAjaran->semester }}
                        @endif
                    </div>
                </div>
            @endif
        @endforeach
    </div>
</body>
</html>
