@props(['seed' => 'course', 'icon' => null, 'label' => null, 'class' => ''])

@php
    $tones = [
        ['from-indigo-400', 'to-violet-500', 'bg-indigo-500/10 text-white'],
        ['from-sky-400', 'to-blue-500', 'bg-sky-500/10 text-white'],
        ['from-emerald-400', 'to-teal-500', 'bg-emerald-500/10 text-white'],
        ['from-amber-400', 'to-orange-500', 'bg-amber-500/10 text-white'],
        ['from-rose-400', 'to-pink-500', 'bg-rose-500/10 text-white'],
        ['from-fuchsia-400', 'to-purple-500', 'bg-fuchsia-500/10 text-white'],
    ];

    $tone = $tones[abs(crc32((string) $seed)) % count($tones)];
@endphp

<div {{ $attributes->merge(['class' => "relative isolate flex items-center justify-center overflow-hidden bg-gradient-to-br {$tone[0]} {$tone[1]} {$class}"]) }} role="img" @if ($label !== null) aria-label="{{ $label }}"
@endif>
    {{-- geometric pattern --}}
    <svg class="pointer-events-none absolute inset-0 size-full opacity-25" xmlns="http://www.w3.org/2000/svg"
        aria-hidden="true">
        <defs>
            <pattern id="dots-{{ abs(crc32((string) $seed)) }}" width="22" height="22" patternUnits="userSpaceOnUse">
                <circle cx="1.5" cy="1.5" r="1.25" fill="white" fill-opacity="0.5" />
            </pattern>
        </defs>
        <rect width="100%" height="100%" fill="url(#dots-{{ abs(crc32((string) $seed)) }})" />
        <circle cx="85%" cy="90%" r="46%" fill="white" fill-opacity="0.08" />
        <circle cx="12%" cy="108%" r="30%" fill="white" fill-opacity="0.1" />
    </svg>

    @if ($icon !== null)
        <span class="relative z-10 {{ $tone[2] }} grid size-11
                           place-items-center rounded-2xl
                           ring-1 ring-white/40 backdrop-blur-sm">
            @if ($icon === 'play')
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="size-6"
                    aria-hidden="true">
                    <path d="M8 5.14v14l11-7-11-7Z" />
                </svg>
            @endif
        </span>
    @endif
</div>
