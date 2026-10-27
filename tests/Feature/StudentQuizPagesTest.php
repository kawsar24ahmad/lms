<?php

use App\Models\Certificate;
use App\Models\Course;
use App\Models\Lesson;
use App\Models\Option;
use App\Models\Question;
use App\Models\Quiz;
use App\Models\QuizAttempt;
use App\Models\Section;
use App\Models\User;

test('the attempt scores answers against correct options by points', function () {
    $quiz = Quiz::factory()->create(['pass_score' => 60]);

    $easy = Question::factory()->create([
        'quiz_id' => $quiz->id,
        'question_text' => 'What does PHP stand for?',
        'points' => 3,
    ]);
    $correct = Option::factory()->correct()->create([
        'question_id' => $easy->id,
        'option_text' => 'Hypertext Preprocessor',
    ]);
    Option::factory()->create(['question_id' => $easy->id, 'option_text' => 'Pretty Home Page']);

    $hard = Question::factory()->create([
        'quiz_id' => $quiz->id,
        'question_text' => 'Which header version is current?',
        'points' => 1,
    ]);
    $wrongOption = Option::factory()->create(['question_id' => $hard->id]);
    Option::factory()->correct()->create(['question_id' => $hard->id]);

    $student = User::factory()->create();
    $attempt = QuizAttempt::query()->create([
        'quiz_id' => $quiz->id,
        'user_id' => $student->id,
    ]);

    expect($attempt->scoreFor([$easy->id => $wrongOption->id]))->toBe(0)
        ->and($attempt->scoreFor([$easy->id => $correct->id]))->toBe(75)
        ->and($attempt->scoreFor([$easy->id => $correct->id, $hard->id => 999999]))->toBe(75);
});

test('the attempt scores open answers and true/false answers against the stored correct answer', function () {
    $quiz = Quiz::factory()->create(['pass_score' => 50]);

    $openQuestion = Question::factory()->openAnswer()->create([
        'quiz_id' => $quiz->id,
        'question_text' => 'What does PHP stand for?',
        'points' => 2,
        'correct_answer' => 'Hypertext Preprocessor',
    ]);

    $trueFalseQuestion = Question::factory()->trueFalse()->create([
        'quiz_id' => $quiz->id,
        'question_text' => 'Laravel is a PHP framework.',
        'points' => 1,
        'correct_answer' => 'true',
    ]);

    $student = User::factory()->create();
    $attempt = QuizAttempt::query()->create([
        'quiz_id' => $quiz->id,
        'user_id' => $student->id,
    ]);

    expect($attempt->scoreFor([
        $openQuestion->id => '  hypertext preprocessor  ',
        $trueFalseQuestion->id => 'true',
    ]))->toBe(100)
        ->and($attempt->scoreFor([
            $openQuestion->id => 'Hypertext Preprocessor',
            $trueFalseQuestion->id => 'false',
        ]))->toBe(67)
        ->and($attempt->scoreFor([
            $openQuestion->id => '   ',
            $trueFalseQuestion->id => 'true',
        ]))->toBe(33);
});

test('submitting correct answers passes the quiz and records the certificate score', function () {
    $student = User::factory()->create();
    $course = Course::factory()->published()->create();
    $section = Section::factory()->create(['course_id' => $course->id]);
    Lesson::factory()->create(['section_id' => $section->id]);
    $course->enroll($student);

    $cert = Certificate::factory()->create([
        'course_id' => $course->id,
        'user_id' => $student->id,
        'final_score' => null,
    ]);

    $quiz = Quiz::factory()->create(['course_id' => $course->id, 'pass_score' => 60]);
    $question = Question::factory()->create(['quiz_id' => $quiz->id, 'points' => 1]);
    $right = Option::factory()->correct()->create(['question_id' => $question->id]);

    $attempt = QuizAttempt::query()->firstOrCreate([
        'quiz_id' => $quiz->id,
        'user_id' => $student->id,
    ]);

    $attempt->submit([$question->id => $right->id]);

    expect($attempt->refresh()->status)->toBe('passed')
        ->and($attempt->score)->toBe(100)
        ->and($attempt->submitted_at)->not->toBeNull()
        ->and($cert->refresh()->final_score)->toBe(100);
});

test('submitting wrong answers fails the quiz and leaves the certificate untouched', function () {
    $student = User::factory()->create();
    $quiz = Quiz::factory()->create(['pass_score' => 60]);
    $question = Question::factory()->create(['quiz_id' => $quiz->id, 'points' => 2]);
    $wrong = Option::factory()->create(['question_id' => $question->id]);
    Option::factory()->correct()->create(['question_id' => $question->id]);

    $cert = Certificate::factory()->create([
        'course_id' => $quiz->course_id,
        'user_id' => $student->id,
        'final_score' => null,
    ]);

    $attempt = QuizAttempt::query()->create([
        'quiz_id' => $quiz->id,
        'user_id' => $student->id,
    ]);

    $attempt->submit([$question->id => $wrong->id]);

    expect($attempt->refresh()->status)->toBe('failed')
        ->and($attempt->score)->toBe(0)
        ->and($cert->refresh()->final_score)->toBeNull();
});

test('retaking resets the attempt', function () {
    $attempt = QuizAttempt::factory()->passed()->create();

    $attempt->retake();

    expect($attempt->refresh()->status)->toBe('in_progress')
        ->and($attempt->score)->toBeNull()
        ->and($attempt->submitted_at)->toBeNull()
        ->and($attempt->answers)->toBe([]);
});

test('guests and unenrolled students cannot open the quiz page', function () {
    $course = Course::factory()->published()->create();
    $quiz = Quiz::factory()->create(['course_id' => $course->id]);

    $this->get(route('quizzes.show', ['course' => $course, 'quiz' => $quiz]))->assertForbidden();

    $outsider = User::factory()->create();
    $courseAgain = Course::factory()->published()->create();
    $otherQuiz = Quiz::factory()->create(['course_id' => $courseAgain->id]);

    $this->actingAs($outsider)
        ->get(route('quizzes.show', ['course' => $courseAgain, 'quiz' => $otherQuiz]))
        ->assertForbidden();
});

test('an enrolled student can take the quiz through the page and pass it', function () {
    $student = User::factory()->create();
    $course = Course::factory()->published()->create();
    $section = Section::factory()->create(['course_id' => $course->id]);
    Lesson::factory()->create(['section_id' => $section->id]);
    $course->enroll($student);

    $quiz = Quiz::factory()->create(['course_id' => $course->id, 'pass_score' => 60]);
    $question = Question::factory()->create(['quiz_id' => $quiz->id]);
    $right = Option::factory()->correct()->create(['question_id' => $question->id]);

    $this->actingAs($student)
        ->get(route('quizzes.show', ['course' => $course, 'quiz' => $quiz]))
        ->assertSuccessful()
        ->assertSee($quiz->title)
        ->assertSee($question->question_text);

    Livewire::actingAs($student)
        ->test('pages::quizzes.show', ['course' => $course, 'quiz' => $quiz])
        ->assertSuccessful()
        ->call('submit')
        ->assertHasErrors(['submit'])
        ->set('answers', [$question->id => $right->id])
        ->call('submit')
        ->assertHasNoErrors();

    $attempt = QuizAttempt::query()
        ->where('quiz_id', $quiz->id)
        ->where('user_id', $student->id)
        ->firstOrFail();

    expect($attempt->status)->toBe('passed')
        ->and($attempt->score)->toBe(100);
});

test('an enrolled student can answer open answer and true/false questions through the page', function () {
    $student = User::factory()->create();
    $course = Course::factory()->published()->create();
    $section = Section::factory()->create(['course_id' => $course->id]);
    Lesson::factory()->create(['section_id' => $section->id]);
    $course->enroll($student);

    $quiz = Quiz::factory()->create(['course_id' => $course->id, 'pass_score' => 60]);

    $openQuestion = Question::factory()->openAnswer()->create([
        'quiz_id' => $quiz->id,
        'question_text' => 'What does PHP stand for?',
        'points' => 2,
        'correct_answer' => 'Hypertext Preprocessor',
    ]);

    $trueFalseQuestion = Question::factory()->trueFalse()->create([
        'quiz_id' => $quiz->id,
        'question_text' => 'Laravel is a PHP framework.',
        'points' => 1,
        'correct_answer' => 'true',
    ]);

    $this->actingAs($student)
        ->get(route('quizzes.show', ['course' => $course, 'quiz' => $quiz]))
        ->assertSuccessful()
        ->assertSee($openQuestion->question_text)
        ->assertSee($trueFalseQuestion->question_text)
        ->assertSee('Type your answer...')
        ->assertSee('True')
        ->assertSee('False');

    Livewire::actingAs($student)
        ->test('pages::quizzes.show', ['course' => $course, 'quiz' => $quiz])
        ->assertSeeHtml('id="answer-'.$openQuestion->id.'"')
        ->assertSeeHtml('id="answer-'.$trueFalseQuestion->id.'-true"')
        ->set('answers', [
            $openQuestion->id => '   ',
            $trueFalseQuestion->id => 'true',
        ])
        ->call('submit')
        ->assertHasErrors(['submit'])
        ->set('answers', [
            $openQuestion->id => 'hypertext preprocessor',
            $trueFalseQuestion->id => 'true',
        ])
        ->call('submit')
        ->assertHasNoErrors();

    $attempt = QuizAttempt::query()
        ->where('quiz_id', $quiz->id)
        ->where('user_id', $student->id)
        ->firstOrFail();

    expect($attempt->status)->toBe('passed')
        ->and($attempt->score)->toBe(100);
});

test('the course page links enrolled students to the quiz', function () {
    $student = User::factory()->create();
    $course = Course::factory()->published()->create();
    Quiz::factory()->create(['course_id' => $course->id, 'title' => 'Arrays basics check']);
    $course->enroll($student);

    $this->actingAs($student)
        ->get(route('courses.show', $course))
        ->assertSuccessful()
        ->assertSee('Arrays basics check')
        ->assertSee('Take quiz');
});
