<?php

namespace App\Filament\Widgets;

use App\Filament\Widgets\Concerns\ScopesDashboardMetrics;
use Filament\Widgets\ChartWidget;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;

class CoursePerformanceChart extends ChartWidget
{
    use ScopesDashboardMetrics;

    protected static ?int $sort = 3;

    protected ?string $heading = 'Top course performance';

    protected ?string $description = 'Enrollments and completions for your busiest courses.';

    protected int|string|array $columnSpan = 1;

    protected ?string $pollingInterval = null;

    protected string $color = 'warning';

    protected function getData(): array
    {
        $courses = $this->scopedCourses()
            ->withCount([
                'enrollments',
                'enrollments as completed_enrollments_count' => fn (Builder $query): Builder => $query->completed(),
            ])
            ->whereHas('enrollments')
            ->orderByDesc('enrollments_count')
            ->limit(6)
            ->get();

        return [
            'datasets' => [
                [
                    'label' => 'Enrolled',
                    'data' => $courses->pluck('enrollments_count')->map(fn ($count): int => (int) $count)->all(),
                    'backgroundColor' => '#f59e0b',
                    'borderRadius' => 4,
                ],
                [
                    'label' => 'Completed',
                    'data' => $courses->pluck('completed_enrollments_count')->map(fn ($count): int => (int) $count)->all(),
                    'backgroundColor' => '#10b981',
                    'borderRadius' => 4,
                ],
            ],
            'labels' => $courses
                ->map(fn ($course): string => Str::limit($course->title, 30))
                ->all(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    protected function getOptions(): array
    {
        return [
            'indexAxis' => 'y',
            'maintainAspectRatio' => false,
            'plugins' => [
                'legend' => [
                    'display' => true,
                    'position' => 'top',
                ],
            ],
            'scales' => [
                'x' => [
                    'beginAtZero' => true,
                    'ticks' => [
                        'precision' => 0,
                    ],
                ],
                'y' => [
                    'ticks' => [
                        'autoSkip' => false,
                    ],
                ],
            ],
        ];
    }

    protected function getType(): string
    {
        return 'bar';
    }
}
