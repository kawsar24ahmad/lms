<?php

namespace App\Filament\Widgets;

use App\Filament\Widgets\Concerns\ScopesDashboardMetrics;
use Filament\Widgets\ChartWidget;

class EnrollmentTrendChart extends ChartWidget
{
    use ScopesDashboardMetrics;

    protected static ?int $sort = 2;

    protected ?string $heading = 'Enrollment & completion trend';

    protected ?string $description = 'Monthly enrollments and course completions over the last 12 months.';

    protected int|string|array $columnSpan = 'full';

    protected ?string $pollingInterval = null;

    protected string $color = 'warning';

    protected function getData(): array
    {
        $months = collect(range(11, 0))
            ->map(fn (int $offset) => now()->startOfMonth()->subMonths($offset));

        $start = now()->startOfMonth()->subMonths(11);

        $enrollments = $this->monthlyCounts($this->scopedEnrollments(), 'created_at', $start);
        $completions = $this->monthlyCounts(
            $this->scopedEnrollments()->completed(),
            'completed_at',
            $start,
        );

        return [
            'datasets' => [
                [
                    'label' => 'Enrollments',
                    'data' => $months
                        ->map(fn ($month): int => (int) ($enrollments[$month->format('Y-m')] ?? 0))
                        ->all(),
                    'borderColor' => '#f59e0b',
                    'backgroundColor' => 'rgba(245, 158, 11, 0.12)',
                    'fill' => true,
                    'tension' => 0.35,
                ],
                [
                    'label' => 'Completions',
                    'data' => $months
                        ->map(fn ($month): int => (int) ($completions[$month->format('Y-m')] ?? 0))
                        ->all(),
                    'borderColor' => '#10b981',
                    'backgroundColor' => 'rgba(16, 185, 129, 0.12)',
                    'fill' => true,
                    'tension' => 0.35,
                ],
            ],
            'labels' => $months
                ->map(fn ($month): string => $month->format('M Y'))
                ->all(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    protected function getOptions(): array
    {
        return [
            'maintainAspectRatio' => false,
            'plugins' => [
                'legend' => [
                    'display' => true,
                    'position' => 'top',
                ],
            ],
            'scales' => [
                'y' => [
                    'beginAtZero' => true,
                    'ticks' => [
                        'precision' => 0,
                    ],
                ],
            ],
        ];
    }

    protected function getType(): string
    {
        return 'line';
    }
}
