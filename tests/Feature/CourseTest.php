<?php

use App\Models\Category;
use App\Models\Course;
use App\Models\User;
use Illuminate\Database\QueryException;

test('course slugs are unique', function () {
    Course::factory()->create(['slug' => 'php-basics']);

    expect(fn () => Course::factory()->create(['slug' => 'php-basics']))
        ->toThrow(QueryException::class);
});

test('the published scope only returns courses that are published', function () {
    Course::factory()->published()->create(['title' => 'Published course']);
    Course::factory()->create(['title' => 'Draft course']);

    $published = Course::published()->get();

    expect($published)->toHaveCount(1)
        ->and($published->first()->title)->toBe('Published course')
        ->and(Course::draft()->get()->pluck('title'))->toContain('Draft course');
});

test('a course knows its instructor and category', function () {
    $course = Course::factory()->create();

    expect($course->instructor)->toBeInstanceOf(User::class)
        ->and($course->category)->toBeInstanceOf(Category::class);
});

test('discount pricing computes the selling price', function () {
    $noDiscount = Course::factory()->make(['price' => 40]);

    expect($noDiscount->isDiscounted())->toBeFalse()
        ->and($noDiscount->effectivePrice())->toBe(40.0);

    $fixed = Course::factory()->make(['price' => 100, 'discount_type' => Course::DISCOUNT_TYPE_FIXED, 'discount_amount' => 30]);

    expect($fixed->isDiscounted())->toBeTrue()
        ->and($fixed->effectivePrice())->toBe(70.0);

    $percent = Course::factory()->make(['price' => 100, 'discount_type' => Course::DISCOUNT_TYPE_PERCENT, 'discount_amount' => 15]);

    expect($percent->effectivePrice())->toBe(85.0);
});

test('the discount never pushes the price below zero and can zero it out', function () {
    $floored = Course::factory()->make(['price' => 10, 'discount_type' => Course::DISCOUNT_TYPE_FIXED, 'discount_amount' => 30]);
    $zeroed = Course::factory()->make(['price' => 50, 'discount_type' => Course::DISCOUNT_TYPE_PERCENT, 'discount_amount' => 100]);

    expect($floored->effectivePrice())->toBe(0.0)
        ->and($zeroed->effectivePrice())->toBe(0.0);
});

test('a nil or zero discount keeps the list price', function () {
    $zeroAmount = Course::factory()->make(['price' => 25, 'discount_type' => Course::DISCOUNT_TYPE_PERCENT, 'discount_amount' => 0]);
    $partial = Course::factory()->make(['price' => 25, 'discount_type' => Course::DISCOUNT_TYPE_PERCENT]);

    expect($zeroAmount->isDiscounted())->toBeFalse()
        ->and($zeroAmount->effectivePrice())->toBe(25.0)
        ->and($partial->isDiscounted())->toBeFalse()
        ->and($partial->effectivePrice())->toBe(25.0);
});

test('the introduction video url is rewritten for embedding', function () {
    $youtube = Course::factory()->make(['intro_video_url' => 'https://www.youtube.com/watch?v=dQw4w9WgXcQ&t=10s']);
    $short = Course::factory()->make(['intro_video_url' => 'https://youtu.be/dQw4w9WgXcQ']);
    $vimeo = Course::factory()->make(['intro_video_url' => 'https://vimeo.com/76979871']);
    $direct = Course::factory()->make(['intro_video_url' => 'https://cdn.site.com/intro.mp4']);
    $plain = Course::factory()->make(['intro_video_url' => 'https://site.com/blog/post']);
    $unset = Course::factory()->make();

    expect($youtube->introVideoEmbedUrl())->toBe('https://www.youtube.com/embed/dQw4w9WgXcQ')
        ->and($short->introVideoEmbedUrl())->toBe('https://www.youtube.com/embed/dQw4w9WgXcQ')
        ->and($vimeo->introVideoEmbedUrl())->toBe('https://player.vimeo.com/video/76979871')
        ->and($direct->introVideoEmbedUrl())->toBe('https://cdn.site.com/intro.mp4')
        ->and($direct->hasDirectIntroVideo())->toBeTrue()
        ->and($plain->introVideoEmbedUrl())->toBeNull()
        ->and($plain->hasDirectIntroVideo())->toBeFalse()
        ->and($unset->introVideoEmbedUrl())->toBeNull();
});

test('the course page shows the discounted selling price', function () {
    $course = Course::factory()->published()->create([
        'price' => 90,
        'discount_type' => Course::DISCOUNT_TYPE_PERCENT,
        'discount_amount' => 20,
    ]);

    $this->get("/courses/{$course->slug}")
        ->assertSuccessful()
        ->assertSee('$72.00', false)
        ->assertSee('$90.00', false)
        ->assertSee('line-through', false);
});

test('the course page embeds the intro video', function () {
    $course = Course::factory()->published()->create([
        'intro_video_url' => 'https://www.youtube.com/watch?v=dQw4w9WgXcQ',
    ]);

    $this->get("/courses/{$course->slug}")
        ->assertSuccessful()
        ->assertSee('youtube.com/embed/dQw4w9WgXcQ', false);
});

test('deleting the parent category detaches its child categories', function () {
    $parent = Category::factory()->create();
    $child = Category::factory()->create(['parent_id' => $parent->id]);

    $parent->delete();

    $child->refresh();

    expect($child->parent_id)->toBeNull()
        ->and(Category::count())->toBe(1);
});

test('deleting a category cascades to its courses', function () {
    $category = Category::factory()->create();
    Course::factory()->count(2)->create(['category_id' => $category->id]);

    $category->delete();

    expect(Course::count())->toBe(0);
});
