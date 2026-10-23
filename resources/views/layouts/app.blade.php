<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        @php
            $siteTitle = $blogSettings?->site_title ?: config('app.name', 'Laravel');
            $pageTitle = $seoTitle ?: $siteTitle;
            if ($seoTitle && $seoTitle !== $siteTitle) {
                $pageTitle .= ' | '.$siteTitle;
            }
            $metaDescription = $seoDescription ?: $blogSettings?->meta_description;
            $socialImage = $seoImage ?: $blogSettings?->default_og_image;
            $canonicalUrl = $seoCanonical ?: url()->current();
            $isPublicSeoRoute = request()->routeIs('home', 'posts.index', 'posts.show', 'pages.show', 'categories.show', 'tags.show');
            $robots = ($blogSettings?->allow_indexing ?? true) && $isPublicSeoRoute && ! $seoNoindex
                ? 'index,follow'
                : 'noindex,nofollow';
        @endphp
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ $pageTitle }}</title>
        <meta name="robots" content="{{ $robots }}">
        <link rel="canonical" href="{{ $canonicalUrl }}">
        @if ($metaDescription)
            <meta name="description" content="{{ $metaDescription }}">
        @endif
        <meta property="og:type" content="{{ $seoType }}">
        <meta property="og:site_name" content="{{ $siteTitle }}">
        <meta property="og:title" content="{{ $pageTitle }}">
        @if ($metaDescription)
            <meta property="og:description" content="{{ $metaDescription }}">
            <meta name="twitter:description" content="{{ $metaDescription }}">
        @endif
        <meta property="og:url" content="{{ $canonicalUrl }}">
        @if ($socialImage)
            <meta property="og:image" content="{{ $socialImage }}">
            <meta name="twitter:card" content="summary_large_image">
            <meta name="twitter:image" content="{{ $socialImage }}">
        @else
            <meta name="twitter:card" content="summary">
        @endif
        <meta name="twitter:title" content="{{ $pageTitle }}">

        @fonts
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="flex min-h-screen flex-col bg-stone-50 font-sans text-stone-900 antialiased">
        <x-site-navigation :site-title="$siteTitle" />

        @isset($header)
            <div class="border-b border-stone-200 bg-white">
                <div class="mx-auto w-full max-w-7xl px-4 py-6 sm:px-6 lg:px-8">
                    {{ $header }}
                </div>
            </div>
        @endisset

        <main id="main-content" class="flex-1">
            {{ $slot }}
        </main>

        <x-site-footer :blog-settings="$blogSettings" />
    </body>
</html>
