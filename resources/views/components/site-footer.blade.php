@props(['blogSettings' => null])

<footer class="border-t border-stone-200 bg-white">
    <div class="mx-auto flex w-full max-w-7xl flex-col gap-6 px-4 py-8 sm:px-6 lg:px-8">
        <div class="flex flex-col gap-5 sm:flex-row sm:items-start sm:justify-between">
            <div class="flex max-w-sm flex-col gap-2">
                <a href="{{ route('home') }}" class="w-fit text-lg font-bold tracking-tight text-stone-950">{{ config('app.name', 'Laravel') }}</a>
                <p class="text-sm leading-6 text-stone-600">{{ $blogSettings?->site_description ?: 'Thoughtful stories, ideas, and guides from our community.' }}</p>
            </div>

            <nav aria-label="Footer navigation" class="flex flex-wrap gap-x-6 gap-y-3 text-sm font-medium">
                <a href="{{ route('home') }}" class="text-stone-600 hover:text-stone-950">Home</a>
                <a href="{{ route('posts.index') }}" class="text-stone-600 hover:text-stone-950">Articles</a>
                @foreach ($footerMenuItems as $menuItem)
                    <a href="{{ $menuItem->url }}" @if ($menuItem->is_external) target="_blank" rel="noopener noreferrer" @endif class="text-stone-600 hover:text-stone-950">{{ $menuItem->label }}</a>
                @endforeach
                @auth
                    <a href="{{ route('profile.edit') }}" class="text-stone-600 hover:text-stone-950">Profile</a>
                    @if (Auth::user()->is_admin)
                        <a href="{{ route('admin.dashboard') }}" class="text-stone-600 hover:text-stone-950">Admin</a>
                    @endif
                @else
                    <a href="{{ route('login') }}" class="text-stone-600 hover:text-stone-950">Log in</a>
                @endauth
            </nav>
        </div>
        <p class="border-t border-stone-200 pt-5 text-xs text-stone-500">&copy; {{ now()->year }} {{ config('app.name', 'Laravel') }}. All rights reserved.</p>
    </div>
</footer>
