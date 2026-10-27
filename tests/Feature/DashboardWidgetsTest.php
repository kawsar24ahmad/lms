<?php

use App\Filament\Widgets\CoursePerformanceChart;
use App\Filament\Widgets\EnrollmentTrendChart;
use App\Filament\Widgets\LessonEngagementChart;
use App\Filament\Widgets\PendingSubmissionsTable;
use App\Filament\Widgets\PlatformStatsOverview;
use App\Filament\Widgets\RecentEnrollmentsTable;
use App\Models\Assignment;
use App\Models\Certificate;
use App\Models\Course;
use App\Models\Enrollment;
use App\Models\Lesson;
use App\Models\LessonProgress;
use App\Models\Section;
use App\Models\Submission;
use App\Models\User;
use Filament\Facades\Filament;
use Filament\Widgets\ChartWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Livewire\Livewire;

/**
 * Read the protected stats from a stats widget without rendering it.
 *
 * @return array<string, mixed>
 */
function dashboardStats(PlatformStatsOverview $widget): array
{
    $stats = (fn (): array => $this->getStats())->call($widget);

    return collect($stats)
        ->mapWithKeys(fn (Stat $stat): array => [(string) $stat->getLabel() => $stat->getValue()])
        ->all();
}

/**
 * Read the protected chart data from a chart widget.
 *
 * @return array<string, mixed>
 */
function dashboardChartData(ChartWidget $widget): array
{
    return (fn (): array => $this->getData())->call($widget);
}

/**
 * @return array{0: Course, 1: Lesson}
 */
function dashboardCourse(User $instructor, array $attributes = []): array
{
    $course = Course::factory()->published()->create([
        ...$attributes,
        'user_id' => $instructor->getKey(),
    ]);

    $section = Section::factory()->create(['course_id' => $course->id]);
    $lesson = Lesson::factory()->create([
        'section_id' => $section->id,
        'duration_minutes' => 10,
    ]);

    return [$course, $lesson];
}

test('the platform stats summarise admin metrics', function () {
    $admin = User::factory()->admin()->create();
    $instructor = User::factory()->instructor()->create();
    $students = User::factory()->count(3)->create();

    [$course, $lesson] = dashboardCourse($instructor);

    Enrollment::factory()->create([
        'course_id' => $course->id,
        'student_id' => $students[0]->id,
        'status' => 'active',
    ]);
    Enrollment::factory()->completed()->create([
        'course_id' => $course->id,
        'student_id' => $students[1]->id,
    ]);

    LessonProgress::factory()->completed()->create([
        'user_id' => $students[0]->id,
        'lesson_id' => $lesson->id,
    ]);

    $assignment = Assignment::factory()->create(['lesson_id' => $lesson->id]);
    Submission::factory()->create([
        'assignment_id' => $assignment->id,
        'user_id' => $students[0]->id,
    ]);

    Certificate::factory()->create([
        'course_id' => $course->id,
        'user_id' => $students[1]->id,
        'issued_at' => now(),
    ]);

    $this->actingAs($admin);

    expect(dashboardStats(app(PlatformStatsOverview::class)))->toMatchArray([
        'Students' => '3',
        'Instructors' => '1',
        'Courses' => '1',
        'Active enrollments' => '1',
        'Completion rate' => '50%',
        'Lessons completed' => '1',
        'Certificates issued' => '1',
        'Awaiting grading' => '1',
    ]);
});

test('instructors only see metrics for their own courses', function () {
    $instructor = User::factory()->instructor()->create();
    $otherInstructor = User::factory()->instructor()->create();
    $student = User::factory()->create();
    $otherStudent = User::factory()->create();

    [$course] = dashboardCourse($instructor);
    [$otherCourse] = dashboardCourse($otherInstructor);

    Enrollment::factory()->create([
        'course_id' => $course->id,
        'student_id' => $student->id,
        'status' => 'active',
    ]);
    Enrollment::factory()->completed()->create([
        'course_id' => $otherCourse->id,
        'student_id' => $otherStudent->id,
    ]);

    $this->actingAs($instructor);

    expect(dashboardStats(app(PlatformStatsOverview::class)))->toMatchArray([
        'Students' => '1',
        'Instructors' => '1',
        'Courses' => '1',
        'Active enrollments' => '1',
        'Completion rate' => '0%',
    ]);
});

test('charts only include the courses an instructor owns', function () {
    $instructor = User::factory()->instructor()->create();
    $otherInstructor = User::factory()->instructor()->create();
    $students = User::factory()->count(4)->create();

    [$course, $lesson] = dashboardCourse($instructor);
    [$secondCourse, $secondLesson] = dashboardCourse($instructor);
    [$otherCourse, $otherLesson] = dashboardCourse($otherInstructor);

    Enrollment::factory()->completed()->create([
        'course_id' => $course->id,
        'student_id' => $students[0]->id,
    ]);
    Enrollment::factory()->create([
        'course_id' => $course->id,
        'student_id' => $students[1]->id,
        'status' => 'active',
    ]);
    Enrollment::factory()->create([
        'course_id' => $secondCourse->id,
        'student_id' => $students[1]->id,
        'status' => 'active',
    ]);

    Enrollment::factory()->create([
        'course_id' => $otherCourse->id,
        'student_id' => $students[2]->id,
        'status' => 'active',
    ]);
    Enrollment::factory()->create([
        'course_id' => $otherCourse->id,
        'student_id' => $students[3]->id,
        'status' => 'active',
    ]);

    LessonProgress::factory()->completed()->create([
        'user_id' => $students[0]->id,
        'lesson_id' => $lesson->id,
    ]);
    LessonProgress::factory()->create([
        'user_id' => $students[1]->id,
        'lesson_id' => $secondLesson->id,
        'seconds_watched' => 50,
        'furthest_position' => 50,
        'duration_seconds' => 100,
        'is_completed' => false,
    ]);
    LessonProgress::factory()->completed()->create([
        'user_id' => $students[2]->id,
        'lesson_id' => $otherLesson->id,
    ]);

    $this->actingAs($instructor);

    $trend = dashboardChartData(app(EnrollmentTrendChart::class));
    $performance = dashboardChartData(app(CoursePerformanceChart::class));
    $engagement = dashboardChartData(app(LessonEngagementChart::class));

    expect(array_sum($trend['datasets'][0]['data']))->toBe(3)
        ->and(array_sum($trend['datasets'][1]['data']))->toBe(1)
        ->and($performance['labels'])->toHaveCount(2)
        ->and(array_sum($performance['datasets'][0]['data']))->toBe(3)
        ->and(array_sum($performance['datasets'][1]['data']))->toBe(1)
        ->and($engagement['datasets'][0]['data'])->toBe([1, 1, 4]);
});

test('dashboard tables only show records from an instructor’s courses', function () {
    $instructor = User::factory()->instructor()->create();
    $otherInstructor = User::factory()->instructor()->create();
    $student = User::factory()->create();
    $otherStudent = User::factory()->create();

    [$course, $lesson] = dashboardCourse($instructor);
    [$otherCourse, $otherLesson] = dashboardCourse($otherInstructor);

    $enrollment = Enrollment::factory()->create([
        'course_id' => $course->id,
        'student_id' => $student->id,
        'status' => 'active',
    ]);
    $otherEnrollment = Enrollment::factory()->create([
        'course_id' => $otherCourse->id,
        'student_id' => $otherStudent->id,
        'status' => 'active',
    ]);

    $assignment = Assignment::factory()->create(['lesson_id' => $lesson->id]);
    $otherAssignment = Assignment::factory()->create(['lesson_id' => $otherLesson->id]);

    $submission = Submission::factory()->create([
        'assignment_id' => $assignment->id,
        'user_id' => $student->id,
    ]);
    $gradedAssignment = Assignment::factory()->create(['lesson_id' => $lesson->id]);
    $gradedSubmission = Submission::factory()->graded()->create([
        'assignment_id' => $gradedAssignment->id,
        'user_id' => $student->id,
    ]);
    $otherSubmission = Submission::factory()->create([
        'assignment_id' => $otherAssignment->id,
        'user_id' => $otherStudent->id,
    ]);

    $this->actingAs($instructor);
    Filament::setCurrentPanel(Filament::getPanel('admin'));

    Livewire::test(RecentEnrollmentsTable::class)
        ->assertCanSeeTableRecords([$enrollment])
        ->assertCanNotSeeTableRecords([$otherEnrollment]);

    Livewire::test(PendingSubmissionsTable::class)
        ->assertCanSeeTableRecords([$submission])
        ->assertCanNotSeeTableRecords([$gradedSubmission, $otherSubmission]);
});

test('all dashboard widgets render for an admin', function () {
    $admin = User::factory()->admin()->create();
    $student = User::factory()->create();
    [$course] = dashboardCourse(User::factory()->instructor()->create());
    $course->enroll($student);

    $this->actingAs($admin);
    Filament::setCurrentPanel(Filament::getPanel('admin'));

    $widgets = [
        PlatformStatsOverview::class,
        EnrollmentTrendChart::class,
        CoursePerformanceChart::class,
        LessonEngagementChart::class,
        RecentEnrollmentsTable::class,
        PendingSubmissionsTable::class,
    ];

    foreach ($widgets as $widget) {
        Livewire::test($widget)->assertSuccessful();
    }

    expect(Filament::getWidgets())->toContain(...$widgets);

    $this->get(route('filament.admin.pages.dashboard'))->assertSuccessful();
});
