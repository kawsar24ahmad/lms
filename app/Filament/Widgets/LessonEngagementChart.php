<?php

namespace App\Filament\Widgets;

use App\Filament\Widgets\Concerns\ScopesDashboardMetrics;
use Filament\Widgets\ChartWidget;
use Illuminate\Database\Eloquent\Builder;

class LessonEngagementChart extends ChartWidget
{
    use ScopesDashboardMetrics;

    protected static ?int $sort = 4;

    protected ?string $heading = 'Lesson engagement';

    protected ?string $description = 'How learners are progressing through course videos.';

    protected int|string|array $columnSpan = 1;

    protected ?string $pollingInterval = null;

    protected string $color = 'warning';

    protected function getData(): array
    {
        $completed = $this->scopedLessonProgress()->completed()->count();

        $inProgress = $this->scopedLessonProgress()
            ->where('is_completed', false)
            ->where(function (Builder $query): Builder {
                return $query
                    ->where('furthest_position', '>', 0)
                    ->orWhere('seconds_watched', '>', 0);
            })
            ->count();

        $expectedRecords = $this->scopedLessonCount() * $this->scopedEnrollments()->count();
        $notStarted = max(0, $expectedRecords - $completed - $inProgress);

        if (($completed + $inProgress + $notStarted) === 0) {
            return [];
        }

        return [
            'datasets' => [
                [
                    'data' => [$completed, $inProgress, $notStarted],
                    'backgroundColor' => ['#10b981', '#f59e0b', '#cbd5e1'],
                    'borderWidth' => 0,
                ],
            ],
            'labels' => ['Completed', 'In progress', 'Not started'],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    protected function getOptions(): array
    {
        return [
            'cutout' => '62%',
            'plugins' => [
                'legend' => [
                    'display' => true,
                    'position' => 'bottom',
                ],
            ],
        ];
    }

    protected function getType(): string
    {
        return 'doughnut';
    }
}
