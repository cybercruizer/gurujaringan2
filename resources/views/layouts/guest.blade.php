<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">
        <meta name="robots" content="noindex,nofollow">

        <title>{{ config('app.name', 'Laravel') }}</title>

        @fonts
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="flex min-h-screen flex-col bg-stone-100 font-sans text-stone-900 antialiased">
        <header class="border-b border-stone-200 bg-white">
            <div class="mx-auto flex w-full max-w-7xl items-center justify-between px-4 py-4 sm:px-6 lg:px-8">
                <a href="{{ route('home') }}" class="text-xl font-bold tracking-tight text-stone-950">
                    {{ config('app.name', 'Laravel') }}
                </a>
                <a href="{{ route('home') }}" class="text-sm font-medium text-stone-600 hover:text-stone-950">Back to blog</a>
            </div>
        </header>

        <main class="flex flex-1 items-center justify-center px-4 py-10 sm:px-6">
            <div class="w-full max-w-lg rounded-3xl border border-stone-200 bg-white p-6 shadow-sm sm:p-10">
                <div class="mb-8 flex flex-col gap-2">
                    <p class="text-xs font-semibold uppercase tracking-[0.18em] text-stone-500">Your account</p>
                    <h1 class="text-2xl font-bold tracking-tight text-stone-950 sm:text-3xl">
                        @if (request()->routeIs('login'))
                            Welcome back
                        @elseif (request()->routeIs('register'))
                            Join the community
                        @elseif (request()->routeIs('password.request'))
                            Reset your password
                        @elseif (request()->routeIs('verification.notice'))
                            Verify your email
                        @else
                            Account security
                        @endif
                    </h1>
                </div>
                {{ $slot }}
            </div>
        </main>

        <footer class="py-6 text-center text-sm text-stone-500">
            &copy; {{ now()->year }} {{ config('app.name', 'Laravel') }}
        </footer>
    </body>
</html>
