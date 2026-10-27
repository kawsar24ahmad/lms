<?php

use App\Models\Lesson;
use Livewire\Component;
use App\Models\LessonNote;

new class extends Component {
    public Lesson $lesson;

    public $title = '';
    public $content = '';

    protected $rules = [
        'title' => 'nullable|string|max:255',
        'content' => 'required|string',
    ];

    public function mount(Lesson $lesson)
    {
        $this->lesson = $lesson;
    }

    public function saveNote()
    {
        $this->validate();

        $this->lesson->notes()->create([
            'user_id' => auth()->id(),
            'title' => $this->title,
            'content' => $this->content,
        ]);

        // ফর্ম ফিল্ড রিসেট করা
        $this->reset(['title', 'content']);

        session()->flash('message', 'Note saved successfully!');
    }

    public function deleteNote($noteId)
    {
        $note = LessonNote::find($noteId);

        if ($note && $note->user_id === auth()->id()) {
            $note->delete();
        }
    }

    // Computed Property বা হেলপার ফাংশন যা অটোমেটিক লেটেস্ট ডাটা ফেচ করবে
    public function getNotesProperty()
    {
        return $this->lesson->notes()->where('user_id', auth()->id())->latest()->get();
    }
};
?>

<div class="space-y-6">

    <!-- নতুন নোট লেখার ফর্ম -->
    <form wire:submit="saveNote" class="rounded-xl bg-slate-50 p-4 ring-1 ring-slate-200/70">
        <h4 class="text-xs font-semibold uppercase tracking-wider text-slate-500 mb-2">Add New Note</h4>

        @if (session()->has('message'))
            <p class="text-xs text-emerald-600 mb-2 font-medium">{{ session('message') }}</p>
        @endif

        {{-- <input type="text" wire:model="title" placeholder="Note title (optional)…"
            class="input mb-3 w-full text-sm"> --}}

        <textarea wire:model="content" rows="3" placeholder="Jot down your takeaways from this lesson…"
            class="input resize-y placeholder:text-slate-400 w-full" required></textarea>

        <div class="mt-3 flex justify-end">
            <button type="submit" class="btn btn-primary btn-sm flex items-center gap-2">
                <span wire:loading.remove>Save Note</span>
                <span wire:loading>Saving...</span>
            </button>
        </div>
    </form>

    <!-- স্টুডেন্টের সেভ করা নোটগুলোর লিস্ট -->
    <div class="space-y-3">
        <h4 class="text-xs font-semibold uppercase tracking-wider text-slate-500">Your Notes for this Lesson</h4>

        <!-- এখানে $notes এর বদলে সরাসরি $this->notes ব্যবহার করা হয়েছে -->
        @forelse ($this->notes as $note)
            <div class="rounded-xl bg-white p-4 ring-1 ring-slate-200 shadow-sm relative group" wire:key="{{ $note->id }}">
                {{-- @if($note->title)
                <h5 class="font-semibold text-sm text-slate-800 mb-1">{{ $note->title }}</h5>
                @endif --}}
                <p class="text-sm text-slate-600 whitespace-pre-line">{{ $note->content }}</p>
                <div class="mt-3 flex items-center justify-between text-xs text-slate-400">
                    <span>{{ $note->created_at->diffForHumans() }}</span>

                    <!-- লাইভওয়্যার দিয়ে ইনস্ট্যান্ট ডিলিট -->
                    <button type="button" wire:click="deleteNote({{ $note->id }})"
                        wire:confirm="Are you sure you want to delete this note?" class="text-red-500 hover:underline">
                        Delete
                    </button>
                </div>
            </div>
        @empty
            <p class="text-xs text-slate-400 italic text-center py-4">No notes added for this lesson yet.</p>
        @endforelse
    </div>
</div>