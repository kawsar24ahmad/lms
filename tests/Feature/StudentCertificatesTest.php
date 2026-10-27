<?php

use App\Models\Certificate;
use App\Models\User;

test('the owner sees a printable certificate page', function () {
    $user = User::factory()->create();

    $certificate = Certificate::factory()->create([
        'user_id' => $user->id,
        'final_score' => 92,
    ]);

    $this->actingAs($user)
        ->get(route('certificates.show', $certificate))
        ->assertSuccessful()
        ->assertSee('Certificate of completion')
        ->assertSee($user->name)
        ->assertSee($certificate->course->title)
        ->assertSee($certificate->certificate_number, false)
        ->assertSee('92%')
        ->assertSee('Print / Save as PDF');
});

test('other users and guests cannot open someone elses certificate', function () {
    $owner = User::factory()->create();
    $certificate = Certificate::factory()->create(['user_id' => $owner->id]);

    $this->get(route('certificates.show', $certificate))->assertForbidden();

    $intruder = User::factory()->create();

    $this->actingAs($intruder)
        ->get(route('certificates.show', $certificate))
        ->assertForbidden();
});

test('anyone can verify a genuine certificate by its number', function () {
    $certificate = Certificate::factory()->create();

    $this->get(route('verify.show', $certificate->certificate_number))
        ->assertSuccessful()
        ->assertSee('Certificate verified')
        ->assertSee($certificate->student->name)
        ->assertSee($certificate->course->title);
});

test('an unknown certificate number shows a friendly not-found state', function () {
    $unknown = Certificate::factory()->make()->certificate_number;

    $this->get(route('verify.show', $unknown))
        ->assertSuccessful()
        ->assertSee('No certificate found');
});

test('the dashboard links certificates to their printable pages', function () {
    $user = User::factory()->create();
    $certificate = Certificate::factory()->create(['user_id' => $user->id]);

    $this->actingAs($user)
        ->get(route('dashboard'))
        ->assertSuccessful()
        ->assertSee(route('certificates.show', $certificate), false)
        ->assertSee('View / print');
});
