<?php

use App\Models\Course;
use App\Models\Certificate;
use Livewire\Attributes\Layout;
use Livewire\Component;

new #[Layout('layouts.minimal')] class extends Component {
    public Certificate $certificate;

    public function mount(?Certificate $certificate = null): void
    {
        if ($certificate !== null) {
            $this->certificate = $certificate;
        }

        $this->certificate->loadMissing('course.instructor', 'student');
    }

    /**
     * Guard: only the student who earned the certificate may open it.
     */
    public function boot(): void
    {
        abort_unless(auth()->id() === $this->certificate->user_id, 403);
    }
};
?>

<div class="mx-auto max-w-3xl">
    <div class="mb-4 flex items-center justify-between no-print">
        <a href="{{ route('dashboard') }}"
            class="inline-flex items-center gap-1 text-sm text-gray-500 hover:text-gray-900">
            ← Back to my courses
        </a>

        <button type="button" onclick="window.print()"
            class="rounded-lg bg-gray-900 px-4 py-2 text-sm font-semibold text-white transition hover:bg-gray-700">
            Print / Save as PDF
        </button>
    </div>

    <article
        class="cert-sheet rounded-2xl border-8 border-double border-amber-400 bg-white px-8 py-14 text-center shadow-sm sm:px-16">
        <div class="flex items-center justify-center gap-2 text-amber-600">
            @if ($siteSettings->logo)
                <img src="{{ \Storage::url($siteSettings->logo) }}" alt="{{ $siteSettings->site_name }}"
                    class="h-8 w-auto object-contain">
                <span class="text-sm font-bold uppercase tracking-[0.25em]">{{ $siteSettings->site_name }}</span>
            @else
                <span class="text-sm font-bold uppercase tracking-[0.25em]">{{ $siteSettings->site_name }}</span>
            @endif
        </div>

        <p class="mt-8 text-xs font-medium uppercase tracking-[0.3em] text-gray-400">Certificate of completion</p>
        <p class="mt-6 text-sm text-gray-500">This certifies that</p>
        <p class="mt-2 font-serif text-3xl font-bold text-gray-900">{{ $certificate->student->name }}</p>
        <p class="mt-6 text-sm text-gray-500">has successfully completed the course</p>
        <p class="mt-2 text-2xl font-semibold text-amber-700">{{ $certificate->course->title }}</p>

        @if ($certificate->final_score !== null)
            <p class="mt-1 text-sm text-gray-500">
                with a final score of <span
                    class="font-semibold text-gray-900">{{ number_format($certificate->final_score, 0) }}%</span>
            </p>
        @endif

        <div class="mt-12 grid grid-cols-1 items-end gap-8 text-sm text-gray-600 sm:grid-cols-3">
            <div>
                <p class="font-serif text-lg font-medium text-gray-900">{{ $certificate->course->instructor?->name }}
                </p>
                <p class="mt-1 border-t border-gray-300 pt-1 text-xs uppercase tracking-wide text-gray-400">Course
                    instructor</p>
            </div>
            <div class="flex flex-col items-center">
                <div
                    class="flex size-12 items-center justify-center rounded-full bg-amber-50 text-amber-600 ring-2 ring-amber-400">
                    🏆
                </div>
                <p class="mt-2 border-t border-gray-300 pt-1 text-xs uppercase tracking-wide text-gray-400">Awarded</p>
            </div>
            <div>
                <p class="font-serif text-lg font-medium text-gray-900">{{ $certificate->issued_at?->format('M j, Y') }}
                </p>
                <p class="mt-1 border-t border-gray-300 pt-1 text-xs uppercase tracking-wide text-gray-400">Date of
                    issue</p>
            </div>
        </div>

        <p class="mt-10 text-xs text-gray-400">
            Certificate number <span class="font-mono">{{ $certificate->certificate_number }}</span>
            ·
            Verifed
        </p>
    </article>
</div>