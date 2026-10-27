@props(['rating', 'count' => null, 'size' => 'sm'])

@php
    $value = (float) $rating;

    $starClass = [
        'xs' => 'size-3',
        'sm' => 'size-3.5',
        'md' => 'size-4',
        'lg' => 'size-5',
    ][$size] ?? 'size-3.5';

    // Round to nearest half star.

    for ($i = 1; $i <= 5; $i++) {
        $stars[] = $value >= $i - 0.25 ? 'full' : ($value >= $i - 0.75 ? 'half' : 'empty');
    }
@endphp

<span {{ $attributes->merge(['class' => 'inline-flex items-center gap-1']) }}>
    <span class="flex items-center gap-0.5 text-amber-400">
        @foreach ($stars as $star)
            @if ($star === 'full')
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="{{ $starClass }}"><path d="M10.788 3.21c.448-1.077 1.976-1.077 2.424 0l2.082 5.007 5.404.433c1.164.093 1.636 1.545.749 2.305l-4.117 3.527 1.257 5.273c.271 1.136-.964 2.033-1.96 1.425L12 18.354 7.373 21.18c-.996.608-2.231-.29-1.96-1.425l1.257-5.273-4.117-3.527c-.887-.76-.415-2.212.749-2.305l5.404-.433 2.082-5.006Z" /></svg>
            @elseif ($star === 'half')
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" class="{{ $starClass }}">
                    <defs><linearGradient id="half-{{ $loop->index }}"><stop offset="55%" stop-color="currentColor" /><stop offset="55%" stop-color="currentColor" stop-opacity="0.25" /></linearGradient></defs>
                    <path fill="url(#half-{{ $loop->index }})" d="M10.788 3.21c.448-1.077 1.976-1.077 2.424 0l2.082 5.007 5.404.433c1.164.093 1.636 1.545.749 2.305l-4.117 3.527 1.257 5.273c.271 1.136-.964 2.033-1.96 1.425L12 18.354 7.373 21.18c-.996.608-2.231-.29-1.96-1.425l1.257-5.273-4.117-3.527c-.887-.76-.415-2.212.749-2.305l5.404-.433 2.082-5.006Z" />
                </svg>
            @else
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" fill-opacity="0.25" class="text-amber-400 {{ $starClass }}"><path d="M10.788 3.21c.448-1.077 1.976-1.077 2.424 0l2.082 5.007 5.404.433c1.164.093 1.636 1.545.749 2.305l-4.117 3.527 1.257 5.273c.271 1.136-.964 2.033-1.96 1.425L12 18.354 7.373 21.18c-.996.608-2.231-.29-1.96-1.425l1.257-5.273-4.117-3.527c-.887-.76-.415-2.212.749-2.305l5.404-.433 2.082-5.006Z" /></svg>
            @endif
        @endforeach
    </span>

    <span class="text-xs font-bold text-slate-700">{{ number_format($value, 1) }}</span>

    @if ($count !== null)
        <span class="text-xs font-normal text-slate-400">({{ number_format($count) }})</span>
    @endif
</span>
