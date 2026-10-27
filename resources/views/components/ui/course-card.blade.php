@props(['title', 'image' => null, 'goal' => null, 'instructor' => null, 'category' => null, 'rating' => null, 'ratingCount' => null, 'price' => null, 'originalPrice' => null, 'meta' => null, 'badge' => null, 'progress' => null, 'progressLabel' => null, 'href' => '#'])

@php
    $effective = $price ?? 0;
    $free = (float) $effective <= 0;
@endphp

<article class="card card-hover group relative flex h-full flex-col overflow-hidden">
    <a href="{{ $href }}" class="flex grow flex-col" tabindex="-1">
        @if ($image)
            <img src="{{ Storage::disk('public')->url($image)  }}" alt="{{ $title }}"
                class="aspect-video w-full object-cover">
        @else
            <x-ui.thumb :seed="$title" :label="$title" class="aspect-video w-full" />
        @endif


        <div class="flex grow flex-col gap-2 p-4 sm:p-5">
            <div class="flex items-center justify-between gap-2">
                <span class="truncate text-[11px] font-semibold uppercase tracking-widest text-brand-600">
                    {{ $category ?? 'General' }}
                </span>
                @if ($badge !== null)
                    <span class="badge bg-amber-100 text-amber-700 ring-1 ring-amber-200/70">{{ $badge }}</span>
                @endif
            </div>

            <h3
                class="text-[15px] leading-snug font-bold text-slate-900 transition group-hover:text-brand-700 sm:text-base">
                {{ $title }}
            </h3>

            @if ($goal !== null)
                <p class="line-clamp-2 text-sm leading-relaxed text-slate-500">{{ \Str::words($goal, 10, '...') }}</p>
            @endif

            @if ($instructor !== null)
                <p class="text-[13px] text-slate-500">{{ $instructor }}</p>
            @endif

            @if ($rating !== null)
                <x-ui.rating :rating="$rating" :count="$ratingCount" />
            @endif

            @if ($meta !== null)
                <p class="flex items-center gap-1.5 text-xs text-slate-500">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8"
                        stroke="currentColor" class="size-3.5 text-slate-400">
                        <path stroke-linecap="round" stroke-linejoin="round"
                            d="M12 6v6h4.5m4.5 0a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
                    </svg>
                    {{ $meta }}
                </p>
            @endif

            @if ($progress !== null)
                <div>
                    <div class="mb-1 flex items-center justify-between text-xs font-medium text-slate-500">
                        <span>{{ $progressLabel }}</span>
                        <span>{{ (int) $progress }}%</span>
                    </div>
                    <div class="progress-track">
                        <div class="progress-fill" style="width: {{ (int) min(100, max(0, $progress)) }}%"></div>
                    </div>
                </div>
            @endif

            <div class="mt-auto flex items-center justify-between gap-2 pt-2">
                <span class="text-lg font-extrabold tracking-tight {{ $free ? 'text-emerald-600' : 'text-slate-900' }}">
                    @if ($originalPrice !== null)
                        <span
                            class="mr-1 align-middle text-xs font-medium text-slate-400 line-through">${{ $originalPrice }}</span>
                    @endif
                    {{ $free ? 'Free' : '$' . number_format((float) $effective, 2) }}
                </span>

                <span
                    class="inline-flex items-center gap-1 text-[13px] font-semibold text-brand-600  transition group-hover:opacity-100">
                    View
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2"
                        stroke="currentColor" class="size-3.5 transition-transform group-hover:translate-x-0.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5 21 12m0 0-7.5 7.5M21 12H3" />
                    </svg>
                </span>
            </div>
        </div>
    </a>

    @isset($footer)
        <div class="border-t border-slate-100 px-4 py-2.5 sm:px-5">
            {{ $footer }}
        </div>
    @endisset
</article>
