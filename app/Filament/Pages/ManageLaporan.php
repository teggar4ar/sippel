<?php

declare(strict_types=1);

namespace App\Filament\Pages;

use App\Models\Laporan;
use App\Models\TahunAjaran;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Auth;

final class ManageLaporan extends Page
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedChartBar;

    protected static ?string $navigationLabel = 'Kelola Laporan';

    protected static ?string $title = 'Kelola Laporan';

    protected static ?int $navigationSort = 1;

    protected string $view = 'filament.pages.manage-laporan';

    public static function getNavigationGroup(): string
    {
        return 'Laporan';
    }

    public static function canAccess(): bool
    {
        /** @var \App\Models\User|null $user */
        $user = Auth::user();

        return $user !== null && $user->hasRole('admin');
    }

    /**
     * @return array<string, mixed>
     */
    public function getViewData(): array
    {
        $activeTahunAjaran = TahunAjaran::where('status', true)->first();

        return [
            'totalLaporan' => Laporan::count(),
            'laporanTahunAktif' => $activeTahunAjaran
                ? Laporan::where('tahun_ajaran_id', $activeTahunAjaran->id)->count()
                : 0,
            'activeTahunAjaran' => $activeTahunAjaran,
            'recentLaporan' => Laporan::with(['siswa.user', 'mataPelajaran', 'tahunAjaran'])
                ->latest('updated_at')
                ->limit(10)
                ->get(),
        ];
    }

    /**
     * @return array<Action>
     */
    protected function getHeaderActions(): array
    {
        return [
            Action::make('calculateReports')
                ->label('Perbarui Laporan')
                ->icon(Heroicon::OutlinedArrowPath)
                ->color('primary')
                ->requiresConfirmation()
                ->modalHeading('Perbarui Laporan')
                ->modalDescription('Ini akan menghitung ulang semua statistik laporan. Proses ini mungkin memakan waktu beberapa menit.')
                ->modalSubmitActionLabel('Ya, Perbarui')
                ->form([
                    Select::make('tahun_ajaran_id')
                        ->label('Tahun Ajaran')
                        ->options(TahunAjaran::query()->orderByDesc('status')->orderByDesc('id')->pluck('nama_tahun', 'id'))
                        ->placeholder('Semua Tahun Ajaran Aktif')
                        ->helperText('Kosongkan untuk memproses tahun ajaran aktif saja')
                        ->native(false),
                ])
                ->action(function (array $data): void {
                    $options = [];
                    if (! empty($data['tahun_ajaran_id'])) {
                        $options['--tahun-ajaran'] = $data['tahun_ajaran_id'];
                    }

                    Artisan::call('reports:calculate', $options);

                    Notification::make()
                        ->title('Laporan Berhasil Diperbarui')
                        ->body('Statistik laporan telah dihitung ulang.')
                        ->success()
                        ->send();
                }),
        ];
    }
}
