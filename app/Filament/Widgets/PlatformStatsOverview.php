<?php

namespace App\Filament\Widgets;

use App\Filament\Widgets\Concerns\ScopesDashboardMetrics;
use Filament\Support\Icons\Heroicon;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class PlatformStatsOverview extends StatsOverviewWidget
{
    use ScopesDashboardMetrics;

    protected static ?int $sort = 1;

    protected int|string|array $columnSpan = 'full';

    protected ?string $pollingInterval = null;

    /**
     * @return array<Stat>
     */
    protected function getStats(): array
    {
        $isAdmin = auth()->user()?->isAdmin() ?? false;

        $studentQuery = $this->scopedStudents();
        $studentCount = $studentQuery->count();
        $studentChart = $this->lastSevenDays(
            $isAdmin ? $studentQuery : $this->scopedEnrollments(),
            'created_at',
        );

        $instructorCount = $this->instructorCount();

        $courseCount = $this->scopedCourses()->count();
        $publishedCourseCount = $this->scopedCourses()->published()->count();

        $enrollmentCount = $this->scopedEnrollments()->count();
        $activeEnrollmentCount = $this->scopedEnrollments()->active()->count();
        $completedEnrollmentCount = $this->scopedEnrollments()->completed()->count();
        $completionRate = $enrollmentCount > 0
            ? (int) round($completedEnrollmentCount / $enrollmentCount * 100)
            : 0;

        $lessonProgress = $this->scopedLessonProgress();
        $lessonsCompleted = $lessonProgress->completed()->count();
        $averageWatch = $this->averageWatchPercentage();

        $certificates = $this->scopedCertificates();
        $certificateCount = $certificates->count();
        $certificatesThisMonth = $this->scopedCertificates()
            ->where('issued_at', '>=', now()->startOfMonth())
            ->count();

        $pendingSubmissions = $this->scopedSubmissions()->pending()->count();

        return [
            Stat::make('Students', number_format($studentCount))
                ->description($isAdmin ? 'Registered learners' : 'Learners in your courses')
                ->descriptionIcon(Heroicon::OutlinedUsers)
                ->chart($studentChart)
                ->color('primary'),

            Stat::make('Instructors', number_format($instructorCount))
                ->description($isAdmin ? 'Active teaching accounts' : 'Your instructor account')
                ->descriptionIcon(Heroicon::OutlinedAcademicCap)
                ->color('info'),

            Stat::make('Courses', number_format($courseCount))
                ->description($publishedCourseCount.' published')
                ->descriptionIcon(Heroicon::OutlinedBookOpen)
                ->color('primary'),

            Stat::make('Active enrollments', number_format($activeEnrollmentCount))
                ->description($completedEnrollmentCount.' completed')
                ->descriptionIcon(Heroicon::OutlinedUserGroup)
                ->chart($this->lastSevenDays($this->scopedEnrollments(), 'created_at'))
                ->color('info'),

            Stat::make('Completion rate', $completionRate.'%')
                ->description($completedEnrollmentCount.' of '.$enrollmentCount.' enrollments')
                ->descriptionIcon(Heroicon::OutlinedChartBar)
                ->color($completionRate >= 50 ? 'success' : 'warning'),

            Stat::make('Lessons completed', number_format($lessonsCompleted))
                ->description('Avg. video watch '.$averageWatch.'%')
                ->descriptionIcon(Heroicon::OutlinedPlayCircle)
                ->chart($this->lastSevenDays(
                    $this->scopedLessonProgress()->completed(),
                    'completed_at',
                ))
                ->color('success'),

            Stat::make('Certificates issued', number_format($certificateCount))
                ->description($certificatesThisMonth.' this month')
                ->descriptionIcon(Heroicon::OutlinedCheckBadge)
                ->chart($this->lastSevenDays($this->scopedCertificates(), 'issued_at'))
                ->color('success'),

            Stat::make('Awaiting grading', number_format($pendingSubmissions))
                ->description('Submissions waiting for review')
                ->descriptionIcon(Heroicon::OutlinedClipboardDocumentCheck)
                ->color($pendingSubmissions > 0 ? 'warning' : 'success'),
        ];
    }

    /**
     * Keep the eight stats in a balanced four-column grid.
     *
     * @return int | array<string, ?int> | null
     */
    protected function getColumns(): int|array|null
    {
        return 4;
    }
}
