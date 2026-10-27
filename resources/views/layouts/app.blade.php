<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

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

    @livewireStyles
</head>

<body x-data="{ mobileNav: false }" @keydown.escape.window="mobileNav = false"
    class="flex min-h-screen flex-col bg-white font-sans text-slate-900 antialiased">
    <x-ui.page-loader />

    {{-- ============================ Header ============================ --}}
    <header x-data="{ scrolled: false, showCatMenu: false, showProfile: false }"
        @scroll.window="scrolled = window.scrollY > 12" @click.outside="showCatMenu = false; showProfile = false"
        class="sticky top-0 z-50 border-b transition-shadow duration-200"
        :class="scrolled || mobileNav ? 'border-slate-200/90 bg-white/95 shadow-(--shadow-card) backdrop-blur' : 'border-transparent bg-white'">
        <div class="mx-auto overflow-hidden flex h-16 max-w-7xl items-center gap-3 px-4 sm:px-6 lg:px-8">
            {{-- Brand --}}
            <button type="button"
                class="flex size-9 shrink-0 items-center justify-center rounded-lg text-slate-600 transition hover:bg-slate-100 lg:hidden"
                @click="mobileNav = true" aria-label="Open menu" x-cloak>
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8"
                    stroke="currentColor" class="size-6">
                    <path stroke-linecap="round" stroke-linejoin="round"
                        d="M3.75 6.75h16.5M3.75 12h16.5m-16.5 5.25h16.5" />
                </svg>
            </button>

            <a href="{{ route('home') }}" class="flex shrink-0 items-center gap-2.5">
                @if ($siteSettings->logo)
                    <img src="{{ \Illuminate\Support\Facades\Storage::disk('public')->url($siteSettings->logo) }}"
                        alt="{{ $siteSettings->displayName() }}" class="h-9 w-auto max-w-40 object-contain">
                @else
                    <span
                        class="grid size-9 place-items-center rounded-xl bg-gradient-to-br from-brand-500 to-brand-700 text-white shadow-sm">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8"
                            stroke="currentColor" class="size-5">
                            <path stroke-linecap="round" stroke-linejoin="round"
                                d="M4.26 10.147a60.438 60.438 0 0 0-.491 6.347A48.628 48.628 0 0 1 12 20.904a48.628 48.628 0 0 1 8.232-4.41 60.46 60.46 0 0 0-.491-6.347m-15.482 0a50.636 50.636 0 0 0-2.658-.8mA50.565 50.565 0 0 1 12 13.489a50.701 50.701 0 0 1 7.74-3.342M6.75 15a.75.75 0 1 0 0-1.5.75.75 0 0 0 0 1.5Zm0 0v-3.675A55.378 55.378 0 0 1 12 8.443m-7.007 11.55A5.981 5.981 0 0 0 6.75 15.75v-1.5" />
                        </svg>
                    </span>
                @endif
                <span class="text-lg font-bold tracking-tight text-slate-900">
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
            <div class="flex items-center gap-1.5 pl-1 sm:gap-2">
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
                        class="flex items-center gap-2 rounded-full p-0.5 pr-2.5 transition hover:bg-slate-100">
                        <span
                            class="grid size-9 place-items-center overflow-hidden rounded-full bg-gradient-to-br from-brand-500 to-brand-700 text-sm font-bold text-white ring-2 ring-white">
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
                        class="absolute top-full right-4 z-50 mt-1">
                        <div
                            class="w-56 overflow-hidden rounded-2xl bg-white p-1.5 ring-1 ring-slate-200 shadow-(--shadow-pop)">
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

        {{-- Categories dropdown --}}
        <div x-show="showCatMenu" x-cloak x-transition.opacity.duration.150ms
            class="absolute inset-x-0 top-full justify-start lg:flex">
            <div
                class="grid grid-cols-4 gap-1 rounded-2xl bg-white p-4 ring-1 ring-slate-200 shadow-(--shadow-pop) max-lg:hidden">
                @foreach ($categories as $category)
                    <a href="{{ route('courses.index', ['category' => $category->id]) }}"
                        class="rounded-xl p-3 transition hover:bg-slate-50">
                        <p class="text-sm font-semibold text-slate-900">{{ $category->name }}</p>
                        {{-- <p class="mt-0.5 text-xs leading-relaxed text-slate-500">{{ $category->description }}</p> --}}
                    </a>
                @endforeach
            </div>
        </div>
    </header>

    {{-- ====================== Mobile slide-out nav ====================== --}}
    <div x-cloak>
        <div x-show="mobileNav" x-transition.opacity.duration.200ms @click="mobileNav = false"
            class="fixed inset-0 z-50 bg-slate-900/40 backdrop-blur-xs lg:hidden"></div>

        <aside x-show="mobileNav" x-cloak x-transition:enter="transition-transform duration-300 ease-out"
            x-transition:enter-start="-translate-x-full" x-transition:enter-end="translate-x-0"
            x-transition:leave="transition-transform duration-200 ease-in" x-transition:leave-start="translate-x-0"
            x-transition:leave-end="-translate-x-full"
            class="fixed inset-y-0 left-0 z-50 flex w-80 max-w-[85vw] flex-col overflow-y-auto bg-white shadow-2xl lg:hidden">
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

            <form action="{{ route('courses.index') }}" method="GET" class="p-4">
                <input type="search" name="search" placeholder="Search courses..." class="input" />
            </form>

            <nav class="flex flex-col gap-1 px-4">
                <a href="{{ route('courses.index') }}"
                    class="flex items-center gap-3 rounded-xl px-3 py-2.5 text-sm font-semibold text-slate-700 transition hover:bg-slate-100"
                    @click="mobileNav = false">
                    Browse courses
                </a>
                @auth
                    <a href="{{ route('dashboard') }}"
                        class="flex items-center gap-3 rounded-xl px-3 py-2.5 text-sm font-semibold text-slate-700 transition hover:bg-slate-100"
                        @click="mobileNav = false">
                        My learning
                    </a>
                    <a href="{{ route('assignments.index') }}"
                        class="flex items-center gap-3 rounded-xl px-3 py-2.5 text-sm font-semibold text-slate-700 transition hover:bg-slate-100"
                        @click="mobileNav = false">
                        Assignments
                    </a>
                @endauth
            </nav>

            <div class="mt-4 px-4">
                <p class="mb-2 px-1 text-xs font-semibold uppercase tracking-widest text-slate-400">Categories</p>
                <div class="grid grid-cols-1 gap-1">
                    @foreach (['Web Development', 'Design', 'Data Science', 'Business'] as $name)
                        <a href="{{ route('courses.index') }}"
                            class="rounded-xl px-3 py-2 text-sm text-slate-600 transition hover:bg-slate-100">
                            {{ $name }}
                        </a>
                    @endforeach
                </div>
            </div>

            <div class="mt-auto border-t border-slate-100 p-4">
                @guest
                    <a href="{{ route('login') }}" class="btn btn-secondary btn-md w-full">Log in</a>
                    <a href="{{ route('register') }}" class="btn btn-primary btn-md mt-2 w-full">Sign up free</a>
                @endguest
                @auth
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
        <div class="mx-auto max-w-7xl px-4 py-12 sm:px-6 lg:px-8">
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

    {{-- ============================ Global cart ============================ --}}
    <livewire:cart-drawer />

    @livewireScripts
</body>

</html>
