<?php

declare(strict_types=1);

namespace App\Filament\Pages;

use App\Exports\ClassReportExport;
use App\Models\AktivitasPembelajaran;
use App\Models\Kelas;
use App\Models\Laporan;
use App\Models\MataPelajaran;
use App\Models\TahunAjaran;
use App\Models\User;
use BackedEnum;
use Barryvdh\DomPDF\Facade\Pdf;
use Filament\Forms\Components\Select;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Support\Icons\Heroicon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\StreamedResponse;

final class ClassReport extends Page implements HasForms
{
    use InteractsWithForms;

    public ?int $kelasId = null;

    public ?int $mataPelajaranId = null;

    public ?int $tahunAjaranId = null;

    public string $sortBy = 'kehadiran';

    /**
     * @var array<string, mixed>|null
     */
    public ?array $previewData = null;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedUserGroup;

    protected static ?string $navigationLabel = 'Laporan Kelas';

    protected static ?string $title = 'Cetak Laporan Kelas';

    protected static ?int $navigationSort = 3;

    protected string $view = 'filament.pages.class-report';

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
        $this->tahunAjaranId = $activeTahunAjaran?->id;
    }

    /**
     * Generate preview data for the selected class and subject.
     */
    public function generatePreview(): void
    {
        $this->validate([
            'kelasId' => ['required', 'exists:kelas,id'],
            'mataPelajaranId' => ['required', 'exists:mata_pelajaran,id'],
            'tahunAjaranId' => ['required', 'exists:tahun_ajaran,id'],
        ]);

        $kelas = Kelas::with('waliKelas')->find($this->kelasId);
        $mataPelajaran = MataPelajaran::with('guru')->find($this->mataPelajaranId);
        $tahunAjaran = TahunAjaran::find($this->tahunAjaranId);

        if (! $kelas || ! $mataPelajaran || ! $tahunAjaran) {
            Notification::make()
                ->title('Data tidak ditemukan')
                ->danger()
                ->send();

            return;
        }

        $laporanData = $this->getLaporanData($kelas, $mataPelajaran, $tahunAjaran);

        $this->previewData = [
            'kelas' => $kelas,
            'mataPelajaran' => $mataPelajaran,
            'tahunAjaran' => $tahunAjaran,
            'laporanData' => $laporanData,
            'hasData' => $laporanData->isNotEmpty(),
        ];
    }

    /**
     * Download PDF report for the selected class.
     */
    public function downloadPdf(): ?StreamedResponse
    {
        $this->validate([
            'kelasId' => ['required', 'exists:kelas,id'],
            'mataPelajaranId' => ['required', 'exists:mata_pelajaran,id'],
            'tahunAjaranId' => ['required', 'exists:tahun_ajaran,id'],
        ]);

        $kelas = Kelas::with('waliKelas')->find($this->kelasId);
        $mataPelajaran = MataPelajaran::with('guru')->find($this->mataPelajaranId);
        $tahunAjaran = TahunAjaran::find($this->tahunAjaranId);

        if (! $kelas || ! $mataPelajaran || ! $tahunAjaran) {
            Notification::make()
                ->title('Data tidak ditemukan')
                ->danger()
                ->send();

            return null;
        }

        $laporanData = $this->getLaporanData($kelas, $mataPelajaran, $tahunAjaran);

        if ($laporanData->isEmpty()) {
            Notification::make()
                ->title('Tidak ada data laporan')
                ->body('Kelas ini belum memiliki data laporan untuk mata pelajaran dan tahun ajaran yang dipilih.')
                ->warning()
                ->send();

            return null;
        }

        $pdf = Pdf::loadView('reports.class-report', [
            'kelas' => $kelas,
            'mataPelajaran' => $mataPelajaran,
            'tahunAjaran' => $tahunAjaran,
            'laporanData' => $laporanData,
        ]);

        $pdf->setPaper('A4', 'portrait');

        // Sanitize filename
        $sanitizedTahun = str_replace(['/', '\\'], '-', $tahunAjaran->nama_tahun);
        $filename = 'laporan-kelas-'.$kelas->tingkat_kelas.$kelas->grup_kelas.'-'.$sanitizedTahun.'.pdf';

        return response()->streamDownload(function () use ($pdf): void {
            echo $pdf->output();
        }, $filename);
    }

    /**
     * Export Excel report for the selected class.
     */
    public function exportExcel(): mixed
    {
        $this->validate([
            'kelasId' => ['required', 'exists:kelas,id'],
            'mataPelajaranId' => ['required', 'exists:mata_pelajaran,id'],
            'tahunAjaranId' => ['required', 'exists:tahun_ajaran,id'],
        ]);

        $resolved = $this->resolveKelasForExcelExport();
        if ($resolved === null) {
            return null;
        }

        [$kelas, $tahunAjaran, $mataPelajaran] = $resolved;

        $sanitizedTahun = str_replace(['/', '\\'], '-', $tahunAjaran->nama_tahun);
        $filename = sprintf(
            'laporan-kelas-%s%s-%s.xlsx',
            $kelas->tingkat_kelas,
            $kelas->grup_kelas,
            $sanitizedTahun
        );

        return Excel::download(new ClassReportExport($kelas, $mataPelajaran), $filename);
    }

    /**
     * @return array<int, Select>
     */
    protected function getFormSchema(): array
    {
        return [
            Select::make('tahunAjaranId')
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
                ->afterStateUpdated(function (): void {
                    $this->kelasId = null;
                    $this->mataPelajaranId = null;
                    $this->previewData = null;
                }),

            Select::make('kelasId')
                ->label('Pilih Kelas')
                ->native(false)
                ->options(function (Get $get): array {
                    /** @var int|null $tahunAjaranId */
                    $tahunAjaranId = $get('tahunAjaranId');
                    if (! $tahunAjaranId) {
                        return [];
                    }

                    return Kelas::where('tahun_ajaran_id', $tahunAjaranId)
                        ->get()
                        ->mapWithKeys(fn (Kelas $kelas): array => [
                            $kelas->id => $kelas->tingkat_kelas.'-'.$kelas->grup_kelas,
                        ])
                        ->toArray();
                })
                ->searchable()
                ->required()
                ->live()
                ->afterStateUpdated(function (): void {
                    $this->mataPelajaranId = null;
                    $this->previewData = null;
                }),

            Select::make('mataPelajaranId')
                ->label('Mata Pelajaran')
                ->native(false)
                ->options(function (Get $get): array {
                    /** @var int|null $kelasId */
                    $kelasId = $get('kelasId');
                    if (! $kelasId) {
                        return [];
                    }

                    return MataPelajaran::where('kelas_id', $kelasId)
                        ->get()
                        ->mapWithKeys(fn (MataPelajaran $mapel): array => [
                            $mapel->id => $mapel->nama_mapel,
                        ])
                        ->toArray();
                })
                ->searchable()
                ->required()
                ->live()
                ->afterStateUpdated(fn (): null => $this->previewData = null),

            Select::make('sortBy')
                ->label('Urutkan Berdasarkan')
                ->native(false)
                ->options([
                    'kehadiran' => 'Kehadiran (Tertinggi)',
                    'nama' => 'Nama (A-Z)',
                ])
                ->default('kehadiran')
                ->live()
                ->afterStateUpdated(fn (): null => $this->previewData = null),
        ];
    }

    /**
     * Load and authorise data needed for the Excel export.
     * Sends a Filament notification and returns null on any validation/auth failure.
     *
     * @return array{0: Kelas, 1: TahunAjaran, 2: MataPelajaran|null}|null
     */
    private function resolveKelasForExcelExport(): ?array
    {
        $kelas = Kelas::find($this->kelasId);
        $tahunAjaran = TahunAjaran::find($this->tahunAjaranId);

        if (! $kelas || ! $tahunAjaran) {
            Notification::make()
                ->title('Data tidak ditemukan')
                ->danger()
                ->send();

            return null;
        }

        if (! Gate::allows('export-class-report', $kelas)) {
            Notification::make()
                ->title('Tidak memiliki akses')
                ->body('Anda tidak memiliki izin untuk mengekspor laporan kelas ini.')
                ->danger()
                ->send();

            return null;
        }

        $mataPelajaran = MataPelajaran::with('guru')->find($this->mataPelajaranId);

        // Guard using the same data source the export queries (DetailAktivitas),
        // not Laporan aggregates which require kelasHistory to be complete.
        $hasActivities = AktivitasPembelajaran::where('kelas_id', $kelas->id)
            ->when($mataPelajaran, fn ($q) => $q->where('mata_pelajaran_id', $mataPelajaran->id))
            ->exists();

        if (! $hasActivities) {
            Notification::make()
                ->title('Tidak ada data aktivitas')
                ->body('Kelas ini belum memiliki data aktivitas untuk mata pelajaran yang dipilih.')
                ->warning()
                ->send();

            return null;
        }

        return [$kelas, $tahunAjaran, $mataPelajaran];
    }

    /**
     * Get sorted laporan data for the class.
     *
     * @return Collection<int, Laporan>
     */
    private function getLaporanData(Kelas $kelas, MataPelajaran $mataPelajaran, TahunAjaran $tahunAjaran): Collection
    {
        // Use kelasHistory so that previous-semester classes (where siswa.kelas_id
        // has already been updated to the new class) still return their data.
        $laporanData = Laporan::where('tahun_ajaran_id', $tahunAjaran->id)
            ->where('mata_pelajaran_id', $mataPelajaran->id)
            ->whereHas(
                'siswa.kelasHistory',
                fn ($q) => $q
                    ->where('tahun_ajaran_id', $tahunAjaran->id)
                    ->where('kelas_id', $kelas->id),
            )
            ->with(['siswa.user', 'mataPelajaran'])
            ->get();

        // Apply sorting
        return match ($this->sortBy) {
            'kehadiran' => $laporanData->sortByDesc('rata_kehadiran')->values(),
            'nama' => $laporanData->sortBy(function (Laporan $l): string {
                /** @var \App\Models\Siswa|null $siswa */
                $siswa = $l->siswa;
                /** @var User|null $user */
                $user = $siswa?->user;

                return $user !== null ? $user->name : '';
            })->values(),
            default => $laporanData->sortByDesc('rata_kehadiran')->values(),
        };
    }
}
