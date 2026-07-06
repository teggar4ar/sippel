<?php

declare(strict_types=1);

namespace App\Filament\Widgets;

use App\Models\AktivitasPembelajaran;
use App\Models\Kelas;
use App\Models\MataPelajaran;
use App\Models\TahunAjaran;
use App\Models\User;
use App\Services\AdminDashboardCacheService;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;

final class SystemStatisticsWidget extends StatsOverviewWidget
{
    protected static ?int $sort = 1;

    protected ?string $pollingInterval = '60s';

    public static function canView(): bool
    {
        /** @var User|null $user */
        $user = Auth::user();

        return $user?->hasRole('admin') ?? false;
    }

    protected function getStats(): array
    {
        // Cache stats for 5 minutes to improve performance
        $cacheVersion = app(AdminDashboardCacheService::class)->version();
        $stats = Cache::remember('admin_dashboard_stats_v2_'.$cacheVersion, 300, function (): array {
            $activeTahunAjaran = TahunAjaran::where('status', true)->first();

            return [
                'admin_count' => User::role('admin')->count(),
                'teacher_count' => User::role('teacher')->count(),
                'student_count' => User::role('student')->count(),
                'total_classes' => $activeTahunAjaran
                    ? Kelas::where('tahun_ajaran_id', $activeTahunAjaran->id)->count()
                    : 0,
                'total_subjects' => $activeTahunAjaran
                    ? MataPelajaran::whereHas('kelas', fn ($q) => $q->where('tahun_ajaran_id', $activeTahunAjaran->id))->count()
                    : 0,
                'activities_this_month' => AktivitasPembelajaran::whereMonth('tanggal', now()->month)
                    ->whereYear('tanggal', now()->year)
                    ->count(),
                'tahun_ajaran_name' => $activeTahunAjaran->nama_tahun ?? '-',
            ];
        });

        return [
            Stat::make('Operator', (string) $stats['admin_count'])
                ->description('Pengguna operator terdaftar')
                ->descriptionIcon('heroicon-m-shield-check')
                ->color('danger'),

            Stat::make('Guru', (string) $stats['teacher_count'])
                ->description('Guru terdaftar')
                ->descriptionIcon('heroicon-m-academic-cap')
                ->color('warning'),

            Stat::make('Siswa', (string) $stats['student_count'])
                ->description('Siswa terdaftar')
                ->descriptionIcon('heroicon-m-user-group')
                ->color('success'),

            Stat::make('Kelas', (string) $stats['total_classes'])
                ->description('Tahun ajaran: '.$stats['tahun_ajaran_name'])
                ->descriptionIcon('heroicon-m-building-library')
                ->color('info'),

            Stat::make('Mata Pelajaran', (string) $stats['total_subjects'])
                ->description('Tahun ajaran aktif')
                ->descriptionIcon('heroicon-m-book-open')
                ->color('primary'),

            Stat::make('Aktivitas Bulan Ini', (string) $stats['activities_this_month'])
                ->description('Pembelajaran tercatat')
                ->descriptionIcon('heroicon-m-clipboard-document-list')
                ->color('success'),
        ];
    }
}
