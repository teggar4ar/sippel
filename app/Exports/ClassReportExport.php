<?php

declare(strict_types=1);

namespace App\Exports;

use App\Models\DetailAktivitas;
use App\Models\Kelas;
use App\Models\MataPelajaran;
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
        private readonly ?MataPelajaran $mataPelajaran = null,
    ) {}

    public function collection()
    {
        // Get all detail aktivitas for the class, scoped to the selected subject when provided
        $query = DetailAktivitas::query()
            ->with([
                'siswa.user',
                'siswa.kelas',
                'aktivitasPembelajaran',
                'aktivitasPembelajaran.mataPelajaran' => fn ($q) => $q->withTrashed()->with('guru'),
            ])
            ->whereHas('aktivitasPembelajaran', fn ($q) => $q->where('kelas_id', $this->kelas->id))
            ->orderBy('created_at');

        // Scope to a specific subject when one is selected
        if ($this->mataPelajaran instanceof MataPelajaran) {
            $query->whereHas(
                'aktivitasPembelajaran',
                fn ($q) => $q->where('mata_pelajaran_id', $this->mataPelajaran->id),
            );
        }

        if ($this->startDate && $this->endDate) {
            $query->whereHas('aktivitasPembelajaran', function ($q): void {
                $q->whereDate('tanggal', '>=', $this->startDate)
                    ->whereDate('tanggal', '<=', $this->endDate);
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

        // Get unique students who appear in the activities (respects kelas scope above)
        $studentIds = $records->pluck('siswa_id')->unique();
        $students = Siswa::whereIn('id', $studentIds)
            ->with('user')
            ->orderBy('nis')
            ->get();

        // Resolve subject and teacher from the injected MataPelajaran (most reliable).
        // Fall back to the first activity record only when no subject was pre-selected.
        $subjectName = $this->mataPelajaran?->nama_mapel;
        $teacherName = $this->mataPelajaran?->guru?->name;

        if ($subjectName === null || $teacherName === null) {
            $firstOverall = $records->first();
            $subjectName ??= $firstOverall?->aktivitasPembelajaran?->mataPelajaran?->nama_mapel;
            $teacherName ??= $firstOverall?->aktivitasPembelajaran?->mataPelajaran?->guru?->name;
        }

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

            $row = [
                $rowNumber,
                $student->nis,
                $student->user->name,
                $this->kelas->tingkat_kelas.'-'.$this->kelas->grup_kelas,
                $subjectName ?? '-',
                $teacherName ?? '-',
                $this->kelas->waliKelas?->name ?? '-',
            ];

            // Fill date columns
            foreach ($this->dates as $date) {
                $record = $records->first(fn ($r): bool => $r->siswa_id === $student->id
                    && $r->aktivitasPembelajaran?->tanggal?->isSameDay($date));

                $row[] = $record ? $record->kehadiran->label() : '';
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
