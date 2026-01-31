<?php

declare(strict_types=1);

namespace App\Filament\Pages;

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
use Symfony\Component\HttpFoundation\StreamedResponse;

final class ClassReport extends Page implements HasForms
{
    use InteractsWithForms;

    public ?int $kelas_id = null;

    public ?int $mata_pelajaran_id = null;

    public ?int $tahun_ajaran_id = null;

    public string $sort_by = 'nilai';

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
        $this->tahun_ajaran_id = $activeTahunAjaran?->id;
    }

    /**
     * Generate preview data for the selected class and subject.
     */
    public function generatePreview(): void
    {
        $this->validate([
            'kelas_id' => ['required', 'exists:kelas,id'],
            'mata_pelajaran_id' => ['required', 'exists:mata_pelajaran,id'],
            'tahun_ajaran_id' => ['required', 'exists:tahun_ajaran,id'],
        ]);

        $kelas = Kelas::with('waliKelas')->find($this->kelas_id);
        $mataPelajaran = MataPelajaran::with('guru')->find($this->mata_pelajaran_id);
        $tahunAjaran = TahunAjaran::find($this->tahun_ajaran_id);

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
            'kelas_id' => ['required', 'exists:kelas,id'],
            'mata_pelajaran_id' => ['required', 'exists:mata_pelajaran,id'],
            'tahun_ajaran_id' => ['required', 'exists:tahun_ajaran,id'],
        ]);

        $kelas = Kelas::with('waliKelas')->find($this->kelas_id);
        $mataPelajaran = MataPelajaran::with('guru')->find($this->mata_pelajaran_id);
        $tahunAjaran = TahunAjaran::find($this->tahun_ajaran_id);

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
     * @return array<int, Select>
     */
    protected function getFormSchema(): array
    {
        return [
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
                ->afterStateUpdated(function (): void {
                    $this->kelas_id = null;
                    $this->mata_pelajaran_id = null;
                    $this->previewData = null;
                }),

            Select::make('kelas_id')
                ->label('Pilih Kelas')
                ->native(false)
                ->options(function (Get $get): array {
                    /** @var int|null $tahunAjaranId */
                    $tahunAjaranId = $get('tahun_ajaran_id');
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
                    $this->mata_pelajaran_id = null;
                    $this->previewData = null;
                }),

            Select::make('mata_pelajaran_id')
                ->label('Mata Pelajaran')
                ->native(false)
                ->options(function (Get $get): array {
                    /** @var int|null $kelasId */
                    $kelasId = $get('kelas_id');
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

            Select::make('sort_by')
                ->label('Urutkan Berdasarkan')
                ->native(false)
                ->options([
                    'nilai' => 'Nilai (Tertinggi)',
                    'nilai_asc' => 'Nilai (Terendah)',
                    'kehadiran' => 'Kehadiran (Tertinggi)',
                    'nama' => 'Nama (A-Z)',
                ])
                ->default('nilai')
                ->live()
                ->afterStateUpdated(fn (): null => $this->previewData = null),
        ];
    }

    /**
     * Get sorted laporan data for the class.
     *
     * @return Collection<int, Laporan>
     */
    private function getLaporanData(Kelas $kelas, MataPelajaran $mataPelajaran, TahunAjaran $tahunAjaran): Collection
    {
        $laporanData = Laporan::where('tahun_ajaran_id', $tahunAjaran->id)
            ->where('mata_pelajaran_id', $mataPelajaran->id)
            ->whereHas('siswa', fn ($q) => $q->where('kelas_id', $kelas->id))
            ->with(['siswa.user', 'mataPelajaran'])
            ->get();

        // Apply sorting
        return match ($this->sort_by) {
            'nilai' => $laporanData->sortByDesc('rata_nilai')->values(),
            'nilai_asc' => $laporanData->sortBy('rata_nilai')->values(),
            'kehadiran' => $laporanData->sortByDesc('rata_kehadiran')->values(),
            'nama' => $laporanData->sortBy(function (Laporan $l): string {
                /** @var \App\Models\Siswa|null $siswa */
                $siswa = $l->siswa;
                /** @var User|null $user */
                $user = $siswa?->user;

                return $user !== null ? $user->name : '';
            })->values(),
            default => $laporanData->sortByDesc('rata_nilai')->values(),
        };
    }
}
