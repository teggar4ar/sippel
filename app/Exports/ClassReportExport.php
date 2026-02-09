<?php

declare(strict_types=1);

namespace App\Exports;

use App\Models\DetailAktivitas;
use App\Models\Kelas;
use App\Models\Siswa;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

final class ClassReportExport implements FromCollection, WithEvents, WithStyles
{
    private array $dates = [];

    private int $fixedColumns = 7;

    public function __construct(
        private readonly Kelas $kelas,
        private readonly ?Carbon $startDate = null,
        private readonly ?Carbon $endDate = null,
    ) {}

    public function collection()
    {
        // Get all detail aktivitas for the class
        $query = DetailAktivitas::query()
            ->with([
                'siswa.user',
                'siswa.kelas',
                'aktivitasPembelajaran',
                'aktivitasPembelajaran.mataPelajaran.guru',
            ])
            ->whereHas('siswa', fn ($q) => $q->where('kelas_id', $this->kelas->id))
            ->orderBy('created_at');

        if ($this->startDate && $this->endDate) {
            $query->whereHas('aktivitasPembelajaran', function ($q): void {
                $q->whereBetween('tanggal', [$this->startDate, $this->endDate]);
            });
        }

        $records = $query->get();

        // Extract unique dates and sort
        $this->dates = $records->pluck('aktivitasPembelajaran.tanggal')
            ->filter()
            ->unique()
            ->sort()
            ->values()
            ->toArray();

        // Get unique students
        $students = Siswa::where('kelas_id', $this->kelas->id)
            ->with('user')
            ->orderBy('nis')
            ->get();

        // Build pivot data
        $pivotData = [];

        // Header row 1 (dates as merged headers)
        $header1 = ['No', 'NIS', 'Nama', 'Kelas', 'Mata Pelajaran', 'Guru Pengampu', 'Wali Kelas'];
        foreach ($this->dates as $date) {
            $header1[] = $date->format('d/m/Y');
            $header1[] = '';
            $header1[] = '';
        }
        $pivotData[] = $header1;

        // Header row 2 (subcolumns)
        $header2 = ['', '', '', '', '', '', ''];
        foreach ($this->dates as $date) {
            $header2[] = 'Status kehadiran';
            $header2[] = 'Nilai';
            $header2[] = 'Partisipasi';
        }
        $pivotData[] = $header2;

        // Data rows
        $rowNumber = 0;
        foreach ($students as $student) {
            $rowNumber++;

            // Get first record to extract common data
            $firstRecord = $records->firstWhere('siswa_id', $student->id);

            $row = [
                $rowNumber,
                $student->nis,
                $student->user->name,
                $this->kelas->tingkat_kelas.'-'.$this->kelas->grup_kelas,
                $firstRecord?->aktivitasPembelajaran?->mataPelajaran?->nama_mapel ?? '-',
                $firstRecord?->aktivitasPembelajaran?->mataPelajaran?->guru?->name ?? '-',
                $this->kelas->waliKelas?->name ?? '-',
            ];

            // Fill date columns
            foreach ($this->dates as $date) {
                $record = $records->first(fn ($r): bool => $r->siswa_id == $student->id
                    && $r->aktivitasPembelajaran?->tanggal?->isSameDay($date));

                $row[] = $record ? ucfirst((string) $record->kehadiran) : '';
                $row[] = $record?->nilai ?? '';
                $row[] = $record?->partisipasi ?? '';
            }

            $pivotData[] = $row;
        }

        return new Collection($pivotData);
    }

    public function styles(Worksheet $sheet)
    {
        // Auto-size all columns
        foreach (range('A', $sheet->getHighestColumn()) as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        // Style header rows
        $sheet->getStyle('A1:'.$sheet->getHighestColumn().'2')->applyFromArray([
            'font' => ['bold' => true],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'vertical' => Alignment::VERTICAL_CENTER,
            ],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => 'E2E8F0'],
            ],
            'borders' => [
                'allBorders' => [
                    'borderStyle' => Border::BORDER_THIN,
                ],
            ],
        ]);

        // Style data rows
        $lastRow = $sheet->getHighestRow();
        $sheet->getStyle('A3:'.$sheet->getHighestColumn().$lastRow)->applyFromArray([
            'borders' => [
                'allBorders' => [
                    'borderStyle' => Border::BORDER_THIN,
                ],
            ],
            'alignment' => [
                'vertical' => Alignment::VERTICAL_CENTER,
            ],
        ]);

        return [];
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event): void {
                $sheet = $event->sheet;

                // Merge cells for fixed column headers (row 1 and 2)
                $fixedCols = ['A', 'B', 'C', 'D', 'E', 'F', 'G'];
                foreach ($fixedCols as $col) {
                    $sheet->mergeCells($col.'1:'.$col.'2');
                }

                // Merge cells for each date (3 columns per date)
                $colIndex = $this->fixedColumns; // Start after fixed columns
                foreach ($this->dates as $date) {
                    $startCol = $this->columnLetter($colIndex);
                    $endCol = $this->columnLetter($colIndex + 2);
                    $sheet->mergeCells($startCol.'1:'.$endCol.'1');
                    $colIndex += 3;
                }

                // Center align date group headers
                $sheet->getStyle('H1:'.$sheet->getHighestColumn().'1')->getAlignment()
                    ->setHorizontal(Alignment::HORIZONTAL_CENTER);
            },
        ];
    }

    private function columnLetter(int $index): string
    {
        $letter = '';
        while ($index >= 0) {
            $letter = chr(65 + ($index % 26)).$letter;
            $index = (int) ($index / 26) - 1;
        }

        return $letter;
    }
}
