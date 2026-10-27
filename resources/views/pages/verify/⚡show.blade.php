<?php

use App\Models\Certificate;
use Livewire\Attributes\Computed;
use Livewire\Component;

new class extends Component {
    /**
     * The certificate matching the URL number, or null when unknown.
     */
    #[Computed]
    public function certificate()
    {
        $number = $this->certificateNumber();

        return $number !== ''
            ? Certificate::query()->where('certificate_number', $number)->with('course.instructor', 'student')->first()
            : null;
    }

    private function certificateNumber(): string
    {
        return (string) request()->route('certificateNumber');
    }
};
?>

<div class="mx-auto max-w-xl">
    <div class="rounded-xl bg-white p-8 ring-1 ring-gray-200">
        @if ($this->certificate !== null)
            @php $certificate = $this->certificate; @endphp

            <div class="text-center">
                <div class="mx-auto mb-4 flex size-14 items-center justify-center rounded-full bg-emerald-50 text-2xl text-emerald-600 ring-2 ring-emerald-200">
                    ✓
                </div>
                <h1 class="text-2xl font-bold text-emerald-700">Certificate verified</h1>
                <p class="mt-1 text-sm text-gray-500">
                    This certificate was issued by {{ $siteSettings->displayName() }} and is authentic.
                </p>
            </div>

            <dl class="mt-8 divide-y divide-gray-100 rounded-xl ring-1 ring-gray-200">
                <div class="flex items-center justify-between px-5 py-3">
                    <dt class="text-sm font-medium text-gray-500">Awarded to</dt>
                    <dd class="text-sm font-semibold text-gray-900">{{ $certificate->student->name }}</dd>
                </div>
                <div class="flex items-center justify-between px-5 py-3">
                    <dt class="text-sm font-medium text-gray-500">Course</dt>
                    <dd class="text-sm font-semibold text-gray-900">{{ $certificate->course->title }}</dd>
                </div>
                <div class="flex items-center justify-between px-5 py-3">
                    <dt class="text-sm font-medium text-gray-500">Instructor</dt>
                    <dd class="text-sm text-gray-700">{{ $certificate->course->instructor?->name }}</dd>
                </div>
                @if ($certificate->final_score !== null)
                    <div class="flex items-center justify-between px-5 py-3">
                        <dt class="text-sm font-medium text-gray-500">Final score</dt>
                        <dd class="text-sm font-semibold text-gray-900">{{ number_format($certificate->final_score, 0) }}%</dd>
                    </div>
                @endif
                <div class="flex items-center justify-between px-5 py-3">
                    <dt class="text-sm font-medium text-gray-500">Issued</dt>
                    <dd class="text-sm text-gray-700">{{ $certificate->issued_at?->format('M j, Y') }}</dd>
                </div>
            </dl>
        @else
            <div class="text-center">
                <div class="mx-auto mb-4 flex size-14 items-center justify-center rounded-full bg-red-50 text-2xl text-red-500 ring-2 ring-red-200">
                    ✕
                </div>
                <h1 class="text-2xl font-bold text-gray-900">No certificate found</h1>
                <p class="mt-2 text-sm text-gray-500">
                    We could not verify a certificate with the number
                    <span class="font-mono text-gray-700">{{ request()->route('certificateNumber') }}</span>.
                    Please double-check it.
                </p>
            </div>
        @endif
    </div>

    <p class="mt-4 text-center text-xs text-gray-400">
        {{ $siteSettings->displayName() }} — public certificate verification
    </p>
</div>
