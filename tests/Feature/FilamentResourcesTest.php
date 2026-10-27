<?php

use App\Filament\Resources\Assignments\AssignmentResource;
use App\Filament\Resources\Categories\CategoryResource;
use App\Filament\Resources\Certificates\CertificateResource;
use App\Filament\Resources\Courses\CourseResource;
use App\Filament\Resources\Enrollments\EnrollmentResource;
use App\Filament\Resources\LessonProgress\LessonProgressResource;
use App\Filament\Resources\Lessons\LessonResource;
use App\Filament\Resources\Options\OptionResource;
use App\Filament\Resources\Orders\OrderResource;
use App\Filament\Resources\Questions\QuestionResource;
use App\Filament\Resources\Quizzes\QuizResource;
use App\Filament\Resources\Reviews\ReviewResource;
use App\Filament\Resources\Sections\SectionResource;
use App\Filament\Resources\Submissions\SubmissionResource;
use App\Filament\Resources\Users\UserResource;
use App\Models\User;

test('all filament resource index pages render for an authenticated user', function () {
    $this->actingAs(User::factory()->admin()->create());

    $resources = [
        CategoryResource::class,
        CourseResource::class,
        SectionResource::class,
        LessonResource::class,
        QuizResource::class,
        QuestionResource::class,
        OptionResource::class,
        EnrollmentResource::class,
        OrderResource::class,
        LessonProgressResource::class,
        AssignmentResource::class,
        SubmissionResource::class,
        ReviewResource::class,
        CertificateResource::class,
        UserResource::class,
    ];

    foreach ($resources as $resource) {
        $this->get($resource::getUrl('index'))->assertSuccessful();
    }
});

test('all filament resource create pages render for an authenticated user', function () {
    $this->actingAs(User::factory()->admin()->create());

    $resources = [
        CategoryResource::class,
        CourseResource::class,
        SectionResource::class,
        LessonResource::class,
        QuizResource::class,
        QuestionResource::class,
        OptionResource::class,
        EnrollmentResource::class,
        OrderResource::class,
        LessonProgressResource::class,
        AssignmentResource::class,
        SubmissionResource::class,
        ReviewResource::class,
        CertificateResource::class,
        UserResource::class,
    ];

    foreach ($resources as $resource) {
        $this->get($resource::getUrl('create'))->assertSuccessful();
    }
});
