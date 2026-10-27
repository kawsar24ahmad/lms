@props(['footer' => true, 'meta' => false])

<article class="card flex h-full w-full flex-col overflow-hidden" aria-hidden="true">
    <div class="skeleton aspect-video w-full rounded-none" style="border-radius: 0"></div>

    <div class="flex grow flex-col gap-2 p-4 sm:p-5">
        <div class="flex items-center justify-between gap-2">
            <div class="skeleton h-5 w-16 rounded-full"></div>
            <div class="skeleton h-5 w-14 rounded-full"></div>
        </div>

        <div class="skeleton h-5 w-4/5"></div>

        <div class="space-y-2">
            <div class="skeleton h-4 w-full"></div>
            <div class="skeleton h-4 w-2/3"></div>
        </div>

        <div class="skeleton h-4 w-1/3"></div>

        <div class="skeleton h-4 w-2/5"></div>

        @if ($meta)
            <div class="skeleton h-3 w-1/2"></div>
        @endif

        <div class="mt-auto flex items-center justify-between gap-2 pt-2">
            <div class="skeleton h-7 w-20"></div>
            <div class="skeleton h-4 w-12"></div>
        </div>
    </div>

    @if ($footer)
        <div class="border-t border-slate-100 px-4 py-2.5 sm:px-5">
            <div class="skeleton h-9 w-full rounded-xl"></div>
        </div>
    @endif
</article>