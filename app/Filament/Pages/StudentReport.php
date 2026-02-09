<?php

declare(strict_types=1);

namespace App\Filament\Pages;

use App\Models\Laporan;
use App\Models\Siswa;
use App\Models\TahunAjaran;
use App\Models\User;
use BackedEnum;
use Barryvdh\DomPDF\Facade\Pdf;
use Filament\Forms\Components\Select;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\StreamedResponse;

final class StudentReport extends Page implements HasForms
{
    use InteractsWithForms;

    public ?int $siswa_id = null;

    public ?int $tahun_ajaran_id = null;

    /**
     * @var array<string, mixed>|null
     */
    public ?array $previewData = null;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedDocumentText;

    protected static ?string $navigationLabel = 'Laporan Siswa';

    protected static ?string $title = 'Cetak Laporan Siswa';

    protected static ?int $navigationSort = 2;

    protected string $view = 'filament.pages.student-report';

    public static function getNavigationGroup(): string
    {
        return 'Laporan';
    }

    public static function canAccess(): bool
    {
        /** @var User|null $user */
        $user = Auth::user();

        return $user !== null && $user->hasRole('admin');
    }

    public function mount(): void
    {
        // Set default to active academic year
        $activeTahunAjaran = TahunAjaran::where('status', true)->first();
        $this->tahun_ajaran_id = $activeTahunAjaran?->id;
    }

    /**
     * Generate preview data for the selected student and academic year.
     */
    public function generatePreview(): void
    {
        $this->validate([
            'siswa_id' => ['required', 'exists:siswa,id'],
            'tahun_ajaran_id' => ['required', 'exists:tahun_ajaran,id'],
        ]);

        $siswa = Siswa::with(['user', 'kelas.waliKelas'])->find($this->siswa_id);
        $tahunAjaran = TahunAjaran::find($this->tahun_ajaran_id);

        if (! $siswa || ! $tahunAjaran) {
            Notification::make()
                ->title('Data tidak ditemukan')
                ->danger()
                ->send();

            return;
        }

        $laporanData = Laporan::where('siswa_id', $siswa->id)
            ->where('tahun_ajaran_id', $tahunAjaran->id)
            ->with('mataPelajaran')
            ->get();

        $this->previewData = [
            'siswa' => $siswa,
            'tahunAjaran' => $tahunAjaran,
            'laporanData' => $laporanData,
            'hasData' => $laporanData->isNotEmpty(),
        ];
    }

    /**
     * Download PDF report for the selected student.
     */
    public function downloadPdf(): ?StreamedResponse
    {
        $this->validate([
            'siswa_id' => ['required', 'exists:siswa,id'],
            'tahun_ajaran_id' => ['required', 'exists:tahun_ajaran,id'],
        ]);

        $siswa = Siswa::with(['user', 'kelas.waliKelas'])->find($this->siswa_id);
        $tahunAjaran = TahunAjaran::find($this->tahun_ajaran_id);

        if (! $siswa || ! $tahunAjaran) {
            Notification::make()
                ->title('Data tidak ditemukan')
                ->danger()
                ->send();

            return null;
        }

        $laporanData = Laporan::where('siswa_id', $siswa->id)
            ->where('tahun_ajaran_id', $tahunAjaran->id)
            ->with('mataPelajaran')
            ->get();

        if ($laporanData->isEmpty()) {
            Notification::make()
                ->title('Tidak ada data laporan')
                ->body('Siswa ini belum memiliki data laporan untuk tahun ajaran yang dipilih.')
                ->warning()
                ->send();

            return null;
        }

        $pdf = Pdf::loadView('reports.student-report', [
            'siswa' => $siswa,
            'tahunAjaran' => $tahunAjaran,
            'laporanData' => $laporanData,
        ]);

        $pdf->setPaper('A4', 'portrait');

        // Sanitize filename - replace "/" with "-" to avoid invalid filename characters
        $sanitizedTahun = str_replace(['/', '\\'], '-', $tahunAjaran->nama_tahun);
        $filename = 'laporan-siswa-'.$siswa->nis.'-'.$sanitizedTahun.'.pdf';

        return response()->streamDownload(function () use ($pdf): void {
            echo $pdf->output();
        }, $filename);
    }

    /**
     * @return array<int, Select>
     */
    protected function getFormSchema(): array
    {
        return [
            Select::make('siswa_id')
                ->label('Pilih Siswa')
                ->native(false)
                ->options(fn () => Siswa::with('user')
                    ->get()
                    ->mapWithKeys(function (Siswa $siswa): array {
                        /** @var User|null $user */
                        $user = $siswa->user;
                        $name = $user !== null ? $user->name : 'Unknown';

                        return [
                            $siswa->id => $name.' ('.$siswa->nis.')',
                        ];
                    }))
                ->searchable()
                ->required()
                ->live()
                ->afterStateUpdated(fn (): null => $this->previewData = null),

            Select::make('tahun_ajaran_id')
                ->label('Tahun Ajaran')
                ->native(false)
                ->options(
                    TahunAjaran::orderByDesc('status')
                        ->orderByDesc('id')
                        ->get()
                        ->mapWithKeys(fn (TahunAjaran $ta): array => [
                            $ta->id => $ta->nama_tahun.' - '.$ta->semester.($ta->status ? ' (Aktif)' : ''),
                        ])
                )
                ->required()
                ->live()
                ->afterStateUpdated(fn (): null => $this->previewData = null),
        ];
    }
}
