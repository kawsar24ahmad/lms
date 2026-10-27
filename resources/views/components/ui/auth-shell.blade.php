@props(['angle' => 'left'])

@php
    $highlights = [
        'Master in-demand skills at your own pace',
        'Learn from verified expert instructors',
        'Earn certificates that recruiters trust',
    ];
@endphp

<div class="flex grow flex-col bg-gradient-to-br from-brand-700 via-brand-800 to-slate-900 p-8 text-white lg:p-10">
    <a href="{{ route('courses.index') }}" class="flex items-center gap-2.5">
        <span class="grid size-9 place-items-center rounded-xl bg-white/10 ring-1 ring-white/20">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor" class="size-5">
                <path stroke-linecap="round" stroke-linejoin="round" d="M4.26 10.147a60.438 60.438 0 0 0-.491 6.347A48.628 48.628 0 0 1 12 20.904a48.628 48.628 0 0 1 8.232-4.41 60.46 60.46 0 0 0-.491-6.347m-15.482 0a50.636 50.636 0 0 0-2.658-.8mA50.565 50.565 0 0 1 12 13.489a50.701 50.701 0 0 1 7.74-3.342M6.75 15a.75.75 0 1 0 0-1.5.75.75 0 0 0 0 1.5Zm0 0v-3.675A55.378 55.378 0 0 1 12 8.443m-7.007 11.55A5.981 5.981 0 0 0 6.75 15.75v-1.5" />
            </svg>
        </span>
        <span class="text-lg font-bold tracking-tight">{{ $siteSettings->displayName() }}</span>
    </a>

    <blockquote class="mt-10 max-w-sm">
        <p class="text-xl leading-relaxed font-medium">
            “I went from zero to a portfolio I'm genuinely proud of in three months — the lessons are focused, practical and never drag.”
        </p>
        <footer class="mt-5 flex items-center gap-3">
            <x-ui.avatar name="Sara Ahmed" size="sm" />
            <div>
                <p class="text-sm font-semibold">Sara Ahmed</p>
                <p class="text-xs text-white/60">Frontend Developer @ Nova</p>
            </div>
        </footer>
    </blockquote>

    <ul class="mt-8 space-y-3 border-t border-white/10 pt-8">
        @foreach ($highlights as $highlight)
            <li class="flex items-center gap-3 text-sm text-white/80">
                <span class="grid size-5 shrink-0 place-items-center rounded-full bg-emerald-400/20 text-emerald-300">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor" class="size-3"><path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5" /></svg>
                </span>
                {{ $highlight }}
            </li>
        @endforeach
    </ul>

    <dl class="mt-auto grid grid-cols-3 gap-4 pt-10 max-lg:hidden">
        @foreach ([
            ['42k', 'students'],
            ['350', 'courses'],
            ['4.8★', 'avg rating'],
        ] as [$value, $stat])
            <div>
                <dt class="text-xl font-extrabold tracking-tight">{{ $value }}</dt>
                <dd class="mt-0.5 text-xs text-white/60">{{ $stat }}</dd>
            </div>
        @endforeach
    </dl>
</div>
