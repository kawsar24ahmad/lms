<?php

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Artisan;
use App\Http\Controllers\LessonNoteController;
use App\Http\Controllers\LessonProgressController;

Route::livewire('/', 'pages::home.index')->name('home');

Route::livewire('/ui-demo', 'pages::design.ui-states')->name('ui-demo');

Route::livewire('/login', 'pages::auth.login')->name('login');
Route::livewire('/register', 'pages::auth.register')->name('register');
Route::livewire('/forgot-password', 'pages::auth.forgot-password')->name('password.request');
Route::livewire('/reset-password/{token}', 'pages::auth.reset-password')->name('password.reset');

Route::livewire('/courses', 'pages::courses.index')
    ->name('courses.index');

Route::livewire('/dashboard', 'pages::dashboard.show')
    ->name('dashboard');

Route::livewire('/assignments', 'pages::assignments.index')
    ->name('assignments.index');

Route::livewire('/assignments/{assignment}', 'pages::assignments.show')
    ->name('assignments.show');

Route::livewire('/certificates/{certificate}', 'pages::certificates.show')
    ->name('certificates.show');

Route::livewire('/verify/{certificateNumber}', 'pages::verify.show')
    ->name('verify.show');

Route::livewire('/courses/{course}/lessons/{lesson}', 'pages::lessons.show')
    ->name('lessons.show');

Route::livewire('/courses/{course}/quizzes/{quiz}', 'pages::quizzes.show')
    ->name('quizzes.show');

Route::livewire('/courses/{course}', 'pages::courses.show')
    ->name('courses.show');

Route::livewire('/checkout', 'pages::checkout.create')
    ->name('checkout.create');

Route::post('/logout', function () {
    Auth::logout();

    return redirect()->route('courses.index');
})->name('logout');

Route::middleware(['auth'])->group(function () {
    // নোট সেভ করার রুট
    Route::post('/lessons/{lesson}/notes', [LessonNoteController::class, 'store'])->name('lesson.notes.store');

    // নোট ডিলিট করার রুট
    Route::delete('/lesson-notes/{note}', [LessonNoteController::class, 'destroy'])->name('lesson.notes.destroy');
});

Route::middleware(['auth', 'throttle:120,1'])->post(
    '/lessons/{lesson}/progress',
    [LessonProgressController::class, 'update']
)->name('lessons.progress.update');

Route::get('/storage-link', function () {
    Artisan::call('storage:link');

    return 'Storage link created successfully!';
});
