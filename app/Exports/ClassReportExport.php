<?php

declare(strict_types=1);

namespace App\Exports;

use App\Enums\KehadiranStatus;
use App\Models\DetailAktivitas;
use App\Models\Kelas;
use App\Models\MataPelajaran;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithColumnFormatting;
use Maatwebsite\Excel\Concerns\WithCustomStartCell;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

/**
 * Exports class observation data as flat tabular rows.
 *
 * Layout:
 *   Row 1-2 : Letterhead (merged, center, bold)
 *   Row 3   : Empty
 *   Row 4-5 : Metadata (Kelas, Mapel, Guru, Wali Kelas)
 *   Row 6   : Empty
 *   Row 7   : Table header
 *   Row 8+  : Data rows
 */
final class ClassReportExport implements FromCollection, WithColumnFormatting, WithCustomStartCell, WithEvents, WithHeadings, WithMapping, WithStyles
{
    private const int HEADER_OFFSET = 6;

    private int $rowCounter = 0;

    private int $totalRows = 0;

    public function __construct(
        private readonly Kelas $kelas,
        private readonly ?MataPelajaran $mataPelajaran = null,
    ) {}

    /**
     * Query flat detail records — one row per student-per-activity.
     */
    public function collection(): Collection
    {
        $query = DetailAktivitas::query()
            ->with([
                'siswa.user',
                'aktivitasPembelajaran.mataPelajaran',
            ])
            ->whereHas(
                'aktivitasPembelajaran',
                fn ($q) => $q->where('kelas_id', $this->kelas->id),
            );

        if ($this->mataPelajaran instanceof MataPelajaran) {
            $query->whereHas(
                'aktivitasPembelajaran',
                fn ($q) => $q->where('mata_pelajaran_id', $this->mataPelajaran->id),
            );
        }

        // Order by date then student NIS for clean printout
        $records = $query
            ->join('aktivitas_pembelajaran', 'detail_aktivitas.aktivitas_pembelajaran_id', '=', 'aktivitas_pembelajaran.id')
            ->orderBy('aktivitas_pembelajaran.tanggal')
            ->orderBy('detail_aktivitas.siswa_id')
            ->select('detail_aktivitas.*')
            ->get();

        $this->totalRows = $records->count();

        return $records;
    }

    public function headings(): array
    {
        return ['No', 'NIS', 'Nama', 'Tanggal', 'Kehadiran', 'Partisipasi', 'Catatan Observasi'];
    }

    public function startCell(): string
    {
        return 'A'.(self::HEADER_OFFSET + 1);
    }

    /**
     * Map each DetailAktivitas row into flat columns.
     *
     * @param  DetailAktivitas  $detail
     */
    public function map($detail): array
    {
        $this->rowCounter++;

        $isHadir = $detail->kehadiran === KehadiranStatus::Hadir;

        return [
            $this->rowCounter,
            $detail->siswa?->nis ?? '-',
            $detail->siswa?->user?->name ?? '-',
            $detail->aktivitasPembelajaran?->tanggal?->format('d/m/Y') ?? '-',
            $detail->kehadiran->label(),
            $isHadir ? $detail->label_partisipasi : '-',
            $isHadir ? ($detail->catatan ?? '-') : '-',
        ];
    }

    /**
     * Format NIS column as text to preserve leading zeros.
     */
    public function columnFormats(): array
    {
        return [
            'B' => NumberFormat::FORMAT_TEXT,
        ];
    }

    public function styles(Worksheet $sheet): void
    {
        $dataStartRow = self::HEADER_OFFSET + 1; // Row 7 (header)
        $lastDataRow = $dataStartRow + $this->totalRows; // last data row
        $lastCol = 'G';

        // Auto-size columns
        foreach (range('A', $lastCol) as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        // Table header row (Row 7): bold, centered, light fill
        $sheet->getStyle("A{$dataStartRow}:{$lastCol}{$dataStartRow}")->applyFromArray([
            'font' => ['bold' => true],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'vertical' => Alignment::VERTICAL_CENTER,
            ],
            'fill' => [
                'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                'startColor' => ['rgb' => 'D9E2F3'],
            ],
        ]);

        // Full table range (header + data): outer border thick, inner border thin
        $tableRange = "A{$dataStartRow}:{$lastCol}{$lastDataRow}";

        $sheet->getStyle($tableRange)->applyFromArray([
            'borders' => [
                'allBorders' => ['borderStyle' => Border::BORDER_THIN],
                'outline' => ['borderStyle' => Border::BORDER_MEDIUM],
            ],
            'alignment' => [
                'vertical' => Alignment::VERTICAL_CENTER,
                'wrapText' => true,
            ],
        ]);

        // Catatan column wider
        $sheet->getColumnDimension('G')->setAutoSize(false);
        $sheet->getColumnDimension('G')->setWidth(35);
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event): void {
                $this->insertLetterhead($event->sheet->getDelegate());
            },
        ];
    }

    /**
     * Insert letterhead rows (1-5) and metadata above the table.
     */
    private function insertLetterhead(Worksheet $sheet): void
    {
        // Row 1: Title
        $sheet->setCellValue('A1', 'JURNAL OBSERVASI KELAS');
        $sheet->mergeCells('A1:G1');
        $sheet->getStyle('A1')->applyFromArray([
            'font' => ['bold' => true, 'size' => 14],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
        ]);

        // Row 2: School name
        $sheet->setCellValue('A2', 'SMPIT AL-ITQON');
        $sheet->mergeCells('A2:G2');
        $sheet->getStyle('A2')->applyFromArray([
            'font' => ['bold' => true, 'size' => 12],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
        ]);

        // Row 3: empty spacer (already empty)

        // Resolve metadata
        $kelasName = $this->kelas->tingkat_kelas . '-' . $this->kelas->grup_kelas;
        $tahunAjaran = $this->kelas->tahunAjaran?->nama_tahun;
        if ($tahunAjaran) {
            $kelasName .= " ({$tahunAjaran})";
        }

        $guruName = $this->mataPelajaran?->guru?->name ?? '-';
        $waliKelasName = $this->kelas->waliKelas?->name ?? '-';
        $mapelName = $this->mataPelajaran?->nama_mapel ?? '-';

        // Row 4: Kelas + Guru Pengampu
        $sheet->setCellValue('A4', 'Kelas');
        $sheet->setCellValue('C4', ': ' . $kelasName);
        $sheet->setCellValue('F4', 'Guru Pengampu');
        $sheet->setCellValue('G4', ': ' . $guruName);

        // Row 5: Mata Pelajaran + Wali Kelas
        $sheet->setCellValue('A5', 'Mata Pelajaran');
        $sheet->setCellValue('C5', ': ' . $mapelName);
        $sheet->setCellValue('F5', 'Wali Kelas');
        $sheet->setCellValue('G5', ': ' . $waliKelasName);

        // Style metadata labels bold
        $sheet->getStyle('A4:A5')->getFont()->setBold(true);
        $sheet->getStyle('F4:F5')->getFont()->setBold(true);

        // Row 6: empty spacer (already empty)
    }
}
