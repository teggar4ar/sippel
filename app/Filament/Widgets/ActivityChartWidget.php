<?php

declare(strict_types=1);

namespace App\Filament\Widgets;

use App\Models\AktivitasPembelajaran;
use App\Models\User;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;

final class ActivityChartWidget extends ChartWidget
{
    protected static ?int $sort = 3;

    protected ?string $pollingInterval = '60s';

    protected int|string|array $columnSpan = 'full';

    protected ?string $maxHeight = '300px';

    public static function canView(): bool
    {
        /** @var User|null $user */
        $user = Auth::user();

        return $user?->hasRole('admin') ?? false;
    }

    public function getHeading(): string
    {
        return 'Aktivitas Pembelajaran (7 Hari Terakhir)';
    }

    protected function getData(): array
    {
        $data = Cache::remember('admin_activity_chart', 300, function (): array {
            $dates = collect();
            $counts = collect();

            // Get last 7 days
            for ($i = 6; $i >= 0; $i--) {
                $date = Carbon::now()->subDays($i);
                $dates->push($date->format('d M'));

                $count = AktivitasPembelajaran::whereDate('tanggal', $date->toDateString())->count();
                $counts->push($count);
            }

            return [
                'labels' => $dates->toArray(),
                'counts' => $counts->toArray(),
            ];
        });

        return [
            'datasets' => [
                [
                    'label' => 'Jumlah Aktivitas',
                    'data' => $data['counts'],
                    'backgroundColor' => 'rgba(59, 130, 246, 0.5)',
                    'borderColor' => 'rgb(59, 130, 246)',
                    'borderWidth' => 2,
                    'fill' => true,
                    'tension' => 0.3,
                ],
            ],
            'labels' => $data['labels'],
        ];
    }

    protected function getType(): string
    {
        return 'line';
    }

    protected function getOptions(): array
    {
        return [
            'plugins' => [
                'legend' => [
                    'display' => true,
                    'position' => 'bottom',
                ],
            ],
            'scales' => [
                'y' => [
                    'beginAtZero' => true,
                    'ticks' => [
                        'stepSize' => 1,
                    ],
                ],
            ],
        ];
    }
}
