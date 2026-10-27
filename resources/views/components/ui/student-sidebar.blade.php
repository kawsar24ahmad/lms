@props([
    'active' => 'overview',
    'enrollmentsCount' => 0,
    'lessonsDone' => 0,
])

@php
    $user = auth()->user();

    $linkBase = 'flex items-center gap-2.5 rounded-xl px-3 py-2.5 text-sm font-medium transition';
    $linkActive = 'bg-brand-50 font-semibold text-brand-700';
    $linkIdle = 'text-slate-600 hover:bg-slate-100';

    $pendingAssignments = $user === null
        ? 0
        : \App\Models\Assignment::query()
            ->forStudent($user)
            ->whereDoesntHave('submissions', fn($query) => $query->where('user_id', $user->getKey()))
            ->count();
@endphp

<aside class="hidden lg:block lg:sticky lg:top-24 lg:self-start">
    <div class="card p-2.5">
        <nav class="space-y-1">
            <a href="{{ route('dashboard') }}"
                class="{{ $linkBase }} {{ $active === 'overview' ? $linkActive : $linkIdle }}">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8"
                    stroke="currentColor" class="size-4.5 {{ $active === 'overview' ? '' : 'text-slate-400' }}">
                    <path stroke-linecap="round" stroke-linejoin="round"
                        d="M3.75 6A2.25 2.25 0 0 1 6 3.75h2.25A2.25 2.25 0 0 1 10.5 6v2.25a2.25 2.25 0 0 1-2.25 2.25H6a2.25 2.25 0 0 1-2.25-2.25V6ZM3.75 15.75A2.25 2.25 0 0 1 6 13.5h2.25a2.25 2.25 0 0 1 2.25 2.25V18a2.25 2.25 0 0 1-2.25 2.25H6A2.25 2.25 0 0 1 3.75 18v-2.25ZM13.5 6a2.25 2.25 0 0 1 2.25-2.25H18A2.25 2.25 0 0 1 20.25 6v2.25A2.25 2.25 0 0 1 18 10.5h-2.25a2.25 2.25 0 0 1-2.25-2.25V6ZM13.5 15.75a2.25 2.25 0 0 1 2.25-2.25H18a2.25 2.25 0 0 1 2.25 2.25V18A2.25 2.25 0 0 1 18 20.25h-2.25A2.25 2.25 0 0 1 13.5 18v-2.25Z" />
                </svg>
                Overview
            </a>

            <a href="{{ route('dashboard') }}#courses"
                class="{{ $linkBase }} {{ $active === 'courses' ? $linkActive : $linkIdle }}">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8"
                    stroke="currentColor" class="size-4.5 {{ $active === 'courses' ? '' : 'text-slate-400' }}">
                    <path stroke-linecap="round" stroke-linejoin="round"
                        d="M12 6.042A8.967 8.967 0 0 0 6 3.75c-1.052 0-2.062.18-3 .512v14.25A8.987 8.987 0 0 1 6 18c2.305 0 4.408.867 6 2.292m0-14.25a8.966 8.966 0 0 1 6-2.292c1.052 0 2.062.18 3 .512v14.25A8.987 8.987 0 0 0 18 18a8.967 8.967 0 0 0-6 2.292m0-14.25v14.25" />
                </svg>
                My enrolled course{{ $enrollmentsCount === 1 ? '' : 's' }}
            </a>

            <a href="{{ route('assignments.index') }}"
                class="{{ $linkBase }} {{ $active === 'assignments' ? $linkActive : $linkIdle }}">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8"
                    stroke="currentColor" class="size-4.5 {{ $active === 'assignments' ? '' : 'text-slate-400' }}">
                    <path stroke-linecap="round" stroke-linejoin="round"
                        d="M9 12h3.75M9 15h3.75M9 18h3.75m3 .75H18a2.25 2.25 0 0 0 2.25-2.25V6.108c0-1.135-.845-2.098-1.976-2.192a48.424 48.424 0 0 0-1.123-.08m-5.801 0c-.065.21-.1.433-.1.664 0 .414.336.75.75.75h4.5a.75.75 0 0 0 .75-.75 2.25 2.25 0 0 0-.1-.664m-5.8 0A2.251 2.251 0 0 1 13.5 2.25H15c1.012 0 1.867.668 2.15 1.586m-5.8 0c-.376.023-.75.05-1.124.08C9.095 4.01 8.25 4.973 8.25 6.108V8.25m0 0H4.875c-.621 0-1.125.504-1.125 1.125v11.25c0 .621.504 1.125 1.125 1.125h9.75c.621 0 1.125-.504 1.125-1.125V9.375c0-.621-.504-1.125-1.125-1.125H8.25ZM6.75 12h.008v.008H6.75V12Zm0 3h.008v.008H6.75V15Zm0 3h.008v.008H6.75V18Z" />
                </svg>
                <span class="min-w-0 flex-1">Assignments</span>
                @if ($pendingAssignments > 0)
                    <span class="badge bg-amber-50 text-amber-700 ring-1 ring-amber-200">{{ $pendingAssignments }}</span>
                @endif
            </a>

            <a href="{{ route('courses.index') }}"
                class="{{ $linkBase }} {{ $active === 'browse' ? $linkActive : $linkIdle }}">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8"
                    stroke="currentColor" class="size-4.5 {{ $active === 'browse' ? '' : 'text-slate-400' }}">
                    <path stroke-linecap="round" stroke-linejoin="round"
                        d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607Z" />
                </svg>
                Browse courses
            </a>

            <a href="{{ route('dashboard') }}#certificates"
                class="{{ $linkBase }} {{ $active === 'certificates' ? $linkActive : $linkIdle }}">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8"
                    stroke="currentColor" class="size-4.5 {{ $active === 'certificates' ? '' : 'text-slate-400' }}">
                    <path stroke-linecap="round" stroke-linejoin="round"
                        d="M16.5 18.75h-9m9 0a3 3 0 0 1 3 3h-15a3 3 0 0 1 3-3m9 0v-3.375c0-.621-.503-1.125-1.125-1.125h-.871M7.5 18.75v-3.375c0-.621.504-1.125 1.125-1.125h.872m5.007 0H9.497m5.007 0a7.004 7.004 0 0 1-.878-3.425V12a6.375 6.375 0 0 0-12.75 0v.75c0 1.6.213 2.908.625 3.915m0 0A6.003 6.003 0 0 0 12 21a6.003 6.003 0 0 0 5.13-3.335" />
                </svg>
                Certificates
            </a>

            <a href="#"
                class="{{ $linkBase }} {{ $active === 'profile' ? $linkActive : $linkIdle }}">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8"
                    stroke="currentColor" class="size-4.5 {{ $active === 'profile' ? '' : 'text-slate-400' }}">
                    <path stroke-linecap="round" stroke-linejoin="round"
                        d="M17.982 18.725A7.488 7.488 0 0 0 12 15.75a7.488 7.488 0 0 0-5.982 2.975m11.963 0a9 9 0 1 0-11.963 0m11.963 0A8.966 8.966 0 0 1 12 21a8.966 8.966 0 0 1-5.982-2.275M15 9.75a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z" />
                </svg>
                Profile settings
            </a>
        </nav>

        {{-- Profile summary --}}
        <div class="mt-3 rounded-xl bg-slate-50 p-4 ring-1 ring-slate-200/70">
            <x-ui.avatar :name="$user?->name ?? 'Student'" size="md" />
            <p class="mt-2.5 truncate text-sm font-bold text-slate-900">{{ $user?->name }}</p>
            <p class="truncate text-xs text-slate-500">{{ $user?->email }}</p>

            <dl class="mt-3 space-y-2 border-t border-slate-200/80 pt-3 text-xs">
                <div class="flex justify-between">
                    <dt class="text-slate-500">Lessons completed</dt>
                    <dd class="font-bold text-slate-900">{{ $lessonsDone }}</dd>
                </div>
                <div class="flex justify-between">
                    <dt class="text-slate-500">Assignments due</dt>
                    <dd class="font-bold text-slate-900">{{ $pendingAssignments }}</dd>
                </div>
            </dl>
        </div>
    </div>
</aside>
