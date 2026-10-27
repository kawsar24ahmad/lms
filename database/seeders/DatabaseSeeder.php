<?php

namespace Database\Seeders;

use App\Models\Assignment;
use App\Models\Category;
use App\Models\Certificate;
use App\Models\Course;
use App\Models\Enrollment;
use App\Models\Lesson;
use App\Models\LessonProgress;
use App\Models\Option;
use App\Models\Question;
use App\Models\Quiz;
use App\Models\QuizAttempt;
use App\Models\Review;
use App\Models\Section;
use App\Models\Submission;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Parent category names mapped to their child category names.
     *
     * @var array<string, list<string>>
     */
    private const CATEGORY_TREE = [
        'Web Development' => ['PHP & Laravel', 'JavaScript & Vue'],
        'Design' => ['User Interface Design', 'Design Systems'],
        'Business' => ['Entrepreneurship'],
    ];

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call(ShieldSeeder::class);
        $this->call(SiteSettingSeeder::class);

        User::factory()->admin()->create([
            'name' => 'Ada Admin',
            'email' => 'admin@lms.test',
        ]);

        $instructors = collect([
            ['name' => 'Olivia Instructor', 'email' => 'olivia@lms.test'],
            ['name' => 'Marcus Teacher', 'email' => 'marcus@lms.test'],
        ])->map(fn (array $attributes) => User::factory()->instructor()->create($attributes));

        $students = User::factory(8)->create();

        $courses = collect();
        foreach (self::CATEGORY_TREE as $parentName => $childNames) {
            $category = Category::factory()->create([
                'name' => $parentName,
                'slug' => Str::slug($parentName),
            ]);

            foreach ($childNames as $childName) {
                $child = Category::factory()->create([
                    'parent_id' => $category->id,
                    'name' => $childName,
                    'slug' => Str::slug($childName),
                ]);

                $courses->push(Course::factory()->published()->free()->introVideo()->create([
                    'category_id' => $child->id,
                    'user_id' => $instructors->random()->id,
                ]));
                $courses->push(Course::factory()->published()->discounted()->create([
                    'category_id' => $child->id,
                    'user_id' => $instructors->random()->id,
                ]));
            }
        }

        $courses->each(fn (Course $course) => $this->buildCurriculum($course));

        $this->graduateFirstStudent($courses->first(), $students->first());
        $this->enrollRemainingStudents($courses, $students);
        $this->collectReviews($courses->first(), $students);
    }

    /**
     * Build the course curriculum: sections, lessons, one quiz and one assignment.
     */
    private function buildCurriculum(Course $course): void
    {
        $sectionCount = random_int(2, 3);

        for ($sectionPosition = 0; $sectionPosition < $sectionCount; $sectionPosition++) {
            $section = Section::factory()->create([
                'course_id' => $course->id,
                'position' => $sectionPosition,
            ]);

            $lessons = collect();
            $lessonCount = random_int(3, 5);
            for ($lessonPosition = 0; $lessonPosition < $lessonCount; $lessonPosition++) {
                $lessons->push(Lesson::factory()
                    ->when($lessonPosition === 0, fn ($factory) => $factory->preview())
                    ->when($lessonPosition % 2 === 1, fn ($factory) => $factory->withVideo())
                    ->create([
                        'section_id' => $section->id,
                        'position' => $lessonPosition,
                    ]));
            }
        }

        $quiz = $course->quizzes()->create([
            'title' => $course->title.' Quiz',
            'description' => 'Test your knowledge of this course.',
            'pass_score' => 60,
            'is_active' => true,
        ]);

        Question::factory(3)->create(['quiz_id' => $quiz->id])->each(function (Question $question): void {
            Option::factory()->correct()->create(['question_id' => $question->id]);
            Option::factory(3)->create(['question_id' => $question->id]);
        });

        Assignment::factory()->dueSoon()->create([
            'lesson_id' => $lessons->first()->id,
        ]);
    }

    /**
     * Fully complete a student's journey in the featured course: enrollment,
     * lesson progress, graded submissions, review context and a certificate.
     */
    private function graduateFirstStudent(Course $course, User $student): void
    {
        Enrollment::factory()->completed()->create([
            'course_id' => $course->id,
            'student_id' => $student->id,
        ]);

        $course->load('sections.lessons.assignments');

        $course->sections->flatMap->lessons->each(function (Lesson $lesson) use ($student): void {
            LessonProgress::factory()->completed()->create([
                'user_id' => $student->id,
                'lesson_id' => $lesson->id,
                'seconds_watched' => ($lesson->duration_minutes ?? 30) * 60,
            ]);
        });

        $course->sections->flatMap->lessons->flatMap->assignments->each(function (Assignment $assignment) use ($student): void {
            Submission::factory()->graded()->create([
                'assignment_id' => $assignment->id,
                'user_id' => $student->id,
            ]);
        });

        Certificate::factory()->create([
            'course_id' => $course->id,
            'user_id' => $student->id,
            'final_score' => 92,
        ]);

        $course->loadMissing('quizzes');

        $course->quizzes->each(function (Quiz $quiz) use ($student): void {
            QuizAttempt::factory()->passed()->create([
                'quiz_id' => $quiz->id,
                'user_id' => $student->id,
                'score' => 92,
            ]);
        });
    }

    /**
     * Give every remaining student a couple of active enrollments.
     *
     * @param  Collection<int, Course>  $courses
     * @param  Collection<int, User>  $students
     */
    private function enrollRemainingStudents(Collection $courses, Collection $students): void
    {
        $students->skip(1)->each(function (User $student) use ($courses): void {
            $courses->random(2)->each(fn (Course $course) => Enrollment::factory()->create([
                'course_id' => $course->id,
                'student_id' => $student->id,
            ]));
        });
    }

    /**
     * Have a few students review the featured course.
     *
     * @param  Collection<int, User>  $students
     */
    private function collectReviews(Course $course, Collection $students): void
    {
        $students->slice(1, 3)->each(function (User $student) use ($course): void {
            Review::factory()->create([
                'course_id' => $course->id,
                'user_id' => $student->id,
            ]);
        });
    }
}
