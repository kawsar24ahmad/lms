<?php

use App\Filament\Resources\Quizzes\Pages\CreateQuiz;
use App\Filament\Resources\Quizzes\Pages\EditQuiz;
use App\Models\Course;
use App\Models\Option;
use App\Models\Question;
use App\Models\Quiz;
use App\Models\User;
use Filament\Forms\Components\Select;

test('an admin can create a quiz with its questions and options in one form', function () {
    $this->actingAs(User::factory()->admin()->create());

    $course = Course::factory()->create();

    Livewire::test(CreateQuiz::class)
        ->fillForm([
            'course_id' => $course->id,
            'title' => 'Laravel Basics',
            'description' => 'A short quiz about Laravel.',
            'pass_score' => 70,
            'is_active' => true,
            'questions' => [
                [
                    'question_text' => 'What is 2 + 2?',
                    'type' => 'multiple_choice',
                    'points' => 1,
                    'options' => [
                        ['option_text' => '4', 'is_correct' => true],
                        ['option_text' => '5', 'is_correct' => false],
                    ],
                ],
                [
                    'question_text' => 'Laravel is a PHP framework.',
                    'type' => 'true_false',
                    'points' => 2,
                    'correct_answer' => 'true',
                ],
            ],
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    $quiz = Quiz::query()->where('title', 'Laravel Basics')->firstOrFail();
    $firstQuestion = $quiz->questions()->where('question_text', 'What is 2 + 2?')->firstOrFail();
    $trueFalseQuestion = $quiz->questions()->where('question_text', 'Laravel is a PHP framework.')->firstOrFail();

    expect($quiz->course_id)->toBe($course->id)
        ->and($quiz->pass_score)->toBe(70)
        ->and($quiz->is_active)->toBeTrue()
        ->and($quiz->questions()->count())->toBe(2)
        ->and($firstQuestion->points)->toBe(1)
        ->and($firstQuestion->options()->count())->toBe(2)
        ->and($firstQuestion->options()->where('is_correct', true)->value('option_text'))->toBe('4')
        ->and($trueFalseQuestion->correct_answer)->toBe('true')
        ->and($trueFalseQuestion->options()->count())->toBe(0);
});

test('an admin can update questions and options and add new ones in one form', function () {
    $this->actingAs(User::factory()->admin()->create());

    $quiz = Quiz::factory()->create();
    $question = Question::factory()->create([
        'quiz_id' => $quiz->id,
        'question_text' => 'Old question',
        'points' => 1,
    ]);
    $correctOption = Option::factory()->create([
        'question_id' => $question->id,
        'option_text' => 'Old correct option',
        'is_correct' => true,
    ]);
    $removedOption = Option::factory()->create([
        'question_id' => $question->id,
        'option_text' => 'Option to remove',
        'is_correct' => false,
    ]);

    Livewire::test(EditQuiz::class, ['record' => $quiz->getRouteKey()])
        ->fillForm([
            'course_id' => $quiz->course_id,
            'title' => $quiz->title,
            'description' => $quiz->description,
            'pass_score' => $quiz->pass_score,
            'is_active' => $quiz->is_active,
            'questions' => [
                'record-'.$question->id => [
                    'question_text' => 'Updated question',
                    'type' => 'multiple_choice',
                    'points' => 3,
                    'options' => [
                        'record-'.$correctOption->id => [
                            'option_text' => 'Updated correct option',
                            'is_correct' => true,
                        ],
                    ],
                ],
                'new-question' => [
                    'question_text' => 'New question',
                    'type' => 'multiple_choice',
                    'points' => 2,
                    'options' => [
                        'new-option-a' => ['option_text' => 'New A', 'is_correct' => true],
                        'new-option-b' => ['option_text' => 'New B', 'is_correct' => false],
                    ],
                ],
            ],
        ])
        ->set('data.questions.record-'.$question->id.'.options', [
            'record-'.$correctOption->id => [
                'option_text' => 'Updated correct option',
                'is_correct' => true,
            ],
        ])
        ->call('save')
        ->assertHasNoFormErrors();

    expect($question->refresh()->question_text)->toBe('Updated question')
        ->and($question->points)->toBe(3)
        ->and($correctOption->refresh()->option_text)->toBe('Updated correct option')
        ->and(Option::query()->find($removedOption->id))->toBeNull()
        ->and($quiz->questions()->count())->toBe(2)
        ->and($quiz->questions()->where('question_text', 'New question')->firstOrFail()->options()->count())->toBe(2);
});

test('an admin can save correct answers for open answer and true/false questions', function () {
    $this->actingAs(User::factory()->admin()->create());

    $course = Course::factory()->create();

    Livewire::test(CreateQuiz::class)
        ->fillForm([
            'course_id' => $course->id,
            'title' => 'Mixed question types',
            'description' => null,
            'pass_score' => 60,
            'is_active' => true,
            'questions' => [
                [
                    'question_text' => 'What does PHP stand for?',
                    'type' => 'open_answer',
                    'points' => 3,
                    'correct_answer' => 'Hypertext Preprocessor',
                ],
                [
                    'question_text' => 'Laravel is a PHP framework.',
                    'type' => 'true_false',
                    'points' => 2,
                    'correct_answer' => 'true',
                ],
            ],
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    $quiz = Quiz::query()->where('title', 'Mixed question types')->firstOrFail();

    expect($quiz->questions()->count())->toBe(2)
        ->and($quiz->questions()->where('question_text', 'What does PHP stand for?')->value('correct_answer'))->toBe('Hypertext Preprocessor')
        ->and($quiz->questions()->where('question_text', 'Laravel is a PHP framework.')->value('correct_answer'))->toBe('true')
        ->and(Option::query()->whereIn('question_id', $quiz->questions()->pluck('id'))->count())->toBe(0);
});

test('instructors only see their own courses in the quiz course selector', function () {
    grantRolePermissions('instructor', [
        'ViewAny:Quiz',
        'Create:Quiz',
    ]);

    $instructor = User::factory()->instructor()->create();
    $ownCourse = Course::factory()->create(['user_id' => $instructor->id]);
    $otherCourse = Course::factory()->create();

    $this->actingAs($instructor);

    Livewire::test(CreateQuiz::class)
        ->assertFormFieldExists(
            'course_id',
            checkFieldUsing: function (Select $field) use ($ownCourse, $otherCourse): bool {
                $options = $field->getOptions();

                return array_key_exists($ownCourse->id, $options)
                    && ! array_key_exists($otherCourse->id, $options);
            },
        );
});
