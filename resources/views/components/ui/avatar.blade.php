@props(['name', 'size' => 'md'])

@php
    $palettes = [
        'from-indigo-500 to-violet-600',
        'from-sky-500 to-blue-600',
        'from-emerald-500 to-teal-600',
        'from-amber-500 to-orange-600',
        'from-rose-500 to-pink-600',
        'from-fuchsia-500 to-purple-600',
    ];

    $initials = collect(explode(' ', trim((string) $name)))
        ->filter()
        ->take(2)
        ->map(fn (string $part) => mb_substr($part, 0, 1))
        ->implode('');

    $palette = $palettes[abs(crc32((string) $name)) % count($palettes)];

    $sizes = [
        'xs' => ['size-7', 'text-[10px]'],
        'sm' => ['size-9', 'text-xs'],
        'md' => ['size-11', 'text-sm'],
        'lg' => ['size-14', 'text-base'],
        'xl' => ['size-20', 'text-xl'],
    ];

    [$width, $text] = $sizes[$size] ?? $sizes['md'];
@endphp

<span
    {{ $attributes->merge(['class' => "grid shrink-0 place-items-center overflow-hidden rounded-full bg-gradient-to-br {$palette} font-bold text-white {$width} {$text}"]) }}
    aria-hidden="true"
>
    {{ $initials }}
</span>
