<?php

use App\Models\Lesson;

test('youtube watch links are rewritten to the embed player', function () {
    expect(Lesson::factory()->make([
        'video_url' => 'https://www.youtube.com/watch?v=dQw4w9WgXcQ',
    ])->embedUrl())->toBe('https://www.youtube.com/embed/dQw4w9WgXcQ')
        ->and(Lesson::factory()->make([
            'video_url' => 'https://www.youtube.com/watch?t=42&v=dQw4w9WgXcQ',
        ])->embedUrl())->toBe('https://www.youtube.com/embed/dQw4w9WgXcQ');
});

test('youtube short share and shorts links are rewritten too', function () {
    expect(Lesson::factory()->make([
        'video_url' => 'https://youtu.be/dQw4w9WgXcQ',
    ])->embedUrl())->toBe('https://www.youtube.com/embed/dQw4w9WgXcQ')
        ->and(Lesson::factory()->make([
            'video_url' => 'https://www.youtube.com/shorts/AuUpBE7wBPs',
        ])->embedUrl())->toBe('https://www.youtube.com/embed/AuUpBE7wBPs');
});

test('youtube embed urls can enable the iframe player api for progress tracking', function () {
    expect(Lesson::factory()->make([
        'video_url' => 'https://www.youtube.com/watch?v=dQw4w9WgXcQ',
    ])->embedUrl(enablePlayerApi: true))->toBe('https://www.youtube.com/embed/dQw4w9WgXcQ?enablejsapi=1&playsinline=1');
});

test('vimeo links are rewritten to the player', function () {
    expect(Lesson::factory()->make([
        'video_url' => 'https://vimeo.com/148751763',
    ])->embedUrl())->toBe('https://player.vimeo.com/video/148751763')
        ->and(Lesson::factory()->make([
            'video_url' => 'https://vimeo.com/video/148751763',
        ])->embedUrl())->toBe('https://player.vimeo.com/video/148751763');
});

test('direct media file urls pass through unchanged and are flagged as direct video', function () {
    $lesson = Lesson::factory()->make([
        'video_url' => 'https://storage.googleapis.com/gtv-videos-bucket/sample/BigBuckBunny.mp4',
    ]);

    expect($lesson->embedUrl())->toBe('https://storage.googleapis.com/gtv-videos-bucket/sample/BigBuckBunny.mp4')
        ->and($lesson->isDirectVideo())->toBeTrue();
});

test('page style urls are not direct videos', function () {
    expect(Lesson::factory()->make([
        'video_url' => 'https://www.youtube.com/watch?v=dQw4w9WgXcQ',
    ])->isDirectVideo())->toBeFalse()
        ->and(Lesson::factory()->make()->isDirectVideo())->toBeFalse();
});

test('a lesson without a video has no embed url', function () {
    expect(Lesson::factory()->make()->embedUrl())->toBe('');
});
