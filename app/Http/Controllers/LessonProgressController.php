<?php

namespace App\Http\Controllers;

use App\Models\Lesson;
use App\Models\LessonProgress;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class LessonProgressController extends Controller
{
    /**
     * Persist the player position reported by the lesson video. Enrolled
     * students (and anyone watching a free preview) may track progress, and
     * lessons complete automatically once the watch threshold is reached.
     */
    public function update(Request $request, Lesson $lesson): JsonResponse
    {
        $validated = $request->validate([
            'last_position' => ['required', 'integer', 'min:0', 'max:86400'],
            'furthest_position' => ['nullable', 'integer', 'min:0', 'max:86400'],
            'seconds_watched' => ['required', 'integer', 'min:0', 'max:65535'],
            'duration_seconds' => ['nullable', 'integer', 'min:0', 'max:86400'],
        ]);

        $user = $request->user();

        if ($user === null) {
            abort(401);
        }

        $course = $lesson->section()->firstOrFail()->course;

        if (! $lesson->is_preview && ! $course->hasEnrolled($user)) {
            abort(403);
        }

        $lastPosition = (int) $validated['last_position'];

        $progress = LessonProgress::recordWatch(
            $user,
            $lesson,
            $lastPosition,
            (int) ($validated['furthest_position'] ?? $lastPosition),
            (int) $validated['seconds_watched'],
            (int) ($validated['duration_seconds'] ?? 0),
        );

        return response()->json([
            'success' => true,
            'completed' => $progress->is_completed,
            'percentage' => $progress->percentage(),
            'last_position' => $progress->last_position,
            'furthest_position' => $progress->furthest_position,
            'seconds_watched' => $progress->seconds_watched,
            'duration_seconds' => $progress->duration_seconds,
        ]);
    }
}
