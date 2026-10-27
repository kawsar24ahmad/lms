<?php

namespace App\Http\Controllers;

use App\Models\Lesson;
use App\Models\LessonNote;
use Illuminate\Http\Request;

class LessonNoteController extends Controller
{
    public function store(Request $request, Lesson $lesson)
    {
        $validated = $request->validate([
            'title' => 'nullable|string|max:255',
            'content' => 'required|string',
        ]);

        $lesson->notes()->create([
            'user_id' => auth()->id(),
            'title' => $validated['title'] ?? null,
            'content' => $validated['content'],
        ]);

        return back()->with('success', 'Note saved successfully!');
    }

    /**
     * নোট ডিলিট করা
     */
    public function destroy(LessonNote $note)
    {
        // সিকিউরিটি চেক: শুধু নোটের মালিকই যেন ডিলিট করতে পারে
        if ($note->user_id !== auth()->id()) {
            abort(403);
        }

        $note->delete();

        return back()->with('success', 'Note deleted successfully!');
    }
}
