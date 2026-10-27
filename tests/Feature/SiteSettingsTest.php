<?php

use App\Filament\Pages\ManageSiteSettings;
use App\Models\SiteSetting;
use App\Models\User;

test('administrators can open the website settings page', function () {
    $this->actingAs(User::factory()->admin()->create())
        ->get(ManageSiteSettings::getUrl())
        ->assertSuccessful()
        ->assertSee('Website Settings')
        ->assertSee('General')
        ->assertSee('Branding')
        ->assertSee('Contact')
        ->assertSee('Social')
        ->assertSee('SEO');
});

test('instructors and students cannot open the website settings page', function () {
    $this->actingAs(User::factory()->instructor()->create())
        ->get(ManageSiteSettings::getUrl())
        ->assertForbidden();

    $this->actingAs(User::factory()->create())
        ->get(ManageSiteSettings::getUrl())
        ->assertForbidden();
});

test('administrators can save the website settings', function () {
    $this->actingAs(User::factory()->admin()->create());

    Livewire::test(ManageSiteSettings::class)
        ->fillForm([
            'site_name' => 'Acme Academy',
            'tagline' => 'Learn something new every day',
            'contact_email' => 'hello@acme.test',
            'contact_phone' => '+1 555 0100',
            'facebook_url' => 'https://facebook.com/acme',
            'meta_title' => 'Acme Academy — online courses',
            'meta_description' => 'Practical courses for ambitious students.',
            'footer_text' => '© 2026 Acme Academy. All rights reserved.',
        ])
        ->call('save')
        ->assertHasNoFormErrors()
        ->assertNotified();

    $settings = SiteSetting::current();

    expect($settings->site_name)->toBe('Acme Academy')
        ->and($settings->tagline)->toBe('Learn something new every day')
        ->and($settings->contact_email)->toBe('hello@acme.test')
        ->and($settings->facebook_url)->toBe('https://facebook.com/acme')
        ->and($settings->meta_title)->toBe('Acme Academy — online courses')
        ->and($settings->footer_text)->toBe('© 2026 Acme Academy. All rights reserved.');
});

test('the site name is required', function () {
    $this->actingAs(User::factory()->admin()->create());

    Livewire::test(ManageSiteSettings::class)
        ->fillForm(['site_name' => null])
        ->call('save')
        ->assertHasFormErrors(['site_name' => 'required']);
});

test('the public site uses the saved branding', function () {
    SiteSetting::factory()->create([
        'site_name' => 'Acme Academy',
        'tagline' => 'Learn something new every day',
        'meta_description' => 'Practical courses for ambitious students.',
        'contact_email' => 'hello@acme.test',
        'facebook_url' => 'https://facebook.com/acme',
    ]);

    $this->get(route('home'))
        ->assertSuccessful()
        ->assertSee('Acme Academy')
        ->assertSee('Learn something new every day')
        ->assertSee('Practical courses for ambitious students.')
        ->assertSee('https://facebook.com/acme', false)
        ->assertSee('mailto:hello@acme.test', false);
});

test('the global page loader is rendered on public and admin pages', function () {
    $this->get(route('home'))
        ->assertSuccessful()
        ->assertSee('id="page-loader"', false);

    $this->actingAs(User::factory()->admin()->create())
        ->get(ManageSiteSettings::getUrl())
        ->assertSuccessful()
        ->assertSee('id="page-loader"', false);
});
