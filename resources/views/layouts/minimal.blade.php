<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">

        <title>{{ $title ?? $siteSettings->displayTitle() }}</title>

        @if ($siteSettings->favicon)
            <link rel="icon" href="{{ \Illuminate\Support\Facades\Storage::disk('public')->url($siteSettings->favicon) }}">
        @else
            <link rel="icon" href="/favicon.ico" sizes="any">
        @endif

        @fonts

        @vite(['resources/css/app.css', 'resources/js/app.js'])

        @livewireStyles

        <style>
            @media print {
                .no-print {
                    display: none !important;
                }

                body {
                    background: #fff !important;
                }
            }
        </style>
    </head>
    <body class="bg-gray-100 text-gray-900 antialiased">
        <x-ui.page-loader />

        <main class="mx-auto max-w-5xl px-4 py-8">
            {{ $slot }}
        </main>

        @livewireScripts
    </body>
</html>
