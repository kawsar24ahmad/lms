<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
    <meta name="theme-color" content="#ffffff">

    <title>{{ $title ?? $siteSettings->displayTitle() }}</title>

    @if ($siteSettings->displayDescription())
        <meta name="description" content="{{ $siteSettings->displayDescription() }}">
    @endif

    <meta property="og:title" content="{{ $siteSettings->displayTitle() }}">
    <meta property="og:type" content="website">
    @if ($siteSettings->displayDescription())
        <meta property="og:description" content="{{ $siteSettings->displayDescription() }}">
    @endif
    @if ($siteSettings->meta_image)
        <meta property="og:image"
            content="{{ \Illuminate\Support\Facades\Storage::disk('public')->url($siteSettings->meta_image) }}">
    @endif

    @if ($siteSettings->favicon)
        <link rel="icon" href="{{ \Illuminate\Support\Facades\Storage::disk('public')->url($siteSettings->favicon) }}">
    @else
        <link rel="icon" href="/favicon.ico" sizes="any">
    @endif

    @fonts

    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <style>
        /* Lift the floating cart bar above the mobile glass tab bar. */
        @media (max-width: 1023px) {
            body:has([data-cart-bar]) [data-cart-bar] {
                bottom: calc(5.5rem + env(safe-area-inset-bottom));
            }
        }
    </style>

    @livewireStyles
</head>

<body x-data="{ mobileNav: false }" @keydown.escape.window="mobileNav = false"
    class="flex min-h-screen flex-col bg-white font-sans text-slate-900 antialiased">
    <x-ui.page-loader />

    {{-- ============================ Header ============================ --}}
    <header x-data="{ scrolled: false, showCatMenu: false, showProfile: false, mobileSearch: false }"
        @scroll.window="scrolled = window.scrollY > 8" @click.outside="showCatMenu = false; showProfile = false"
        @keydown.escape.window="showCatMenu = false; showProfile = false; mobileSearch = false"
        @profile-open.window="showProfile = true; mobileSearch = false; window.scrollTo({ top: 0, behavior: 'smooth' })"
        class="sticky top-0 z-50 border-b backdrop-blur-xl backdrop-saturate-150 transition-[background-color,border-color,box-shadow] duration-300"
        :class="scrolled || mobileNav || mobileSearch ?
            'border-slate-200/80 bg-white/90 shadow-[0_10px_30px_-18px_rgba(15,23,42,0.35)]' :
            'border-slate-200/60 bg-white/80'">
        <div class="mx-auto flex h-14 max-w-7xl items-center gap-2 px-3 sm:h-16 sm:gap-3 sm:px-6 lg:px-8">
            {{-- Brand --}}
            <button type="button"
                class="-ml-1 grid size-10 shrink-0 place-items-center rounded-xl text-slate-600 transition hover:bg-slate-100 active:scale-95 lg:hidden"
                @click="mobileNav = true" aria-label="Open menu" aria-controls="mobile-nav"
                :aria-expanded="mobileNav">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8"
                    stroke="currentColor" class="size-5.5">
                    <path stroke-linecap="round" stroke-linejoin="round"
                        d="M3.75 6.75h16.5M3.75 12h16.5m-16.5 5.25h16.5" />
                </svg>
            </button>

            <a href="{{ route('home') }}" class="flex min-w-0 items-center gap-2 sm:gap-2.5">
                @if ($siteSettings->logo)
                    <img src="{{ \Illuminate\Support\Facades\Storage::disk('public')->url($siteSettings->logo) }}"
                        alt="{{ $siteSettings->displayName() }}"
                        class="h-8 w-auto max-w-28 shrink-0 object-contain sm:h-9 sm:max-w-40">
                @else
                    <span
                        class="grid size-8 shrink-0 place-items-center rounded-xl bg-gradient-to-br from-brand-500 to-brand-700 text-white shadow-sm sm:size-9">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8"
                            stroke="currentColor" class="size-4.5 sm:size-5">
                            <path stroke-linecap="round" stroke-linejoin="round"
                                d="M4.26 10.147a60.438 60.438 0 0 0-.491 6.347A48.628 48.628 0 0 1 12 20.904a48.628 48.628 0 0 1 8.232-4.41 60.46 60.46 0 0 0-.491-6.347m-15.482 0a50.636 50.636 0 0 0-2.658-.8mA50.565 50.565 0 0 1 12 13.489a50.701 50.701 0 0 1 7.74-3.342M6.75 15a.75.75 0 1 0 0-1.5.75.75 0 0 0 0 1.5Zm0 0v-3.675A55.378 55.378 0 0 1 12 8.443m-7.007 11.55A5.981 5.981 0 0 0 6.75 15.75v-1.5" />
                        </svg>
                    </span>
                @endif
                <span class="truncate text-[15px] font-bold tracking-tight text-slate-900 sm:text-lg">
                    {{ $siteSettings->displayName() }}
                </span>
            </a>

            {{-- Desktop navigation --}}
            <nav class="ml-4 hidden items-center gap-1 lg:flex">
                <a href="{{ route('courses.index') }}"
                    class="rounded-lg px-3 py-2 text-sm font-medium text-slate-600 transition hover:bg-slate-100 hover:text-slate-900">
                    Courses
                </a>

                <button type="button" @click="showCatMenu = !showCatMenu; showProfile = false"
                    class="flex items-center gap-1.5 rounded-lg px-3 py-2 text-sm font-medium text-slate-600 transition hover:bg-slate-100 hover:text-slate-900"
                    :class="showCatMenu ? 'bg-slate-100 text-slate-900' : ''">
                    Categories
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2"
                        stroke="currentColor" class="size-3.5 transition-transform"
                        :class="showCatMenu && 'rotate-180'">
                        <path stroke-linecap="round" stroke-linejoin="round" d="m19.5 8.25-7.5 7.5-7.5-7.5" />
                    </svg>
                </button>

                @auth
                    <a href="{{ route('dashboard') }}"
                        class="rounded-lg px-3 py-2 text-sm font-medium text-slate-600 transition hover:bg-slate-100 hover:text-slate-900">
                        My learning
                    </a>
                    <a href="{{ route('assignments.index') }}"
                        class="rounded-lg px-3 py-2 text-sm font-medium text-slate-600 transition hover:bg-slate-100 hover:text-slate-900">
                        Assignments
                    </a>
                @endauth
            </nav>

            {{-- Search --}}
            <form action="{{ route('courses.index') }}" method="GET"
                class="ml-auto hidden flex-1 justify-end md:flex md:max-w-sm lg:max-w-md">
                <label for="header-search" class="group relative w-full">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2"
                        stroke="currentColor"
                        class="pointer-events-none absolute left-3.5 top-1/2 size-4 -translate-y-1/2 text-slate-400 transition group-focus-within:text-brand-600">
                        <path stroke-linecap="round" stroke-linejoin="round"
                            d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607Z" />
                    </svg>
                    <input id="header-search" type="search" name="search" value="{{ request('search') }}"
                        placeholder="Search courses..."
                        class="w-full rounded-full border-0 bg-slate-100 py-2.5 pl-10 pr-4 text-sm text-slate-900 transition placeholder:text-slate-500 focus:bg-white focus:ring-2 focus:ring-brand-500" />
                </label>
            </form>

            {{-- Right actions --}}
            <div class="relative ml-auto flex shrink-0 items-center gap-0.5 sm:gap-1.5">
                {{-- Search (mobile) --}}
                <button type="button" @click="mobileSearch = !mobileSearch; showProfile = false; showCatMenu = false"
                    class="grid size-9 place-items-center rounded-xl text-slate-600 transition hover:bg-slate-100 active:scale-95 md:hidden"
                    aria-label="Search courses" aria-controls="mobile-search" :aria-expanded="mobileSearch">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2"
                        stroke="currentColor" class="size-5">
                        <path stroke-linecap="round" stroke-linejoin="round"
                            d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607Z" />
                    </svg>
                </button>

                {{-- Cart --}}
                <livewire:cart-count />

                @guest
                    <a href="{{ route('login') }}" class="btn btn-ghost btn-sm hidden sm:inline-flex">
                        Log in
                    </a>
                    <a href="{{ route('register') }}" class="btn btn-primary btn-sm">
                        Sign up
                    </a>
                @endguest

                @auth
                    <button type="button" @click="showProfile = !showProfile; showCatMenu = false"
                        class="ml-0.5 flex items-center gap-1.5 rounded-full p-0.5 transition hover:bg-slate-100 sm:pr-2.5"
                        aria-haspopup="menu" :aria-expanded="showProfile">
                        <span
                            class="grid size-8 shrink-0 place-items-center overflow-hidden rounded-full bg-gradient-to-br from-brand-500 to-brand-700 text-[13px] font-bold text-white ring-2 ring-white sm:size-9 sm:text-sm">
                            {{ strtoupper(mb_substr(auth()->user()->name, 0, 1)) }}
                        </span>
                        <span class="hidden max-w-24 truncate text-sm font-medium text-slate-700 sm:block xl:max-w-none">
                            {{ auth()->user()->name }}
                        </span>
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2"
                            stroke="currentColor" class="hidden size-3.5 text-slate-500 sm:block" x-show="!showProfile">
                            <path stroke-linecap="round" stroke-linejoin="round" d="m19.5 8.25-7.5 7.5-7.5-7.5" />
                        </svg>
                    </button>

                    <div x-show="showProfile" x-cloak x-transition.opacity.duration.150ms
                        class="absolute top-full right-0 z-50 mt-2">
                        <div
                            class="w-60 max-w-[calc(100vw-1.5rem)] overflow-hidden rounded-2xl border border-slate-200/80 bg-white/95 p-1.5 shadow-(--shadow-pop) backdrop-blur-xl">
                            <div class="border-b border-slate-100 px-3 pb-2.5 pt-2">
                                <p class="truncate text-sm font-semibold text-slate-900">{{ auth()->user()->name }}</p>
                                <p class="truncate text-xs text-slate-500">{{ auth()->user()->email }}</p>
                            </div>
                            <a href="{{ route('dashboard') }}"
                                class="mt-1 flex items-center gap-2.5 rounded-lg px-3 py-2 text-sm font-medium text-slate-700 transition hover:bg-slate-100">
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8"
                                    stroke="currentColor" class="size-4.5 text-slate-400">
                                    <path stroke-linecap="round" stroke-linejoin="round"
                                        d="M12 6.042A8.967 8.967 0 0 0 6 3.75c-1.052 0-2.062.18-3 .512v14.25A8.987 8.987 0 0 1 6 18c2.305 0 4.408.867 6 2.292m0-14.25a8.966 8.966 0 0 1 6-2.292c1.052 0 2.062.18 3 .512v14.25A8.987 8.987 0 0 0 18 18a8.967 8.967 0 0 0-6 2.292m0-14.25v14.25" />
                                </svg>
                                My learning
                            </a>
                            <a href="{{ route('assignments.index') }}"
                                class="flex items-center gap-2.5 rounded-lg px-3 py-2 text-sm font-medium text-slate-700 transition hover:bg-slate-100">
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8"
                                    stroke="currentColor" class="size-4.5 text-slate-400">
                                    <path stroke-linecap="round" stroke-linejoin="round"
                                        d="M9 12h3.75M9 15h3.75M9 18h3.75m3 .75H18a2.25 2.25 0 0 0 2.25-2.25V6.108c0-1.135-.845-2.098-1.976-2.192a48.424 48.424 0 0 0-1.123-.08m-5.801 0c-.065.21-.1.433-.1.664 0 .414.336.75.75.75h4.5a.75.75 0 0 0 .75-.75 2.25 2.25 0 0 0-.1-.664m-5.8 0A2.251 2.251 0 0 1 13.5 2.25H15c1.012 0 1.867.668 2.15 1.586m-5.8 0c-.376.023-.75.05-1.124.08C9.095 4.01 8.25 4.973 8.25 6.108V8.25m0 0H4.875c-.621 0-1.125.504-1.125 1.125v11.25c0 .621.504 1.125 1.125 1.125h9.75c.621 0 1.125-.504 1.125-1.125V9.375c0-.621-.504-1.125-1.125-1.125H8.25ZM6.75 12h.008v.008H6.75V12Zm0 3h.008v.008H6.75V15Zm0 3h.008v.008H6.75V18Z" />
                                </svg>
                                Assignments
                            </a>
                            <a href="{{ route('courses.index') }}"
                                class="flex items-center gap-2.5 rounded-lg px-3 py-2 text-sm font-medium text-slate-700 transition hover:bg-slate-100">
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8"
                                    stroke="currentColor" class="size-4.5 text-slate-400">
                                    <path stroke-linecap="round" stroke-linejoin="round"
                                        d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607Z" />
                                </svg>
                                Browse courses
                            </a>
                            <div class="my-1 border-t border-slate-100"></div>
                            <form method="POST" action="{{ route('logout') }}">
                                @csrf
                                <button type="submit"
                                    class="flex w-full items-center gap-2.5 rounded-lg px-3 py-2 text-sm font-medium text-rose-600 transition hover:bg-rose-50">
                                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                                        stroke-width="1.8" stroke="currentColor" class="size-4.5 text-rose-400">
                                        <path stroke-linecap="round" stroke-linejoin="round"
                                            d="M15.75 9V5.25A2.25 2.25 0 0 0 13.5 3h-6a2.25 2.25 0 0 0-2.25 2.25v13.5A2.25 2.25 0 0 0 7.5 21h6a2.25 2.25 0 0 0 2.25-2.25V15m3 0 3-3m0 0-3-3m3 3H9" />
                                    </svg>
                                    Log out
                                </button>
                            </form>
                        </div>
                    </div>
                @endauth
            </div>
        </div>

        {{-- Mobile search --}}
        <div id="mobile-search" x-show="mobileSearch" x-cloak
            x-effect="mobileSearch && $nextTick(() => $refs.mobileSearchInput?.focus())"
            x-transition:enter="transition duration-200 ease-out" x-transition:enter-start="opacity-0 -translate-y-1"
            x-transition:enter-end="opacity-100 translate-y-0" x-transition:leave="transition duration-150 ease-in"
            x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0"
            class="border-t border-slate-100 bg-white/80 px-3 pb-3 pt-2.5 backdrop-blur-xl md:hidden">
            <form action="{{ route('courses.index') }}" method="GET" class="relative">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2"
                    stroke="currentColor"
                    class="pointer-events-none absolute top-1/2 left-3 size-4 -translate-y-1/2 text-slate-400">
                    <path stroke-linecap="round" stroke-linejoin="round"
                        d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607Z" />
                </svg>
                <input x-ref="mobileSearchInput" type="search" name="search" value="{{ request('search') }}"
                    placeholder="Search courses..." autocomplete="off"
                    class="input pr-20 pl-9 text-base placeholder:text-sm sm:placeholder:text-base" />
                <button type="submit" class="btn btn-primary btn-sm absolute top-1/2 right-1.5 -translate-y-1/2">
                    Search
                </button>
            </form>
        </div>

        {{-- Categories dropdown --}}
        <div x-show="showCatMenu" x-cloak x-transition.opacity.duration.150ms
            class="absolute inset-x-0 top-full hidden lg:block">
            <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                <div
                    class="grid grid-cols-4 gap-1 rounded-b-2xl border border-t-0 border-slate-200/80 bg-white/95 p-4 shadow-(--shadow-pop) backdrop-blur-xl">
                    @forelse ($categories as $category)
                        <a href="{{ route('courses.index', ['category' => $category->id]) }}"
                            class="group rounded-xl p-3 transition hover:bg-slate-50">
                            <p class="text-sm font-semibold text-slate-900 transition group-hover:text-brand-700">
                                {{ $category->name }}
                            </p>
                            @if ($category->courses_count)
                                <p class="mt-0.5 text-xs text-slate-500">
                                    {{ $category->courses_count }} course{{ $category->courses_count === 1 ? '' : 's' }}
                                </p>
                            @endif
                        </a>
                    @empty
                        <p class="col-span-4 px-3 py-2 text-sm text-slate-500">Categories are coming soon.</p>
                    @endforelse
                </div>
            </div>
        </div>
    </header>

    {{-- ====================== Mobile slide-out nav ====================== --}}
    <div x-cloak>
        <div x-show="mobileNav" x-transition.opacity.duration.200ms @click="mobileNav = false"
            class="fixed inset-0 z-50 bg-slate-900/50 backdrop-blur-sm lg:hidden"></div>

        <aside id="mobile-nav" x-show="mobileNav" x-cloak x-transition:enter="transition-transform duration-300 ease-out"
            x-transition:enter-start="-translate-x-full" x-transition:enter-end="translate-x-0"
            x-transition:leave="transition-transform duration-200 ease-in" x-transition:leave-start="translate-x-0"
            x-transition:leave-end="-translate-x-full"
            class="fixed inset-y-0 left-0 z-50 flex w-[min(20rem,86vw)] flex-col overflow-y-auto overscroll-contain bg-white shadow-2xl lg:hidden"
            role="dialog" aria-modal="true" aria-label="Site navigation">
            <div class="flex items-center justify-between border-b border-slate-100 px-5 py-4">
                <span class="flex items-center gap-2 font-bold text-slate-900">
                    @if ($siteSettings->logo)
                        <img src="{{ \Illuminate\Support\Facades\Storage::disk('public')->url($siteSettings->logo) }}"
                            alt="{{ $siteSettings->displayName() }}" class="h-8 w-auto max-w-32 object-contain">
                    @else
                        <span
                            class="grid size-8 place-items-center rounded-lg bg-gradient-to-br from-brand-500 to-brand-700 text-white">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8"
                                stroke="currentColor" class="size-4.5">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                    d="M4.26 10.147a60.438 60.438 0 0 0-.491 6.347A48.628 48.628 0 0 1 12 20.904a48.628 48.628 0 0 1 8.232-4.41 60.46 60.46 0 0 0-.491-6.347m-15.482 0a50.636 50.636 0 0 0-2.658-.8mA50.565 50.565 0 0 1 12 13.489a50.701 50.701 0 0 1 7.74-3.342M6.75 15a.75.75 0 1 0 0-1.5.75.75 0 0 0 0 1.5Zm0 0v-3.675A55.378 55.378 0 0 1 12 8.443m-7.007 11.55A5.981 5.981 0 0 0 6.75 15.75v-1.5" />
                            </svg>
                        </span>
                    @endif
                    {{ $siteSettings->displayName() }}
                </span>
                <button type="button" @click="mobileNav = false"
                    class="grid size-9 place-items-center rounded-lg text-slate-500 transition hover:bg-slate-100"
                    aria-label="Close menu">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8"
                        stroke="currentColor" class="size-5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>

            <form action="{{ route('courses.index') }}" method="GET" class="px-4 pb-2 pt-4">
                <label for="mobile-nav-search" class="relative block">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2"
                        stroke="currentColor"
                        class="pointer-events-none absolute top-1/2 left-3 size-4 -translate-y-1/2 text-slate-400">
                        <path stroke-linecap="round" stroke-linejoin="round"
                            d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607Z" />
                    </svg>
                    <input id="mobile-nav-search" type="search" name="search" placeholder="Search courses..."
                        autocomplete="off" class="input pl-9 text-base placeholder:text-sm" />
                </label>
            </form>

            <nav class="flex flex-col gap-0.5 px-4" aria-label="Mobile navigation">
                <a href="{{ route('home') }}"
                    class="flex items-center gap-3 rounded-xl px-3 py-2.5 text-sm font-semibold text-slate-700 transition hover:bg-slate-100"
                    @click="mobileNav = false">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8"
                        stroke="currentColor" class="size-5 text-slate-400">
                        <path stroke-linecap="round" stroke-linejoin="round"
                            d="m2.25 12 8.954-8.955c.44-.439 1.152-.439 1.591 0L21.75 12M4.5 9.75v10.125c0 .621.504 1.125 1.125 1.125H9.75v-4.875c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125V21h4.125c.621 0 1.125-.504 1.125-1.125V9.75" />
                    </svg>
                    Home
                </a>
                <a href="{{ route('courses.index') }}"
                    class="flex items-center gap-3 rounded-xl px-3 py-2.5 text-sm font-semibold text-slate-700 transition hover:bg-slate-100"
                    @click="mobileNav = false">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8"
                        stroke="currentColor" class="size-5 text-slate-400">
                        <path stroke-linecap="round" stroke-linejoin="round"
                            d="M12 6.042A8.967 8.967 0 0 0 6 3.75c-1.052 0-2.062.18-3 .512v14.25A8.987 8.987 0 0 1 6 18c2.305 0 4.408.867 6 2.292m0-14.25a8.966 8.966 0 0 1 6-2.292c1.052 0 2.062.18 3 .512v14.25A8.987 8.987 0 0 0 18 18a8.967 8.967 0 0 0-6 2.292m0-14.25v14.25" />
                    </svg>
                    Browse courses
                </a>
                @auth
                    <a href="{{ route('dashboard') }}"
                        class="flex items-center gap-3 rounded-xl px-3 py-2.5 text-sm font-semibold text-slate-700 transition hover:bg-slate-100"
                        @click="mobileNav = false">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8"
                            stroke="currentColor" class="size-5 text-slate-400">
                            <path stroke-linecap="round" stroke-linejoin="round"
                                d="M5.25 5.653c0-.856.917-1.398 1.667-.986l11.54 6.348a1.125 1.125 0 0 1 0 1.971l-11.54 6.347a1.125 1.125 0 0 1-1.667-.985V5.653Z" />
                        </svg>
                        My learning
                    </a>
                    <a href="{{ route('assignments.index') }}"
                        class="flex items-center gap-3 rounded-xl px-3 py-2.5 text-sm font-semibold text-slate-700 transition hover:bg-slate-100"
                        @click="mobileNav = false">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8"
                            stroke="currentColor" class="size-5 text-slate-400">
                            <path stroke-linecap="round" stroke-linejoin="round"
                                d="M9 12h3.75M9 15h3.75M9 18h3.75m3 .75H18a2.25 2.25 0 0 0 2.25-2.25V6.108c0-1.135-.845-2.098-1.976-2.192a48.424 48.424 0 0 0-1.123-.08m-5.801 0c-.065.21-.1.433-.1.664 0 .414.336.75.75.75h4.5a.75.75 0 0 0 .75-.75 2.25 2.25 0 0 0-.1-.664m-5.8 0A2.251 2.251 0 0 1 13.5 2.25H15c1.012 0 1.867.668 2.15 1.586m-5.8 0c-.376.023-.75.05-1.124.08C9.095 4.01 8.25 4.973 8.25 6.108V8.25m0 0H4.875c-.621 0-1.125.504-1.125 1.125v11.25c0 .621.504 1.125 1.125 1.125h9.75c.621 0 1.125-.504 1.125-1.125V9.375c0-.621-.504-1.125-1.125-1.125H8.25ZM6.75 12h.008v.008H6.75V12Zm0 3h.008v.008H6.75V15Zm0 3h.008v.008H6.75V18Z" />
                        </svg>
                        Assignments
                    </a>
                @endauth
            </nav>

            <div class="mt-4 px-4">
                <p class="mb-2 px-1 text-[11px] font-semibold uppercase tracking-widest text-slate-400">Categories</p>
                <div class="grid grid-cols-1 gap-0.5">
                    @forelse ($categories as $category)
                        <a href="{{ route('courses.index', ['category' => $category->id]) }}"
                            class="flex items-center justify-between rounded-xl px-3 py-2 text-sm text-slate-600 transition hover:bg-slate-100"
                            @click="mobileNav = false">
                            <span class="truncate">{{ $category->name }}</span>
                            <span class="ml-2 shrink-0 text-xs text-slate-400">{{ $category->courses_count }}</span>
                        </a>
                    @empty
                        <p class="px-3 py-2 text-sm text-slate-500">Categories are coming soon.</p>
                    @endforelse
                </div>
            </div>

            <div class="mt-auto border-t border-slate-100 p-4 pb-[max(1rem,env(safe-area-inset-bottom))]">
                @guest
                    <a href="{{ route('login') }}" class="btn btn-secondary btn-md w-full">Log in</a>
                    <a href="{{ route('register') }}" class="btn btn-primary btn-md mt-2 w-full">Sign up free</a>
                @endguest
                @auth
                    <div class="mb-3 flex items-center gap-2.5 rounded-xl bg-slate-50 p-2.5">
                        <span
                            class="grid size-9 shrink-0 place-items-center rounded-full bg-gradient-to-br from-brand-500 to-brand-700 text-sm font-bold text-white">
                            {{ strtoupper(mb_substr(auth()->user()->name, 0, 1)) }}
                        </span>
                        <div class="min-w-0">
                            <p class="truncate text-sm font-semibold text-slate-900">{{ auth()->user()->name }}</p>
                            <p class="truncate text-xs text-slate-500">{{ auth()->user()->email }}</p>
                        </div>
                    </div>
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button type="submit" class="btn btn-secondary btn-md w-full">Log out</button>
                    </form>
                @endauth
            </div>
        </aside>
    </div>

    {{-- ============================ Main ============================ --}}
    <main class="mx-auto w-full max-w-7xl grow px-4 sm:px-6 lg:px-8">
        {{ $slot }}
    </main>

    {{-- ============================ Footer ============================ --}}
    <footer class="mt-16 border-t border-slate-200/80 bg-slate-50/60">
        <div class="mx-auto max-w-7xl px-4 pt-12 pb-28 sm:px-6 lg:px-8 lg:pb-12">
            <div class="grid gap-10 sm:grid-cols-2 lg:grid-cols-5">
                <div class="lg:col-span-2">
                    <a href="{{ route('home') }}" class="flex items-center gap-2.5">
                        @if ($siteSettings->logo)
                            <img src="{{ \Illuminate\Support\Facades\Storage::disk('public')->url($siteSettings->logo) }}"
                                alt="{{ $siteSettings->displayName() }}" class="h-9 w-auto max-w-40 object-contain">
                        @else
                            <span
                                class="grid size-9 place-items-center rounded-xl bg-gradient-to-br from-brand-500 to-brand-700 text-white">
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8"
                                    stroke="currentColor" class="size-5">
                                    <path stroke-linecap="round" stroke-linejoin="round"
                                        d="M4.26 10.147a60.438 60.438 0 0 0-.491 6.347A48.628 48.628 0 0 1 12 20.904a48.628 48.628 0 0 1 8.232-4.41 60.46 60.46 0 0 0-.491-6.347m-15.482 0a50.636 50.636 0 0 0-2.658-.8mA50.565 50.565 0 0 1 12 13.489a50.701 50.701 0 0 1 7.74-3.342M6.75 15a.75.75 0 1 0 0-1.5.75.75 0 0 0 0 1.5Zm0 0v-3.675A55.378 55.378 0 0 1 12 8.443m-7.007 11.55A5.981 5.981 0 0 0 6.75 15.75v-1.5" />
                                </svg>
                            </span>
                        @endif
                        <span class="text-lg font-bold tracking-tight">{{ $siteSettings->displayName() }}</span>
                    </a>
                    <p class="mt-4 max-w-xs text-sm leading-relaxed text-slate-500">
                        {{ $siteSettings->tagline ?: $siteSettings->site_description ?: 'The modern learning platform for ambitious students — expert-taught courses, hands-on projects and certificates that get you hired.' }}
                    </p>
                    @if ($siteSettings->facebook_url || $siteSettings->twitter_url || $siteSettings->instagram_url || $siteSettings->linkedin_url || $siteSettings->youtube_url)
                        <div class="mt-5 flex gap-2">
                            @if ($siteSettings->facebook_url)
                                <a href="{{ $siteSettings->facebook_url }}" target="_blank" rel="noopener noreferrer"
                                    class="grid size-9 place-items-center rounded-lg bg-white text-slate-500 ring-1 ring-slate-200 transition hover:text-brand-600 hover:ring-brand-200"
                                    aria-label="Facebook">
                                    <svg viewBox="0 0 24 24" fill="currentColor" class="size-4">
                                        <path
                                            d="M22 12.06C22 6.5 17.52 2 12 2S2 6.5 2 12.06c0 5.02 3.66 9.18 8.44 9.94v-7.03H7.9v-2.91h2.54V9.85c0-2.5 1.49-3.89 3.77-3.89 1.09 0 2.24.2 2.24.2v2.46h-1.26c-1.24 0-1.63.77-1.63 1.56v1.88h2.78l-.45 2.91h-2.33V22c4.78-.76 8.44-4.92 8.44-9.94Z" />
                                    </svg>
                                </a>
                            @endif
                            @if ($siteSettings->twitter_url)
                                <a href="{{ $siteSettings->twitter_url }}" target="_blank" rel="noopener noreferrer"
                                    class="grid size-9 place-items-center rounded-lg bg-white text-slate-500 ring-1 ring-slate-200 transition hover:text-brand-600 hover:ring-brand-200"
                                    aria-label="Twitter / X">
                                    <svg viewBox="0 0 24 24" fill="currentColor" class="size-4">
                                        <path
                                            d="M18.244 2.25h3.308l-7.227 8.26 8.502 11.24H16.17l-5.214-6.817L4.99 21.75H1.68l7.73-8.835L1.254 2.25H8.08l4.713 6.231zm-1.161 17.52h1.833L7.084 4.126H5.117z" />
                                    </svg>
                                </a>
                            @endif
                            @if ($siteSettings->instagram_url)
                                <a href="{{ $siteSettings->instagram_url }}" target="_blank" rel="noopener noreferrer"
                                    class="grid size-9 place-items-center rounded-lg bg-white text-slate-500 ring-1 ring-slate-200 transition hover:text-brand-600 hover:ring-brand-200"
                                    aria-label="Instagram">
                                    <svg viewBox="0 0 24 24" fill="currentColor" class="size-4">
                                        <path
                                            d="M12 2.16c3.2 0 3.58.01 4.85.07 1.17.05 1.8.25 2.23.41.56.22.96.48 1.38.9.42.42.68.82.9 1.38.16.42.36 1.06.41 2.23.06 1.27.07 1.65.07 4.85s-.01 3.58-.07 4.85c-.05 1.17-.25 1.8-.41 2.23-.22.56-.48.96-.9 1.38-.42.42-.82.68-1.38.9-.42.16-1.06.36-2.23.41-1.27.06-1.65.07-4.85.07s-3.58-.01-4.85-.07c-1.17-.05-1.8-.25-2.23-.41-.56-.22-.96-.48-1.38-.9-.42-.42-.68-.82-.9-1.38-.16-.42-.36-1.06-.41-2.23C2.17 15.58 2.16 15.2 2.16 12s.01-3.58.07-4.85c.05-1.17.25-1.8.41-2.23.22-.56.48-.96.9-1.38.42-.42.82-.68 1.38-.9.42-.16 1.06-.36 2.23-.41C8.42 2.17 8.8 2.16 12 2.16Zm0 3.68A6.16 6.16 0 1 0 12 18.16 6.16 6.16 0 0 0 12 5.84Zm0 10.16A4 4 0 1 1 12 8a4 4 0 0 1 0 8Zm6.4-10.4a1.44 1.44 0 1 1-2.88 0 1.44 1.44 0 0 1 2.88 0Z" />
                                    </svg>
                                </a>
                            @endif
                            @if ($siteSettings->linkedin_url)
                                <a href="{{ $siteSettings->linkedin_url }}" target="_blank" rel="noopener noreferrer"
                                    class="grid size-9 place-items-center rounded-lg bg-white text-slate-500 ring-1 ring-slate-200 transition hover:text-brand-600 hover:ring-brand-200"
                                    aria-label="LinkedIn">
                                    <svg viewBox="0 0 24 24" fill="currentColor" class="size-4">
                                        <path
                                            d="M20.45 20.45h-3.55v-5.57c0-1.33-.03-3.04-1.85-3.04-1.85 0-2.14 1.45-2.14 2.94v5.67H9.36V9h3.41v1.56h.05c.47-.9 1.63-1.85 3.36-1.85 3.6 0 4.27 2.37 4.27 5.45zM5.34 7.43a2.06 2.06 0 1 1 0-4.12 2.06 2.06 0 0 1 0 4.12zM7.12 20.45H3.56V9h3.56zM22.22 0H1.77C.79 0 0 .77 0 1.72v20.56C0 23.23.79 24 1.77 24h20.45c.98 0 1.78-.77 1.78-1.72V1.72C24 .77 23.2 0 22.22 0z" />
                                    </svg>
                                </a>
                            @endif
                            @if ($siteSettings->youtube_url)
                                <a href="{{ $siteSettings->youtube_url }}" target="_blank" rel="noopener noreferrer"
                                    class="grid size-9 place-items-center rounded-lg bg-white text-slate-500 ring-1 ring-slate-200 transition hover:text-brand-600 hover:ring-brand-200"
                                    aria-label="YouTube">
                                    <svg viewBox="0 0 24 24" fill="currentColor" class="size-4">
                                        <path
                                            d="M23.5 6.19a3.02 3.02 0 0 0-2.12-2.14C19.51 3.55 12 3.55 12 3.55s-7.51 0-9.38.5A3.02 3.02 0 0 0 .5 6.19 31.6 31.6 0 0 0 0 12a31.6 31.6 0 0 0 .5 5.81 3.02 3.02 0 0 0 2.12 2.14c1.87.5 9.38.5 9.38.5s7.51 0 9.38-.5a3.02 3.02 0 0 0 2.12-2.14A31.6 31.6 0 0 0 24 12a31.6 31.6 0 0 0-.5-5.81zM9.55 15.57V8.43L15.82 12z" />
                                    </svg>
                                </a>
                            @endif
                        </div>
                    @endif
                </div>

                <div>
                    <p class="text-sm font-bold text-slate-900">Platform</p>
                    <ul class="mt-4 space-y-2.5 text-sm text-slate-500">
                        <li><a href="{{ route('courses.index') }}" class="transition hover:text-slate-900">All
                                courses</a></li>
                        <li><a href="{{ route('dashboard') }}" class="transition hover:text-slate-900">My learning</a>
                        </li>
                        <li><a href="{{ route('login') }}" class="transition hover:text-slate-900">Sign in</a></li>
                        <li><a href="{{ route('register') }}" class="transition hover:text-slate-900">Create account</a>
                        </li>
                    </ul>
                </div>

                <div>
                    <p class="text-sm font-bold text-slate-900">Categories</p>
                    <ul class="mt-4 space-y-2.5 text-sm text-slate-500">
                        @foreach (['Web Development', 'Design', 'Data Science', 'Business'] as $name)
                            <li><a href="{{ route('courses.index') }}"
                                    class="transition hover:text-slate-900">{{ $name }}</a></li>
                        @endforeach
                    </ul>
                </div>

                <div>
                    <p class="text-sm font-bold text-slate-900">Support</p>
                    <ul class="mt-4 space-y-2.5 text-sm text-slate-500">
                        <li><a href="#" class="transition hover:text-slate-900">Help center</a></li>
                        <li><a href="{{ $siteSettings->contact_email ? 'mailto:' . $siteSettings->contact_email : '#' }}"
                                class="transition hover:text-slate-900">Contact us</a></li>
                        <li><a href="#" class="transition hover:text-slate-900">Terms & privacy</a></li>
                        <li><a href="/ui-demo" class="text-brand-600 transition hover:text-brand-700">UI kit (demo)</a>
                        </li>
                    </ul>
                </div>
            </div>

            <div
                class="mt-10 flex flex-col items-center justify-between gap-3 border-t border-slate-200/80 pt-6 sm:flex-row">
                <p class="text-xs text-slate-500">
                    {{ $siteSettings->footer_text ?: '© ' . now()->year . ' ' . $siteSettings->displayName() . '. All rights reserved.' }}
                </p>
                <p class="text-xs text-slate-400">Designed for learning. Built with Laravel & Livewire.</p>
            </div>
        </div>
    </footer>

    {{-- ====================== Mobile glass tab bar ====================== --}}
    @php
        $mobileCartCount = app(\App\Services\CourseCart::class)->count();
        $onHome = request()->routeIs('home');
        $onCourses = request()->routeIs('courses.*');
        $onLearning = request()->routeIs('dashboard', 'assignments.*', 'lessons.*', 'quizzes.*', 'certificates.*');
        $tabItem =
            'group relative flex flex-1 flex-col items-center justify-center gap-1 rounded-xl py-1.5 text-[10px] font-semibold leading-none whitespace-nowrap transition-colors duration-150';
        $tabIcon = 'grid size-7 place-items-center rounded-xl transition-colors duration-150';
        $tabOn = 'text-brand-700';
        $tabOff = 'text-slate-500 hover:text-slate-900';
        $iconOn = 'bg-brand-50 text-brand-600 ring-1 ring-brand-100';
        $iconOff = 'text-slate-500 group-hover:bg-slate-100 group-hover:text-slate-700';
    @endphp

    <nav class="mobile-tabbar fixed inset-x-0 bottom-0 z-40 lg:hidden" aria-label="Quick navigation">
        <div class="mx-auto max-w-md px-3 pb-[max(0.5rem,env(safe-area-inset-bottom))]">
            <div
                class="flex items-stretch gap-0.5 rounded-2xl border border-white/70 bg-white/65 p-1.5 shadow-[0_16px_40px_-16px_rgba(15,23,42,0.5)] ring-1 ring-slate-900/5 backdrop-blur-2xl backdrop-saturate-150 supports-[backdrop-filter]:bg-white/55">

                {{-- Home --}}
                <a href="{{ route('home') }}" class="{{ $tabItem }} {{ $onHome ? $tabOn : $tabOff }}"
                    @if ($onHome) aria-current="page" @endif>
                    <span class="{{ $tabIcon }} {{ $onHome ? $iconOn : $iconOff }}">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8"
                            stroke="currentColor" class="size-5" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round"
                                d="m2.25 12 8.954-8.955c.44-.439 1.152-.439 1.591 0L21.75 12M4.5 9.75v10.125c0 .621.504 1.125 1.125 1.125H9.75v-4.875c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125V21h4.125c.621 0 1.125-.504 1.125-1.125V9.75" />
                        </svg>
                    </span>
                    <span>Home</span>
                </a>

                {{-- Courses --}}
                <a href="{{ route('courses.index') }}" class="{{ $tabItem }} {{ $onCourses ? $tabOn : $tabOff }}"
                    @if ($onCourses) aria-current="page" @endif>
                    <span class="{{ $tabIcon }} {{ $onCourses ? $iconOn : $iconOff }}">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8"
                            stroke="currentColor" class="size-5" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round"
                                d="M12 6.042A8.967 8.967 0 0 0 6 3.75c-1.052 0-2.062.18-3 .512v14.25A8.987 8.987 0 0 1 6 18c2.305 0 4.408.867 6 2.292m0-14.25a8.966 8.966 0 0 1 6-2.292c1.052 0 2.062.18 3 .512v14.25A8.987 8.987 0 0 0 18 18a8.967 8.967 0 0 0-6 2.292m0-14.25v14.25" />
                        </svg>
                    </span>
                    <span>Courses</span>
                </a>

                {{-- Cart --}}
                <button type="button" x-on:click="$dispatch('cart-open')"
                    class="{{ $tabItem }} text-slate-500 hover:text-slate-900" aria-label="Open cart">
                    <span class="{{ $tabIcon }} relative {{ $iconOff }}">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8"
                            stroke="currentColor" class="size-5" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round"
                                d="M2.25 3h1.386c.51 0 .955.343 1.087.835l.383 1.437M7.5 14.25a3 3 0 0 0-3 3h15.75m-12.75-3h11.218c1.121-2.3 2.1-4.684 2.924-7.138a60.114 60.114 0 0 0-16.536-1.84M7.5 14.25 5.106 5.272M6 20.25a.75.75 0 1 1-1.5 0 .75.75 0 0 1 1.5 0Zm12.75 0a.75.75 0 1 1-1.5 0 .75.75 0 0 1 1.5 0Z" />
                        </svg>
                        @if ($mobileCartCount > 0)
                            <span
                                class="absolute -top-1 -right-1 grid h-4 min-w-4 place-items-center rounded-full bg-brand-600 px-1 text-[9px] font-bold text-white ring-2 ring-white">
                                {{ $mobileCartCount > 99 ? '99+' : $mobileCartCount }}
                            </span>
                        @endif
                    </span>
                    <span>Cart</span>
                </button>

                {{-- Learning (auth) / Log in (guest) --}}
                @auth
                    <a href="{{ route('dashboard') }}"
                        class="{{ $tabItem }} {{ $onLearning ? $tabOn : $tabOff }}"
                        @if ($onLearning) aria-current="page" @endif>
                        <span class="{{ $tabIcon }} {{ $onLearning ? $iconOn : $iconOff }}">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                                stroke-width="1.8" stroke="currentColor" class="size-5" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                    d="M5.25 5.653c0-.856.917-1.398 1.667-.986l11.54 6.348a1.125 1.125 0 0 1 0 1.971l-11.54 6.347a1.125 1.125 0 0 1-1.667-.985V5.653Z" />
                            </svg>
                        </span>
                        <span>Learning</span>
                    </a>

                    {{-- Account --}}
                    <button type="button" x-on:click.stop="$dispatch('profile-open')"
                        class="{{ $tabItem }} text-slate-500 hover:text-slate-900" aria-label="Open account menu">
                        <span class="{{ $tabIcon }} {{ $iconOff }}">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                                stroke-width="1.8" stroke="currentColor" class="size-5" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                    d="M15.75 6a3.75 3.75 0 1 1-7.5 0 3.75 3.75 0 0 1 7.5 0ZM4.501 20.118a7.5 7.5 0 0 1 14.998 0A17.933 17.933 0 0 1 12 21.75c-2.676 0-5.216-.584-7.499-1.632Z" />
                            </svg>
                        </span>
                        <span>Account</span>
                    </button>
                @else
                    <a href="{{ route('login') }}" class="{{ $tabItem }} {{ $tabOff }}">
                        <span class="{{ $tabIcon }} {{ $iconOff }}">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                                stroke-width="1.8" stroke="currentColor" class="size-5" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                    d="M15.75 9V5.25A2.25 2.25 0 0 0 13.5 3h-6a2.25 2.25 0 0 0-2.25 2.25v13.5A2.25 2.25 0 0 0 7.5 21h6a2.25 2.25 0 0 0 2.25-2.25V15m3 0 3-3m0 0-3-3m3 3H9" />
                            </svg>
                        </span>
                        <span>Log in</span>
                    </a>

                    <a href="{{ route('register') }}" class="{{ $tabItem }} {{ $tabOff }}">
                        <span class="{{ $tabIcon }} {{ $iconOff }}">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                                stroke-width="1.8" stroke="currentColor" class="size-5" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                    d="M18 7.5v3m0 0v3m0-3h3m-3 0h-3m-2.25-4.125a3.375 3.375 0 1 1-6.75 0 3.375 3.375 0 0 1 6.75 0ZM3 19.235v-.11a6.375 6.375 0 0 1 12.75 0v.109A12.318 12.318 0 0 1 9.374 21c-2.331 0-4.512-.645-6.374-1.766Z" />
                            </svg>
                        </span>
                        <span>Sign up</span>
                    </a>
                @endauth
            </div>
        </div>
    </nav>

    {{-- ============================ Global cart ============================ --}}
    <livewire:cart-drawer />

    @livewireScripts
</body>

</html>
